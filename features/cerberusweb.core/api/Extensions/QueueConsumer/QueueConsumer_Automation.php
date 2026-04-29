<?php
namespace Cerb\Extensions\QueueConsumer;

use Cerb\Extensions\Extension_QueueConsumer;
use Model_Queue;

class QueueConsumer_Automation extends Extension_QueueConsumer {
	const ID = 'cerb.queue.consumer.automation';
	
	function renderConfig(Model_Queue $model): void {
	}
	
	function invokeConfig($config_action, Model_Queue $model): void {
	}
	
	public function processQueueMessages(Model_Queue $queue, int $stop_time, int $count_hint, ?\Model_QueueJob $queue_job = null): int {
		return 0;
	}
}