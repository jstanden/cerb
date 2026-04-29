<?php
class _DevblocksQueueService {
	private static ?_DevblocksQueueService $_instance = null;
	
	private array $_queue_cache = [];
	
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
	
	public function reportSuccess(array $message_uuids) : void {
		if($message_uuids)
			DAO_QueueMessage::reportSuccess($message_uuids);
	}
	
	public function reportFailure(array $message_uuids) : void {
		if($message_uuids)
			DAO_QueueMessage::reportFailure($message_uuids);
	}
	
	function maint() : void {
		// Purge completed queue messages
		DAO_QueueMessage::maint();
	}
}