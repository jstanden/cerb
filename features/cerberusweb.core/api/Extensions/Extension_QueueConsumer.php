<?php
namespace Cerb\Extensions;

use DevblocksExtension;
use DevblocksExtensionGetterTrait;
use Model_Queue;

abstract class Extension_QueueConsumer extends DevblocksExtension {
	use DevblocksExtensionGetterTrait;
	
	const POINT = 'cerb.queue.consumer';
	
	static $_registry = [];
	
	abstract function renderConfig(Model_Queue $model) : void;
	
	abstract function invokeConfig($config_action, Model_Queue $model) : void;
	
	function saveConfig(array $fields, $id, &$error = null): bool {
		return true;
	}
	
	abstract public function processQueueMessages(Model_Queue $queue, int $stop_time, int $count_hint, ?\Model_QueueJob $queue_job=null) : int;
}