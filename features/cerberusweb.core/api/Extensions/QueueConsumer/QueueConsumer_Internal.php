<?php
namespace Cerb\Extensions\QueueConsumer;

use Cerb\Extensions\Extension_QueueConsumer;
use Model_Queue;
use Model_QueueJob;

class QueueConsumer_Internal extends Extension_QueueConsumer {
	const ID = 'cerb.queue.consumer.internal';
	
	function renderConfig(Model_Queue $model): void {
	}
	
	function invokeConfig($config_action, Model_Queue $model): void {
	}
	
	public function processQueueMessages(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job=null) : int {
		return 0;
	}
}
