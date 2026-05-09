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
		if($queue->name == 'cerb.metrics.publish') {
			if ($stop_time > time()) {
				$metrics = DevblocksPlatform::services()->metrics();
				$metrics->processQueue($queue, $stop_time, $count_hint, $queue_job);
			}
			
		}
		
		return 0;
	}
}
