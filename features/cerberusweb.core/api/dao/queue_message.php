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
			sprintf("INSERT INTO queue_message (uuid, queue_id, job_id, status_id, created_at, consumer_id, message, available_at, cardinality, retry_count) VALUES %s",
				implode(',', $insert_values)
			));

		return $results;
	}
	
	/**
	 * @param Model_Queue $queue
	 * @param int|null $limit
	 * @param $consumer_id
	 * @param ?int $job_id (null=any, zero=no job)
	 * @return Model_QueueMessage[]
	 */
	static function dequeue(Model_Queue $queue, ?int $limit=1, &$consumer_id=null, ?int $job_id=null) : array {
		$db = DevblocksPlatform::services()->database();
		$nodeProvider = new RandomNodeProvider();
		
		if(!is_numeric($limit) || !$limit)
			$limit = 1;
		
		$uuid = Uuid::uuid6($nodeProvider->getNode());
		$consumer_id = '0x' . $uuid->getHex();
		
		$db->ExecuteWriter(
			sprintf(
				"UPDATE queue_message SET status_id=%d, consumer_id=%s ".
				"WHERE queue_id=%d %sAND status_id=%d AND consumer_id IS NULL AND available_at <= %d LIMIT %d",
				QueueMessageStatus::IN_FLIGHT->value,
				$db->escape($consumer_id),
				$queue->id,
				!is_null($job_id) ? sprintf("AND job_id=%d ", $job_id) : '',
				QueueMessageStatus::AVAILABLE->value,
				time(),
				$limit
			)
		);
		
		$results = $db->GetArrayMaster(sprintf(
			"SELECT uuid, job_id, message, available_at, cardinality, retry_count FROM queue_message ".
			"WHERE queue_id=%d %sAND status_id=%d AND consumer_id=%s",
			$queue->id,
			!is_null($job_id) ? sprintf("AND job_id=%d ", $job_id) : '',
			QueueMessageStatus::IN_FLIGHT->value,
			$db->escape($consumer_id)
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
		
		$job_ids = array_unique(array_filter(array_column($messages, 'job_id')));
		
		// [TODO] This is inefficient if we're pulling single messages
		// [TODO] Do we want to cache counts on jobs or just do them dynamically/reliably from indexes?
		// If we have pulled from a job, update its counts
		if($job_ids) {
			foreach ($job_ids as $job_id)
				DAO_QueueJob::syncProgress($job_id);
		}
		
		return $messages;
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
			if($retry_max <= 0 || $retry_count >= $retry_max) {
				$terminal[] = $message->uuid;
			} else {
				$available_at = $now + _DevblocksQueueService::getRetryBackoffSecs($retry_count, $retry_max, $queue->retry_window_secs);
				$retries[$available_at . ':' . ($retry_count + 1)][] = $message->uuid;
			}
		}

		// Terminal failures reuse the shared status writer
		if($terminal)
			self::_reportStatus(QueueMessageStatus::FAILED, $terminal);

		// One re-enqueue per (available_at, new retry_count) group
		foreach($retries as $key => $group_uuids) {
			[$available_at, $new_retry_count] = explode(':', $key);
			self::_reEnqueueForRetry($group_uuids, intval($available_at), intval($new_retry_count));
		}
	}

	/**
	 * Return failed messages to AVAILABLE with a cleared claim and incremented retry count,
	 * deferred until `available_at` so dequeue() re-claims them after the backoff.
	 */
	private static function _reEnqueueForRetry(array $message_uuids, int $available_at, int $retry_count) : void {
		$db = DevblocksPlatform::services()->database();

		if(!$message_uuids)
			return;

		$uuid_literals = array_map(fn($uuid) => '0x' . $db->escape($uuid), $message_uuids);
		
		implode(',', $uuid_literals)
			|> (fn($uuids) => sprintf("UPDATE queue_message SET status_id=%d, consumer_id=NULL, processed_at=0, available_at=%d, retry_count=%d WHERE uuid IN (%s)", QueueMessageStatus::AVAILABLE->value, $available_at, $retry_count, $uuids))
			|> $db->ExecuteWriter(...)
		;
	}

	static private function _reportStatus(QueueMessageStatus $status, $message_uuids) : void {
		$db = DevblocksPlatform::services()->database();
		
		if(!$message_uuids)
			return;
		
		$insert_values = array_map(
			fn($uuid) => '0x' . $db->escape($uuid),
			$message_uuids
		);
		
		$db->ExecuteWriter(sprintf("UPDATE queue_message SET status_id=%d, processed_at=%d WHERE uuid IN (%s)",
			$status->value,
			time(),
			implode(',', $insert_values)
		));
	}
	
	// [TODO] Heartbeat for handling abandoned in-flight queue messages to re-drive?

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