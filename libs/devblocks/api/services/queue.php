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
				DAO_QueueJob::setStatus(array_keys($newly_finished_jobs), QueueJobStatus::DONE);
			}
			
			$this->_jobs_buffer = [];
		}
	}
}