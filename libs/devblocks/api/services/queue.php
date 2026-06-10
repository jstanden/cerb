<?php
class _DevblocksQueueService {
	private static ?_DevblocksQueueService $_instance = null;
	
	private array $_queue_cache = [];
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
	
	public function getConcurrencySlot(int $max_slots=APP_QUEUE_CONCURRENCY_SLOTS) {
		$db = DevblocksPlatform::services()->database();
		
		// If for some reason we're given 0 slots
		if(!$max_slots) return null;
		
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
	 * @param $consumer_id
	 * @param ?int $job_id
	 * @return Model_QueueMessage[]|false
	 */
	public function dequeue(string $queue_name, int $limit=1, &$consumer_id=null, ?int $job_id=null) : array|false {
		if(null == ($queue = $this->_getQueueByName($queue_name)))
			return false;
		
		return DAO_QueueMessage::dequeue($queue, $limit, $consumer_id, $job_id);
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

		// Update counts on jobs that changed
		if($this->_jobs_buffer) {
			$job_ids = array_keys($this->_jobs_buffer);

			foreach($job_ids as $job_id)
				DAO_QueueJob::syncProgress($job_id);

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