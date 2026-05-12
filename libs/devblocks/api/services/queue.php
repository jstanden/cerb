<?php
class _DevblocksQueueService {
	private static ?_DevblocksQueueService $_instance = null;
	
	private array $_queue_cache = [];
	private array $_status_buffer = ['success'=>[], 'failure'=>[]];
	private array $_jobs_buffer = [];
	
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
	
	public function getConcurrencySlot() {
		$db = DevblocksPlatform::services()->database();
		
		// Shuffle the max number of slots and attempt to reserve them
		$max_slots = APP_QUEUE_CONCURRENCY_SLOTS;
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
	 * @param string $queue_name
	 * @param array $messages
	 * @param string|null $error
	 * @param int $job_id
	 * @param int $available_at
	 * @return array|false
	 */
	public function enqueue(string $queue_name, array $messages, string &$error=null, int $job_id=0, int $available_at=0) {
		if(null == ($queue = $this->_getQueueByName($queue_name))) {
			$error = sprintf("Unknown queue `%s`", $queue_name);
			return false;
		}
		
		return DAO_QueueMessage::enqueue($queue, $messages, $job_id, $available_at);
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
	
	public function reportSuccess(array $messages, string $message='') : void {
		foreach($messages as $message)
			$this->_status_buffer['success'][$message->uuid] = true;
		$this->_trackJobIds($messages);
	}
	
	public function reportFailure(array $messages, string $message='') : void {
		foreach($messages as $message)
			$this->_status_buffer['failure'][$message->uuid] = true;
		$this->_trackJobIds($messages);
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
			DAO_QueueMessage::reportFailure(array_keys($this->_status_buffer['failure']));
			$this->_status_buffer['failure'] = [];
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
				// Re-check with master to avoid acting on a job another worker already finalized
				$current = DAO_QueueJob::get($job->id);

				if(!$current || $current->status_id == QueueJobStatus::DONE->value)
					continue;

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

				$queue = $queues[$current->queue_id] ?? null;

				if($queue && ($extension = $queue->getExtension())) {
					$extension->onQueueJobComplete($current);
				}

			} catch(Throwable $e) {
				DevblocksPlatform::logException($e);

			} finally {
				$db->ExecuteWriter(sprintf("DO RELEASE_LOCK(%s)", $db->qstr($lock_name)));
			}
		}
	}
}