<?php
use Ramsey\Uuid\Provider\Node\RandomNodeProvider;
use Ramsey\Uuid\Uuid;

enum QueueMessageStatus : int {
	case AVAILABLE = 0;
	case IN_FLIGHT = 1;
	case FAILED = 2;
	case DONE = 3;
}

class DAO_QueueMessage {
	/**
	 * @param Model_Queue $queue
	 * @param array $messages
	 * @param int $job_id
	 * @param int $available_at
	 * @param int $cardinality Work units represented by each message (default 1)
	 * @param int $retry_count Attempts already made (carried forward on a re-enqueue)
	 * @return array|false
	 */
	static function enqueue(Model_Queue $queue, array $messages, int $job_id=0, int $available_at=0, int $cardinality=1, int $retry_count=0) {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();

		if(empty($messages))
			return false;

		$results = [];
		$insert_values = [];

		foreach($messages as $message) {
			$uuid = Uuid::uuid6($nodeProvider->getNode());
			$message_uuid = $uuid->getHex();

			$insert_values[] = sprintf("(%s, %d, %d, %d, %d, %s, %s, %d, %d, %d)",
				'0x' . $db->escape($message_uuid),
				$queue->id,
				$job_id,
				QueueMessageStatus::AVAILABLE->value,
				time(),
				$db->escape('NULL'),
				$db->qstr(json_encode($message)),
				$available_at,
				max(1, $cardinality),
				max(0, $retry_count)
			);

			$results[] = $message_uuid->toString();
		}

		$db->ExecuteWriter(
			sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, claim_id, message, available_at, cardinality, retry_count) VALUES %s",
				implode(',', $insert_values)
			));

		return $results;
	}

	/**
	 * Claimable-now message counts per queue: status AVAILABLE, unclaimed, past its
	 * `available_at`, and belonging to no job or to a RUNNING one. Drives the background
	 * cron's queue dispatch, so it must agree with dequeue() about what "available" means --
	 * a queue reported here whose dequeue() then claims nothing makes the cron re-poll for
	 * its whole budget.
	 *
	 * @return array<int,int> queue_id => count
	 */
	static function getAvailableCountsByQueue() : array {
		$db = DevblocksPlatform::services()->database();

		$rows = $db->GetArrayMaster(sprintf(
			"SELECT queue_id, COUNT(*) AS hits FROM queue_message ".
			"WHERE status_id = %d AND claim_id IS NULL AND available_at < UNIX_TIMESTAMP() ".
			"AND %s ".
			"GROUP BY queue_id, status_id",
			QueueMessageStatus::AVAILABLE->value,
			DAO_QueueJob::getRunnableMessageSql()
		));

		if(!$rows)
			return [];

		return array_combine(
			array_column($rows, 'queue_id'),
			array_map('intval', array_column($rows, 'hits'))
		);
	}

	/**
	 * @param Model_Queue $queue
	 * @param int|null $limit
	 * @param $claim_id
	 * @param ?int $job_id (null=any, zero=no job)
	 * @return Model_QueueMessage[]
	 */
	static function dequeue(Model_Queue $queue, ?int $limit=1, &$claim_id=null, ?int $job_id=null) : array {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();

		if(!is_numeric($limit) || !$limit)
			$limit = 1;

		$uuid = Uuid::uuid6($nodeProvider->getNode());
		$claim_id = '0x' . $uuid->getHex();

		// The job-status gate rides on the CLAIM, not on the callers. Every consumer reaches
		// work through here -- the cron, the monitor widget's browser workers, and an
		// automation's `queue.pop` -- so this is the one place that can make a PAUSED job
		// actually stop. See DAO_QueueJob::getRunnableMessageSql().
		$db->ExecuteWriter(
			sprintf(
				"UPDATE queue_message SET status_id=%d, claim_id=%s, claimed_at=%d ".
				"WHERE queue_id=%d %sAND status_id=%d AND claim_id IS NULL AND available_at <= %d ".
				"AND %s LIMIT %d",
				QueueMessageStatus::IN_FLIGHT->value,
				$db->escape($claim_id),
				time(),
				$queue->id,
				!is_null($job_id) ? sprintf("AND job_id=%d ", $job_id) : '',
				QueueMessageStatus::AVAILABLE->value,
				time(),
				DAO_QueueJob::getRunnableMessageSql(),
				$limit
			)
		);

		$results = $db->GetArrayMaster(sprintf(
			"SELECT uuid, job_id, message, available_at, cardinality, retry_count FROM queue_message ".
			"WHERE queue_id=%d %sAND status_id=%d AND claim_id=%s",
			$queue->id,
			!is_null($job_id) ? sprintf("AND job_id=%d ", $job_id) : '',
			QueueMessageStatus::IN_FLIGHT->value,
			$db->escape($claim_id)
		));
		
		$messages = [];
		
		if(!$results)
			return $messages;
		
		foreach($results as $result) {
			$message = new Model_QueueMessage();
			$message->uuid = Uuid::fromBytes($result['uuid'])->getHex()->toString();
			$message->queue_id = intval($queue->id);
			$message->job_id = intval($result['job_id']);
			$message->message = json_decode($result['message'], true);
			$message->available_at = intval($result['available_at']);
			$message->cardinality = max(1, intval($result['cardinality']));
			$message->retry_count = intval($result['retry_count']);
			$messages[] = $message;
		}
		
		unset($results);

		return $messages;
	}

	/**
	 * Extend the lease on in-flight messages by restamping `claimed_at`, so a consumer that is
	 * demonstrably still working isn't reaped by getStalled(). This is what lets `claim_window_secs`
	 * be short: the window sizes how long a DEAD consumer holds work hostage, not how long a live one
	 * is allowed to take.
	 *
	 * Scoped to the caller's own `claim_id`. Without that, a message already reaped and re-claimed
	 * elsewhere would have the NEW holder's lease extended by the dead one.
	 *
	 * @param string[] $uuids 32-hex (or dashed) message uuids
	 * @param string $claim_id The `0x…` literal handed back by dequeue()
	 * @return int messages renewed
	 */
	static function renewClaims(array $uuids, string $claim_id) : int {
		$db = DevblocksPlatform::services()->database();

		if(!$uuids || '' === $claim_id)
			return 0;

		$literals = array_map(fn($uuid) => '0x' . str_replace('-', '', $db->escape($uuid)), $uuids);

		$db->ExecuteMaster(sprintf(
			"UPDATE queue_message SET claimed_at=%d WHERE uuid IN (%s) AND status_id=%d AND claim_id=%s",
			time(),
			implode(',', $literals),
			QueueMessageStatus::IN_FLIGHT->value,
			$db->escape($claim_id)
		));

		return intval($db->Affected_Rows());
	}

	static function reportSuccess(array $message_uuids) : void {
		self::_reportStatus(QueueMessageStatus::DONE, $message_uuids);
	}

	/**
	 * Retry-aware failure handling. A message is only written to terminal FAILED once it has
	 * exhausted its queue's `retry_max`; until then a failure returns it to AVAILABLE with an
	 * exponential backoff (via `available_at`) and an incremented `retry_count`. A queue with
	 * `retry_max = 0` fails immediately (no retries).
	 *
	 * @param Model_QueueMessage[] $messages Dequeued models carrying `queue_id` + `retry_count`,
	 *   so the retry decision needs no extra lookup.
	 */
	static function reportFailure(array $messages) : void {
		if(!$messages)
			return;

		$now = time();

		$queues = DAO_Queue::getAll();

		$terminal = [];   // raw uuid hex -> terminal FAILED
		$retries = [];    // "available_at:new_retry_count" => [raw uuid hex]

		foreach($messages as $message) {
			$queue = $queues[$message->queue_id] ?? null;
			$retry_max = $queue ? $queue->retry_max : 0;
			$retry_count = $message->retry_count;

			// retry_max == 0 means the queue never retries; >= caps total attempts at retry_max+1
			$disposition = _DevblocksQueueService::getRetryDisposition($retry_count, $retry_max, $queue->retry_window_secs, $now);

			if(!$disposition['will_retry']) {
				$terminal[] = $message->uuid;
			} else {
				$retries[$disposition['available_at'] . ':' . $disposition['next_retry_count']][] = $message->uuid;
			}
		}

		// Terminal failures reuse the shared status writer
		if($terminal)
			self::_reportStatus(QueueMessageStatus::FAILED, $terminal);

		// One re-enqueue per (available_at, new retry_count) group
		foreach($retries as $key => $group_uuids) {
			[$available_at, $new_retry_count] = explode(':', $key);
			self::requeue($group_uuids, intval($available_at), intval($new_retry_count));
		}
	}

	/**
	 * Return messages to AVAILABLE with a cleared claim and a given retry count, deferred until
	 * `available_at` so dequeue() re-claims them once it passes.
	 *
	 * PUBLIC because the queue's own retry policy is not the only legitimate reason to put a message back. A
	 * consumer that knows its failure cost NOTHING -- an LLM turn rejected with a 429, where the request never
	 * reached a model -- can requeue deliberately, which is the only way to retry at all on a queue whose
	 * `retry_max` is 0 by design. Consumers should call _DevblocksQueueService::requeueMessage() (attempt ran)
	 * or deferMessage() (attempt never started) rather than this directly.
	 *
	 * Requeuing is NOT reporting a status: nothing here touches the service's status buffer, so a caller that
	 * requeues a message must not also call reportStatus() for it.
	 */
	static function requeue(array $message_uuids, int $available_at, int $retry_count) : void {
		$db = DevblocksPlatform::services()->database();

		if(!$message_uuids)
			return;

		$uuid_literals = array_map(fn($uuid) => '0x' . str_replace('-', '', $db->escape($uuid)), $message_uuids);
		
		implode(',', $uuid_literals)
			|> (fn($uuids) => sprintf("UPDATE queue_message SET status_id=%d, claim_id=NULL, claimed_at=0, processed_at=0, available_at=%d, retry_count=%d WHERE uuid IN (%s)", QueueMessageStatus::AVAILABLE->value, $available_at, $retry_count, $uuids))
			|> $db->ExecuteWriter(...)
		;
	}

	static private function _reportStatus(QueueMessageStatus $status, $message_uuids) : void {
		$db = DevblocksPlatform::services()->database();
		
		if(!$message_uuids)
			return;
		
		$insert_values = array_map(
			fn($uuid) => '0x' . str_replace('-', '', $db->escape($uuid)),
			$message_uuids
		);
		
		$db->ExecuteWriter(sprintf("UPDATE queue_message SET status_id=%d, processed_at=%d WHERE uuid IN (%s)",
			$status->value,
			time(),
			implode(',', $insert_values)
		));
	}
	
	/**
	 * The unfinished messages already queued for one LLM session -- AVAILABLE or IN_FLIGHT, in the order
	 * they were enqueued.
	 *
	 * `session_id` lives inside the JSON payload rather than a column, which sounds expensive and is not:
	 * `queue_claimed` (queue_id, status_id, job_id, ...) narrows to the pending messages of ONE queue
	 * before any JSON is touched, and agent turns are all `job_id = 0`. That set is bounded by the
	 * concurrency pool plus whatever is waiting, so it is small by construction.
	 *
	 * @return string[] lowercase hex uuids
	 */
	static function getPendingUuidsForSession(int $queue_id, string $session_id) : array {
		$db = DevblocksPlatform::services()->database();

		if(!$queue_id || '' === $session_id)
			return [];

		return $db->GetArrayMaster(sprintf(
			"SELECT LOWER(HEX(uuid)) AS uuid FROM queue_message ".
			"WHERE queue_id = %d AND job_id = 0 AND status_id IN (%d,%d) ".
			"AND JSON_UNQUOTE(JSON_EXTRACT(message, '$.session_id')) = %s ".
			"ORDER BY created_at",
			$queue_id,
			QueueMessageStatus::AVAILABLE->value,
			QueueMessageStatus::IN_FLIGHT->value,
			$db->qstr($session_id)
		))
			|> (fn($rows) => array_column($rows, 'uuid'))
		;
	}
	
	/**
	 * Current status of specific messages by uuid, keyed by lowercase 32-char hex uuid → status_id
	 * (QueueMessageStatus). A uuid ABSENT from the result no longer exists (purged after DONE, or never
	 * created) — callers that need a definitive terminal AND presence must treat absence distinctly.
	 * Master read: a caller gating on a just-reported status can't tolerate replica lag.
	 *
	 * @param string[] $uuids 32-hex (or dashed) message uuids
	 * @return array<string,int> [uuid_hex => status_id]
	 */
	static function getStatusesByUuids(array $uuids) : array {
		$db = DevblocksPlatform::services()->database();

		if(!$uuids)
			return [];

		// Same raw-hex `0x…` literal idiom the DAO uses everywhere for `binary(16)` uuid comparisons.
		$literals = array_map(fn($uuid) => '0x' . str_replace('-', '', $db->escape($uuid)), $uuids);

		$rows = $db->GetArrayMaster(sprintf(
			"SELECT LOWER(HEX(uuid)) AS uuid, status_id FROM queue_message WHERE uuid IN (%s)",
			implode(',', $literals)
		));

		$out = [];

		foreach($rows as $row)
			$out[$row['uuid']] = intval($row['status_id']);

		return $out;
	}

	/**
	 * Everything a poller needs about specific messages: their status, when a worker may claim them, and how
	 * many attempts they've already had.
	 *
	 * Richer than getStatusesByUuids() because "is it AVAILABLE?" stopped being the useful question once a
	 * consumer could requeue: a message waiting out a retry is AVAILABLE with `available_at` in the FUTURE, and
	 * dequeue() skips it. A poller reading status alone spawns a worker per cycle for the whole wait, finds
	 * nothing each time, and can say nothing about how long it will be. `available_at` answers both.
	 *
	 * Master read, for the same reason getStatusesByUuids() is: this runs right after a status was written.
	 *
	 * @param string[] $uuids 32-hex (or dashed) message uuids
	 * @return array<string,array{status_id:int,available_at:int,retry_count:int}> Absent uuid = no longer exists
	 */
	static function getPollStateByUuids(array $uuids) : array {
		$db = DevblocksPlatform::services()->database();

		if(!$uuids)
			return [];

		$literals = array_map(fn($uuid) => '0x' . str_replace('-', '', $db->escape($uuid)), $uuids);

		$rows = $db->GetArrayMaster(sprintf(
			"SELECT LOWER(HEX(uuid)) AS uuid, status_id, available_at, retry_count FROM queue_message WHERE uuid IN (%s)",
			implode(',', $literals)
		));

		$out = [];

		foreach($rows as $row) {
			$out[$row['uuid']] = [
				'status_id' => intval($row['status_id']),
				'available_at' => intval($row['available_at']),
				'retry_count' => intval($row['retry_count']),
			];
		}

		return $out;
	}

	// Count messages a worker could claim RIGHT NOW (AVAILABLE + past their available_at). Lets a two-loop
	// client pace how many worker requests to spawn.
	static function countAvailable(int $queue_id) : int {
		$db = DevblocksPlatform::services()->database();

		return intval($db->GetOneReader(sprintf(
			"SELECT COUNT(*) FROM queue_message WHERE queue_id = %d AND status_id = %d AND claim_id IS NULL AND available_at <= %d",
			$queue_id,
			QueueMessageStatus::AVAILABLE->value,
			time()
		)));
	}

	/**
	 * Find in-flight messages whose claim outlived their queue's `claim_window_secs`
	 * (abandoned by a crashed/stalled consumer). Queues with a zero window never reap.
	 *
	 * Measured from the last renewal, not the original claim -- a live consumer restamps
	 * `claimed_at` as it works (renewClaims()), so a long run is never mistaken for a dead one.
	 *
	 * @return Model_QueueMessage[] Payload-free models (uuid, queue_id, job_id, retry_count)
	 */
	static function getStalled() : array {
		$db = DevblocksPlatform::services()->database();

		$results = $db->GetArrayMaster(sprintf(
			"SELECT m.uuid AS uuid, m.queue_id, m.job_id, m.retry_count ".
			"FROM queue_message m INNER JOIN queue q ON (q.id=m.queue_id) ".
			"WHERE m.status_id=%d AND q.claim_window_secs > 0 AND m.claimed_at < %d - q.claim_window_secs",
			QueueMessageStatus::IN_FLIGHT->value,
			time()
		));

		$messages = [];

		foreach($results ?: [] as $result) {
			$message = new Model_QueueMessage();
			$message->uuid = Uuid::fromBytes($result['uuid'])->getHex()->toString();
			$message->queue_id = intval($result['queue_id']);
			$message->job_id = intval($result['job_id']);
			$message->retry_count = intval($result['retry_count']);
			$messages[] = $message;
		}

		return $messages;
	}

	/**
	 * Delete only the open (available + inflight) messages for a job, leaving
	 * completed/failed history in place for audit until DAO_QueueMessage::maint()
	 * sweeps them. Used by the queue-job monitor's cancel action.
	 */
	public static function deleteOpenByJob(Model_QueueJob $job) : void {
		$db = DevblocksPlatform::services()->database();

		$db->ExecuteWriter(sprintf(
			"DELETE FROM queue_message WHERE job_id = %d AND status_id IN (0, 1)",
			$job->id
		));
	}

	static function deleteByJobIds(array $job_ids) : void {
		$db = DevblocksPlatform::services()->database();

		$job_ids = DevblocksPlatform::sanitizeArray($job_ids, 'int');

		if(!$job_ids) return;

		$db->ExecuteWriter(sprintf("DELETE FROM queue_message WHERE job_id IN (%s)",
			implode(',', $job_ids)
		));
	}

	static function deleteByQueueIds(array $queue_ids) : void {
		$db = DevblocksPlatform::services()->database();

		$queue_ids = DevblocksPlatform::sanitizeArray($queue_ids, 'int');

		if(!$queue_ids) return;

		$db->ExecuteWriter(sprintf("DELETE FROM queue_message WHERE queue_id IN (%s)",
			implode(',', $queue_ids)
		));
	}

	public static function maint() {
		$db = DevblocksPlatform::services()->database();

		// [TODO] This can be longer retention
		// [TODO] Configurable per queue?
		$before = time()-86400;

		$db->ExecuteWriter(sprintf("DELETE FROM queue_message WHERE status_id = %d AND processed_at < %d",
			QueueMessageStatus::DONE->value,
			$before
		));
	}
}

class Model_QueueMessage {
	public string $uuid = '';
	public int $queue_id = 0;
	public mixed $message = null;
	public int $job_id = 0;
	public int $available_at = 0;
	public int $cardinality = 1;
	public int $retry_count = 0;

	public function reportStatus(QueueMessageStatus $status, string $message='', array $metadata=[]) : void {
		$queue_service = DevblocksPlatform::services()->queue();

		if(QueueMessageStatus::DONE == $status) {
			$queue_service->reportSuccess([$this], $message, $metadata);
		} else {
			$queue_service->reportFailure([$this], $message, $metadata);
		}
	}
}