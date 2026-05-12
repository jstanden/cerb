<?php
namespace Cerb\Extensions\QueueConsumer;

use AutomationTrigger_RecordChanged;
use Cerb\Extensions\Extension_QueueConsumer;
use DevblocksPlatform;
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
			
		} elseif($queue->name == 'cerb.records.changed') {
			if ($stop_time > time())
				return AutomationTrigger_RecordChanged::processQueueEvents($queue, $stop_time, $count_hint, $queue_job);
			
		} elseif($queue->name == 'cerb.records.import') {
			if ($stop_time > time()) {
				$records = DevblocksPlatform::services()->records();
				$records->processImportQueue($queue, $stop_time, $count_hint, $queue_job);
			}

		} elseif($queue->name == 'cerb.records.export') {
			if ($stop_time > time()) {
				$records = DevblocksPlatform::services()->records();
				$records->processExportQueue($queue, $stop_time, $count_hint, $queue_job);
			}

		} elseif($queue->name == 'cerb.records.bulk_update') {
			if ($stop_time > time()) {
				$records = DevblocksPlatform::services()->records();
				$records->processBulkUpdateQueue($queue, $stop_time, $count_hint, $queue_job);
			}

		} elseif($queue->name == 'cerb.search.index') {
			if ($stop_time > time()) {
				$search = DevblocksPlatform::services()->search();
				$search->processQueue($queue, $stop_time, $count_hint, $queue_job);
			}
		}

		return 0;
	}

	public function onQueueJobComplete(Model_QueueJob $queue_job) : void {
		if(!($queue = \DAO_Queue::get($queue_job->queue_id)))
			return;

		if($queue->name == 'cerb.records.export') {
			$records = DevblocksPlatform::services()->records();
			$records->onExportJobComplete($queue_job);

		} elseif($queue->name == 'cerb.records.bulk_update') {
			$records = DevblocksPlatform::services()->records();
			$records->onBulkUpdateJobComplete($queue_job);
		}
	}
}
