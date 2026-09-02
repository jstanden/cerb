<?php
class _DevblocksQueueService {
	private static ?_DevblocksQueueService $_instance = null;
	
	private array $_queue_cache = [];
	// Per-request, keyed soft/hard. Not a cross-request cache: the license is already a singleton that
	// validates once and its setting is already cached, so the only repetition worth removing is this
	// method running per SLOT LOCK -- every acquire, usage read and widget poll calls it. Nothing that
	// feeds it can change mid-request (singleton license, compile-time constant).
	private array $_max_concurrency_slots = [];
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
			$is_covered = is_null($license->upgrades) || $license->upgrades >= time();
			$slots_licensed = max($slots_community, $is_covered ? intval($license->seats) : 0);
		}
		
		return $this->_max_concurrency_slots[$memo_key] = $slots_licensed
			|> (fn($x) => $with_soft_cap ? min($x, $slots_configured) : $x)
			|> (fn($x) => max(0, $x))
		;
	}
	
	public function getConcurrencySlot(int $slot) : ?int {
		$db = DevblocksPlatform::services()->database();
		
		// Slot pool must be enabled
		if(($max_slots = self::getMaxConcurrencySlots()) < 1)
			return null;
		
		// Must be in the range 0...max_slots
		if($slot != DevblocksPlatform::intClamp($slot, 0, $max_slots))
			return null;
		
		// Check the slot
		$result = sprintf("queue_slot_%d", $slot)
			|> $db->qstr(...)
			|> (fn($x) => sprintf("SELECT GET_LOCK(%s, 0)", $x))
			|> $db->GetOneMaster(...)
		;
		
		return $result ? $slot : null;
	}
	
	/**
	 * Reserve one of the pool's `queue_slot_N` advisory locks, or null when they're all held.
	 */
	public function getAvailableConcurrencySlot(int $limit=PHP_INT_MAX) : ?int {
		$db = DevblocksPlatform::services()->database();
		
		if(($max_slots = self::getMaxConcurrencySlots()) < 1)
			return null;
		
		$max_slots = min($limit, $max_slots);
		
		// Shuffle the max number of slots and attempt to reserve them
		$slots = range(1, $max_slots);
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
	 * Put a claimed message back on the queue, to be re-claimed in `$delay_secs`, WITHOUT it counting as a
	 * failure.
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
		if('' === $message->uuid)
			return;

		DAO_QueueMessage::requeue(
			[$message->uuid],
			time() + max(0, $delay_secs),
			$message->retry_count + 1
		);
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

		return DAO_QueueMessage::dequeue($queue, $limit, $claim_id, $job_id);
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
	}

	/**
	 * Reap in-flight messages claimed longer than their queue's `claim_window_secs`
	 * (a crashed/stalled consumer never reported status). Each reaped message is
	 * treated exactly like a reported failure — metrics, retry backoff or terminal
	 * FAILED, job progress + finalization — flushed immediately rather than at
	 * shutdown. The claim window is the consumer's completion deadline; a consumer
	 * that finishes after its claim was reaped just re-reports the message's status.
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