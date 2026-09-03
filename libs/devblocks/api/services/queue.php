<?php
/**
 * Which half of the pool a drain draws from.
 *
 * The axis is HOW LONG A SLOT IS HELD, because that is what one kind of work does to the other. A
 * bulk update yields every hundred records; an agent turn holds its slot for as long as a provider
 * takes to answer. Without lanes a queue of turns takes the whole pool and every export waits behind
 * work measured in minutes.
 */
enum QueueLane : string {
	case Fast = 'fast';
	case Slow = 'slow';
}

class _DevblocksQueueService {
	private static ?_DevblocksQueueService $_instance = null;
	
	private array $_queue_cache = [];
	// Per-request, keyed soft/hard. Not a cross-request cache: the license is already a singleton that
	// validates once and its setting is already cached, so the only repetition worth removing is this
	// method running per SLOT LOCK -- every acquire, usage read and widget poll calls it. Nothing that
	// feeds it can change mid-request (singleton license, compile-time constant).
	private array $_max_concurrency_slots = [];
	// Messages this request currently holds a claim on: claim_id => [uuid => true]. Fed by dequeue(),
	// drained as each message reaches a disposition, and re-leased by heartbeat().
	private array $_claim_leases = [];
	private int $_claim_renewed_at = 0;
	// Sessions this PROCESS holds a turn lock on. Not a cache -- a correctness guard; see
	// getSessionTurnLock() for why the advisory lock alone cannot do this job.
	private array $_session_turn_locks = [];
	private array $_status_buffer = ['success'=>[], 'failure'=>[]];
	private array $_jobs_buffer = [];
	private array $_log_buffer = [];
	
	static function getInstance() : _DevblocksQueueService {
		if(is_null(self::$_instance))
			self::$_instance = new _DevblocksQueueService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	private function _getQueueByName($queue_name) {
		if(array_key_exists($queue_name, $this->_queue_cache))
			return $this->_queue_cache[$queue_name];
		
		$this->_queue_cache[$queue_name] = DAO_Queue::getByName($queue_name);
		
		return $this->_queue_cache[$queue_name];
	}
	
	public function getMaxConcurrencySlots($with_soft_cap=true) : int {
		$memo_key = $with_soft_cap ? 'soft' : 'hard';
		
		if(array_key_exists($memo_key, $this->_max_concurrency_slots))
			return $this->_max_concurrency_slots[$memo_key];
		
		// Please be honest
		$slots_community = 3;
		$slots_configured = APP_QUEUE_CONCURRENCY_SLOTS;
		
		if(defined('CERB_CLOUD_SEATS')) {
			$slots_licensed = CERB_CLOUD_SEATS;
			
		} else {
			$license = CerberusLicense::getInstance();
			$slots_licensed = max($slots_community, $license->isLicensed() ? intval($license->slots) : 0);
		}
		
		return $this->_max_concurrency_slots[$memo_key] = $slots_licensed
			|> (fn($x) => $with_soft_cap ? min($x, $slots_configured) : $x)
			|> (fn($x) => max(0, $x))
		;
	}
	
	/**
	 * How many slots each lane reserves for itself. The rest are the commons, which either lane may use.
	 *
	 * A quarter each to fast and slow, so the split is 1:1:2 and most of the pool stays shared -- lanes
	 * exist to stop one kind of work EXCLUDING the other, not to partition the pool into private halves.
	 *
	 * Below three there is nothing to divide: a slot per lane would leave no commons at all, so each lane
	 * could only ever use its own single slot and an idle pool would read as full. Everything is shared
	 * there instead.
	 *
	 * The floor of 1 is what makes the community pool of three behave as 1 fast / 1 slow / 1 shared.
	 * Plain intdiv(3, 4) is 0, which would leave the most common install with no lanes at all.
	 */
	static function getLaneWidth(int $max_slots) : int {
		if($max_slots < 3)
			return 0;

		return max(1, intdiv($max_slots, 4));
	}

	/**
	 * The slots a caller in `$lane` may attempt: its own, then the commons. Ascending and deterministic
	 * -- the CALLER shuffles, so this stays a pure function the tests can pin exact vectors on.
	 *
	 * A null lane means the whole pool, which is what an unclassified drain gets.
	 *
	 * @return int[] slot numbers, 1-based (slot 0 is the scheduler's and is never in the pool)
	 */
	static function getLaneSlots(int $max_slots, ?QueueLane $lane = null) : array {
		if($max_slots < 1)
			return [];

		$all = range(1, $max_slots);

		if(is_null($lane) || !($width = self::getLaneWidth($max_slots)))
			return $all;

		return array_merge(
			array_slice($all, (QueueLane::Fast === $lane) ? 0 : $width, $width),
			array_slice($all, 2 * $width)
		);
	}

	/**
	 * Acquire any free slot in `$lane`, or null. The caller names a LANE, never a slot -- which slot it
	 * gets is not its business and it must not assume the same one twice.
	 */
	public function getAvailableConcurrencySlot(?QueueLane $lane = null) : ?int {
		$db = DevblocksPlatform::services()->database();
		
		if(($max_slots = self::getMaxConcurrencySlots()) < 1)
			return null;
		
		// Shuffle so concurrent acquirers spread across the lane instead of contending on its first slot.
		// Trying each without replacement is O(lane) round trips worst case; at these pool sizes that is
		// cheaper than reading occupancy first and it needs no second query.
		$slots = self::getLaneSlots($max_slots, $lane);
		shuffle($slots);
		
		foreach($slots as $slot) {
			$slot_name = sprintf("queue_slot_%d", $slot);
			if($db->GetOneMaster(sprintf("SELECT GET_LOCK(%s, 0)", $db->qstr($slot_name))))
				return $slot;
		}

		return null;
	}
	
	/**
	 * Hand a slot back as soon as its drain finishes rather than waiting for the request to end.
	 * GET_LOCK is connection-scoped, so connection close remains the backstop for a crash; this
	 * just stops a 25s drain from holding a scarce slot for the rest of a long request.
	 */
	public function releaseConcurrencySlot(int $slot) : void {
		$db = DevblocksPlatform::services()->database();
		$db->ExecuteMaster(sprintf("DO RELEASE_LOCK(%s)", $db->qstr(sprintf("queue_slot_%d", $slot))));
	}
	
	/**
	 * Claim the exclusive right to advance ONE agent session, or fail immediately.
	 *
	 * ️ **This closes a hole that exists TODAY, independently of multiplexing.**
	 * `DAO_QueueMessage::dequeue()` scopes a claim by queue and job -- never by session -- so two
	 * drainers can each claim a DIFFERENT message for the SAME session and run concurrent turns. That
	 * is reachable in normal use: a worker can resume one conversation in several tabs, windows or
	 * devices, and resume does not filter out a session already open elsewhere, so each of them drives
	 * its own sidecar. `nextSessionTurn()` appends to session history and `resolveDanglingStream()`
	 * assumes it is resolving the newest thing there, so two concurrent turns interleave their appends
	 * and produce a tree that cannot be replayed.
	 *
	 * An advisory lock rather than a column, because the guarantee has to hold ACROSS PROCESSES -- the
	 * competing drainers are separate FPM children (and, once turns multiplex, separate fibers sharing
	 * one connection, which MySQL 8 allows since a session may hold many named locks at once).
	 *
	 * Non-blocking by design: a caller that loses this race must DEFER its message, not wait on it.
	 * Waiting would hold an FPM child for the length of somebody else's turn -- up to 900s -- which is
	 * the exact cost this whole track exists to stop paying.
	 *
	 * The name is hashed: session ids are opaque and MySQL caps a lock name at 64 characters, so
	 * hashing is what keeps this from silently truncating two sessions onto one lock.
	 */
	public function getSessionTurnLock(string $session_id) : bool {
		if('' === $session_id)
			return false;

		// THE IN-PROCESS SET IS NOT AN OPTIMISATION. `GET_LOCK` is RE-ENTRANT: a second
		// acquire on the SAME connection returns 1 and bumps a counter. So the advisory lock alone cannot
		// stop two turns in one process -- which is precisely the case multiplexing creates, where every
		// fiber shares one connection. Checked first, so a same-process collision is refused before the
		// round trip.
		//
		// It also makes release SAFE: re-entrancy means N acquires need N releases, so without this a
		// double acquire followed by one release would leave the lock held until the connection closed.
		if(array_key_exists($session_id, $this->_session_turn_locks))
			return false;

		$db = DevblocksPlatform::services()->database();

		$acquired = boolval($db->GetOneMaster(sprintf("SELECT GET_LOCK(%s, 0)",
			$db->qstr($this->_sessionTurnLockName($session_id))
		)));

		if($acquired)
			$this->_session_turn_locks[$session_id] = true;

		return $acquired;
	}

	public function releaseSessionTurnLock(string $session_id) : void {
		// Only release what THIS process actually took. A stray release would otherwise decrement a
		// counter we never incremented, handing the session away while a turn is still writing to it.
		if('' === $session_id || !array_key_exists($session_id, $this->_session_turn_locks))
			return;

		unset($this->_session_turn_locks[$session_id]);

		$db = DevblocksPlatform::services()->database();

		$db->ExecuteMaster(sprintf("DO RELEASE_LOCK(%s)",
			$db->qstr($this->_sessionTurnLockName($session_id))
		));
	}

	// NOT the planned `llm_turn_1..N` meter: this is keyed by session IDENTITY, that is an index into a
	// counted pool. Do not merge the two namespaces.
	private function _sessionTurnLockName(string $session_id) : string {
		return 'llm_session_turn_' . sha1($session_id);
	}

	// 529, not 503. `llm.php` already reads 429/529 as "overloaded, never took the request" and
	// explicitly rules 503 out as too vague, so a client that already knows how to back off from a
	// provider needs no second rule for us.
	const int HTTP_STATUS_OVERLOADED = 529;
	const int THROTTLED_RETRY_AFTER_SECS = 5;

	/**
	 * Refuse a drain because the pool is full, in the one place that knows the status code and the
	 * header. Neither `dieWithHttpError()` nor `respondWithErrorReason()` can carry a header, and the
	 * latter renders a Smarty page into what is meant to be JSON.
	 *
	 * `Retry-After` is a MINIMUM, not an instruction: a client waits max(header, its own backoff).
	 * That preserves a caller's own exponential backoff, which is better information than a flat
	 * header can be.
	 */
	public function respondThrottled(array $payload, ?int $retry_after = null) : void {
		$http = DevblocksPlatform::services()->http();

		$http->setHeader('Content-Type', 'application/json; charset=utf-8');
		$http->setHeader('Retry-After', strval($retry_after ?? self::THROTTLED_RETRY_AFTER_SECS));

		http_response_code(self::HTTP_STATUS_OVERLOADED);

		echo json_encode($payload);
	}

	/**
	 * How many of the pool's slots are held right now, in ONE round trip. Master-only:
	 * GET_LOCK state lives on the writer connection.
	 */
	public function getConcurrencyUsage() : array {
		$db = DevblocksPlatform::services()->database();
		
		if(($max_slots = self::getMaxConcurrencySlots()) < 1)
			return ['used' => 0, 'total' => 0];
		
		$terms = [];
		
		// IF(... IS NULL, 0, 1) rather than the shorter `IS NOT NULL`: summing the latter is a SYNTAX
		// ERROR (`a IS NOT NULL + b IS NOT NULL` doesn't parse), and it fails SILENTLY here -- the query
		// returns false, intval() makes it 0, and the pool reads as permanently idle so a throttle
		// notice never fires. It only parses at all for a 1-slot pool, which is why it looked fine.
		foreach(range(1, $max_slots) as $slot)
			$terms[] = sprintf("IF(IS_USED_LOCK(%s) IS NULL, 0, 1)", $db->qstr(sprintf("queue_slot_%d", $slot)));
		
		$used = $db->GetOneMaster('SELECT ' . implode(' + ', $terms));
		
		// Don't let a failed read masquerade as an idle pool -- report "unknown" so callers can stay quiet
		// instead of asserting 0 of N busy.
		if(false === $used || is_null($used))
			return ['used' => 0, 'total' => 0];
		
		return [
			'used' => intval($used),
			'total' => $max_slots,
		];
	}

	const int RETRY_BACKOFF_MIN_SECS = 10;

	/**
	 * Exponential backoff spreading `retry_max` attempts across `retry_window_secs`. The
	 * intervals for k=0..retry_max-1 form a geometric series summing to the window, so the
	 * final retry lands ~one window after the first failure. Pure/stateless — pass the
	 * queue's retry policy fields in.
	 */
	public static function getRetryBackoffSecs(int $retry_count, int $retry_max, int $retry_window_secs) : int {
		if($retry_max <= 0)
			return 0;

		// Cap the exponent so 2^retry_max can't overflow and early intervals stay sane
		$retry_max = min($retry_max, 16);
		$retry_count = max(0, min($retry_count, $retry_max - 1));

		$delay = (int) floor($retry_window_secs * (2 ** $retry_count) / ((2 ** $retry_max) - 1));

		return max(self::RETRY_BACKOFF_MIN_SECS, $delay);
	}

	/**
	 * Pure: given a message's current retry_count and the queue's policy, decide whether
	 * the next failure retries (and when) or goes terminal. Single source of truth for the
	 * retry decision — shared by the actual re-enqueue (reportFailure) and human-facing log
	 * text (consumers) so the two can't drift.
	 *
	 * @return array{will_retry:bool,backoff_secs:int,available_at:int,next_retry_count:int}
	 */
	public static function getRetryDisposition(int $retry_count, int $retry_max, int $retry_window_secs, int $now) : array {
		if($retry_max <= 0 || $retry_count >= $retry_max)
			return ['will_retry'=>false, 'backoff_secs'=>0, 'available_at'=>0, 'next_retry_count'=>$retry_count];

		$backoff = self::getRetryBackoffSecs($retry_count, $retry_max, $retry_window_secs);

		return ['will_retry'=>true, 'backoff_secs'=>$backoff, 'available_at'=>$now + $backoff, 'next_retry_count'=>$retry_count + 1];
	}

	/**
	 * Put a claimed message back on the queue, to be re-claimed in `$delay_secs`, without recording a
	 * FAILURE. Advances `retry_count` because the attempt RAN -- use deferMessage() when it never started.
	 *
	 * The third disposition, beside reportSuccess() and reportFailure(). Those two are terminal-ish judgements
	 * governed by the QUEUE's `retry_max`; this one is the consumer saying "I know what this failure cost, and
	 * it cost nothing, so let me have another go." That distinction is why it can't be expressed by turning
	 * `retry_max` on: a queue-wide policy retries whatever fails, including the failures that already billed us
	 * for a full turn, and it applies to every OTHER kind of message on the same queue too.
	 *
	 * Writes IMMEDIATELY rather than buffering. There's no batching win for a single message, and the
	 * interactive worker sidecar reads countAvailable() straight after publish(), so the row has to be visible
	 * by then. It also increments nothing else: no status buffer entry, and no `cerb.queue.messages.processed`
	 * increment, because the message has not been processed.
	 *
	 * @param int $delay_secs Seconds from now before a worker may claim it again; floored at 0.
	 */
	public function requeueMessage(Model_QueueMessage $message, int $delay_secs) : void {
		$this->_requeueMessage($message, $delay_secs, $message->retry_count + 1);
	}

	/**
	 * Put a claimed message back WITHOUT advancing `retry_count`, for work that never started -- it lost a
	 * lock, or its dependency was busy. There is no attempt to penalize.
	 *
	 * `retry_count` is free at the QUEUE layer (these queues run `retry_max = 0`, so queue policy never
	 * reads it), which is why the split is easy to miss. Consumers are not: `llm.php` treats a non-zero
	 * count as proof a prior attempt already called the provider, so it drops the caller's new messages
	 * and can ack the turn as "already landed". Bumping the counter for a deferral silently discards the
	 * message.
	 *
	 * @param int $delay_secs Seconds from now before a worker may claim it again; floored at 0.
	 */
	public function deferMessage(Model_QueueMessage $message, int $delay_secs) : void {
		$this->_requeueMessage($message, $delay_secs, $message->retry_count);
	}

	private function _requeueMessage(Model_QueueMessage $message, int $delay_secs, int $retry_count) : void {
		if('' === $message->uuid)
			return;

		DAO_QueueMessage::requeue(
			[$message->uuid],
			time() + max(0, $delay_secs),
			$retry_count
		);

		$this->_releaseClaimLeases([$message]);
	}

	private static function _retryNoticeCacheKey(string $message_uuid) : string {
		return 'queue_msg_retry_notice_' . $message_uuid;
	}

	/**
	 * WHY a message is sitting in a retry wait, in one human sentence, for whatever is watching it.
	 *
	 * A requeued message is invisible by construction: the row says AVAILABLE with a future `available_at` and
	 * nothing else, so a UI polling it can see THAT it's waiting and for how long, but never what happened.
	 * Left un-said, a silent 10-second gap in a chat is indistinguishable from a slow model — which is exactly
	 * the complaint that prompted this ("invisible to the user, but also no indication").
	 *
	 * Deliberately generic and out-of-band: `queue_message` has no column for it, the writer (a consumer, in
	 * another request) and the reader (an await marker) never meet, and any consumer that requeues can leave a
	 * sentence here without the poller knowing anything about that consumer. The reader states the timing
	 * itself, from `available_at`, so this holds the REASON only and can't go stale against the clock.
	 *
	 * Best-effort by design: an evicted slot degrades to a generic "waiting to retry", never to a wrong reason.
	 */
	public function setRetryNotice(string $message_uuid, string $notice, int $ttl_secs) : void {
		if('' === $message_uuid || '' === trim($notice))
			return;

		DevblocksPlatform::services()->cache()->save(
			trim($notice),
			self::_retryNoticeCacheKey($message_uuid),
			[],
			max(60, $ttl_secs)
		);
	}

	public function getRetryNotice(string $message_uuid) : string {
		if('' === $message_uuid)
			return '';

		$notice = DevblocksPlatform::services()->cache()->load(self::_retryNoticeCacheKey($message_uuid), true);

		return is_string($notice) ? $notice : '';
	}

	/**
	 * @param string $queue_name
	 * @param array $messages
	 * @param string|null $error
	 * @param int $job_id
	 * @param int $available_at
	 * @param int $cardinality
	 * @return array|false
	 */
	public function enqueue(string $queue_name, array $messages, ?string &$error=null, int $job_id=0, int $available_at=0, int $cardinality=1) {
		if(null == ($queue = $this->_getQueueByName($queue_name))) {
			$error = sprintf("Unknown queue `%s`", $queue_name);
			return false;
		}

		return DAO_QueueMessage::enqueue($queue, $messages, $job_id, $available_at, $cardinality);
	}
	
	/**
	 * @param string $queue_name
	 * @param int $limit
	 * @param $claim_id
	 * @param ?int $job_id
	 * @return Model_QueueMessage[]|false
	 */
	public function dequeue(string $queue_name, int $limit=1, &$claim_id=null, ?int $job_id=null) : array|false {
		if(null == ($queue = $this->_getQueueByName($queue_name)))
			return false;

		$messages = DAO_QueueMessage::dequeue($queue, $limit, $claim_id, $job_id);

		foreach($messages as $message)
			$this->_claim_leases[strval($claim_id)][$message->uuid] = true;

		if($messages)
			$this->_claim_renewed_at = time();

		return $messages;
	}

	const int CLAIM_RENEW_INTERVAL_SECS = 15;

	/**
	 * "Still working" — re-lease every message this request holds, so a short `claim_window_secs`
	 * reaps abandoned work quickly without ever reaping live work.
	 *
	 * Called from two places, which between them cover both shapes of long consumer:
	 *   - every HTTP transfer's curl progress callback (_DevblocksHttpService), so a single provider
	 *     round trip that runs for ten minutes keeps its claim alive the whole way down;
	 *   - each reportSuccess()/reportFailure(), so a batch consumer chewing through a claim of many
	 *     messages re-leases the rest as it goes.
	 *
	 * Cheap to call at any rate: it returns on an in-memory check when nothing is claimed, and
	 * writes at most once per CLAIM_RENEW_INTERVAL_SECS. curl calls its progress function about
	 * once a second, so the throttle is what keeps that from becoming an UPDATE per second.
	 */
	public function heartbeat() : void {
		if(!$this->_claim_leases)
			return;

		if(time() - $this->_claim_renewed_at < self::CLAIM_RENEW_INTERVAL_SECS)
			return;

		$this->_claim_renewed_at = time();

		foreach($this->_claim_leases as $claim_id => $uuids)
			DAO_QueueMessage::renewClaims(array_keys($uuids), strval($claim_id));
	}

	/**
	 * Stop re-leasing messages that have reached a disposition. A claim we keep renewing after we're
	 * done with it holds the reaper off work we've abandoned.
	 *
	 * @param Model_QueueMessage[] $messages
	 */
	private function _releaseClaimLeases(array $messages) : void {
		if(!$this->_claim_leases)
			return;

		foreach($this->_claim_leases as $claim_id => $uuids) {
			foreach($messages as $message)
				unset($uuids[$message->uuid]);

			if($uuids)
				$this->_claim_leases[$claim_id] = $uuids;
			else
				unset($this->_claim_leases[$claim_id]);
		}
	}

	/**
	 * The DEFINITIVE await-gate state for a set of message uuids: 'clear' | 'pending' | 'error'.
	 * We require every message to be PRESENT and terminal-DONE to clear — an AVAILABLE/IN_FLIGHT message is
	 * pending, and a FAILED or ABSENT (vanished/never-created) one is an error (absence must not read as done).
	 * Backs the automation engine's intrinsic `await:queue:` gate; master read (a gate can't tolerate lag).
	 *
	 * @param string[] $uuids
	 */
	public function awaitGate(array $uuids) : string {
		$uuids = array_values(array_filter(array_map('strval', $uuids)));

		if(!$uuids)
			return 'clear';

		$statuses = DAO_QueueMessage::getStatusesByUuids($uuids);
		$any_error = false;

		foreach($uuids as $uuid) {
			$key = strtolower(str_replace('-', '', $uuid));
			$status = $statuses[$key] ?? null;

			if(is_null($status)) {          // absent — vanished (never a normal state mid-await)
				$any_error = true;
				continue;
			}

			if(in_array($status, [QueueMessageStatus::AVAILABLE->value, QueueMessageStatus::IN_FLIGHT->value], true))
				return 'pending';           // definitively still working

			if(QueueMessageStatus::FAILED->value === $status)
				$any_error = true;
		}

		return $any_error ? 'error' : 'clear';
	}
	
	public function reportSuccess(array $messages, string $message='', array $metadata=[]) : void {
		$metrics = DevblocksPlatform::services()->metrics();
		foreach($messages as $queue_message) {
			// Count each message once; the buffer dedupes re-reports of the same uuid
			if(!array_key_exists($queue_message->uuid, $this->_status_buffer['success']))
				$metrics->increment('cerb.queue.messages.processed', 1, ['queue_id' => $queue_message->queue_id, 'job_id' => $queue_message->job_id, 'status_id' => QueueMessageStatus::DONE->value]);
			$this->_status_buffer['success'][$queue_message->uuid] = true;
		}
		$this->_trackJobIds($messages);
		$this->_bufferLogEntry($messages, 1 /* SUCCESS */, $message, $metadata);
		$this->_releaseClaimLeases($messages);
		$this->heartbeat();
	}

	public function reportFailure(array $messages, string $message='', array $metadata=[]) : void {
		$metrics = DevblocksPlatform::services()->metrics();
		foreach($messages as $queue_message) { /* @var $queue_message Model_QueueMessage */
			// Count each message once; the buffer dedupes re-reports of the same uuid
			if(!array_key_exists($queue_message->uuid, $this->_status_buffer['failure']))
				$metrics->increment('cerb.queue.messages.processed', 1, ['queue_id' => $queue_message->queue_id, 'job_id' => $queue_message->job_id, 'status_id' => QueueMessageStatus::FAILED->value]);
			// Buffer the model (payload stripped) so publish() has queue_id + retry_count
			// for retry decisions without re-querying.
			$buffered = clone($queue_message, [
				"message" => null
			]);
			$this->_status_buffer['failure'][$queue_message->uuid] = $buffered;
		}
		$this->_trackJobIds($messages);
		$this->_bufferLogEntry($messages, 3 /* ERROR */, $message, $metadata);
		$this->_releaseClaimLeases($messages);
		$this->heartbeat();
	}

	/**
	 * Reap in-flight messages claimed longer than their queue's `claim_window_secs`
	 * (a crashed/stalled consumer never reported status). Each reaped message is
	 * treated exactly like a reported failure — metrics, retry backoff or terminal
	 * FAILED, job progress + finalization — flushed immediately rather than at
	 * shutdown. The claim window is a SILENCE deadline, not a completion deadline -- a live
	 * consumer re-leases as it works (see heartbeat()), so reaching it means nothing has
	 * reported progress in that long. A consumer that finishes after its claim was reaped
	 * just re-reports the message's status.
	 */
	public function reapStalledMessages() : int {
		if(!($stalled = DAO_QueueMessage::getStalled()))
			return 0;

		// One reportFailure per job so each affected job gets its own log entry
		$messages_by_job = [];
		foreach($stalled as $message)
			$messages_by_job[$message->job_id][] = $message;

		foreach($messages_by_job as $job_messages) {
			$this->reportFailure(
				$job_messages,
				sprintf("Reclaimed %d stalled message(s) after the claim window expired", count($job_messages))
			);
		}

		// Flush now; releasing claims and starting backoff timers shouldn't wait for shutdown
		$this->publish();

		return count($stalled);
	}

	private function _bufferLogEntry(array $messages, int $level, string $message, array $metadata) : void {
		// Nothing worth recording — consumer didn't pass a message or metadata
		if($message === '' && !$metadata) return;

		// Derive job_id from the first message that has one. All messages in a
		// single reportSuccess/Failure call should share a job_id; fire-and-forget
		// messages (job_id=0) skip logging.
		$job_id = 0;
		foreach($messages as $queue_message) {
			if($queue_message->job_id) { $job_id = $queue_message->job_id; break; }
		}
		if(!$job_id) return;

		$this->_log_buffer[] = [
			'job_id'     => $job_id,
			'created_at' => time(),
			'level'      => $level,
			'message'    => $message,
			'metadata'   => $metadata,
		];
	}
	
	private function _trackJobIds(array $messages) : void {
		foreach(array_unique(array_column($messages, 'job_id')) as $job_id) {
			if($job_id) $this->_jobs_buffer[$job_id] = true;
		}
	}
	
	function maint() : void {
		// Purge completed queue messages after retention
		DAO_QueueMessage::maint();
	}
	
	/**
	 * Persist queue message stats
	 *
	 * @return void
	 */
	public function publish() {
		if($this->_status_buffer['success']) {
			DAO_QueueMessage::reportSuccess(array_keys($this->_status_buffer['success']));
			$this->_status_buffer['success'] = [];
		}
		
		if($this->_status_buffer['failure']) {
			DAO_QueueMessage::reportFailure(array_values($this->_status_buffer['failure']));
			$this->_status_buffer['failure'] = [];
		}

		// Flush any buffered job-log entries from this request's reportSuccess/Failure
		// calls. One row per call (with consumer-supplied message + metadata).
		if($this->_log_buffer) {
			DAO_QueueJobLog::insertBatch($this->_log_buffer);
			$this->_log_buffer = [];
		}

		// Finalize any jobs that just drained their last open message. Counts are
		// read live from queue_message on demand, so there's nothing to sync here.
		if($this->_jobs_buffer) {
			$job_ids = array_keys($this->_jobs_buffer);

			if(($newly_finished_jobs = DAO_QueueJob::checkForCompletedJobs($job_ids))) {
				$this->_finalizeJobs($newly_finished_jobs);
			}

			$this->_jobs_buffer = [];
		}
	}

	/**
	 * Finalize any of the given jobs that are message-exhausted but not yet DONE.
	 * Safe to call from outside the publish() shutdown flow (e.g. UI poll endpoints);
	 * the per-job advisory lock in _finalizeJobs() prevents racing the shutdown call.
	 *
	 * @return Model_QueueJob[] Jobs that were eligible (the lock decides who actually transitions).
	 */
	public function finalizeJobsIfReady(array $job_ids) : array {
		$ready = DAO_QueueJob::checkForCompletedJobs($job_ids);

		if($ready)
			$this->_finalizeJobs($ready);

		return $ready;
	}

	/**
	 * Transition each job to DONE under a per-job advisory lock so the consumer's
	 * onQueueJobComplete hook fires exactly once even with parallel consumers.
	 *
	 * @param Model_QueueJob[] $jobs
	 */
	private function _finalizeJobs(array $jobs) : void {
		$db = DevblocksPlatform::services()->database();
		$queues = DAO_Queue::getAll();

		foreach($jobs as $job) {
			$lock_name = sprintf('cerb_queue_job_complete:%d', $job->id);

			if(!$db->GetOneMaster(sprintf("SELECT GET_LOCK(%s, 0)", $db->qstr($lock_name))))
				continue;

			try {
				// Re-check with master to avoid acting on a job another worker already
				// finalized or canceled. A job could be canceled between selection and
				// lock acquisition; checkForCompletedJobs already filters DONE/CANCELED,
				// but this is defense in depth.
				$current = DAO_QueueJob::get($job->id);

				if(!$current || in_array($current->status_id, [QueueJobStatus::DONE->value, QueueJobStatus::CANCELED->value], true))
					continue;

				// Run the completion hook first. If it throws, the catch below logs it
				// and the status stays out of DONE — the next finalize attempt (from
				// another worker shutdown or the widget's refresh poll) will retry under
				// a fresh lock. Only after the hook has succeeded do we transition to
				// DONE so callers can treat status==DONE as "result is ready".
				$queue = $queues[$current->queue_id] ?? null;

				if($queue && ($extension = $queue->getExtension())) {
					$extension->onQueueJobComplete($current);
				}

				DAO_QueueJob::setStatus([$job->id], QueueJobStatus::DONE);

				// Abstract completion notification — fires for every job that has an originating worker.
				// Workers can suppress via the dont_notify_on_activities pref for this activity point.
				if($current->worker_id) {
					DAO_Notification::create([
						DAO_Notification::CONTEXT        => CerberusContexts::CONTEXT_QUEUE_JOB,
						DAO_Notification::CONTEXT_ID     => $current->id,
						DAO_Notification::WORKER_ID      => $current->worker_id,
						DAO_Notification::CREATED_DATE   => time(),
						DAO_Notification::IS_READ        => 0,
						DAO_Notification::ACTIVITY_POINT => 'cerb.queue.job.completed',
						DAO_Notification::ENTRY_JSON     => json_encode([
							'variables' => ['target' => $current->name],
							'urls'      => ['target' => 'cerb:' . CerberusContexts::CONTEXT_QUEUE_JOB . ':' . $current->id],
						]),
					]);
				}

			} catch(Throwable $e) {
				DevblocksPlatform::logException($e);

			} finally {
				$db->ExecuteWriter(sprintf("DO RELEASE_LOCK(%s)", $db->qstr($lock_name)));
			}
		}
	}
}