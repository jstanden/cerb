<?php
namespace Cerb\Records;

use Context_QueueJob;
use DAO_Queue;
use DAO_QueueJob;
use DevblocksPlatform;
use Model_QueueJob;
use Model_Worker;
use QueueJobStatus;
use Throwable;

class QueueJobMonitor {
	public static function determineMode(Model_QueueJob $queue_job, Model_Worker $active_worker) : string {
		$is_paused = ($queue_job->status_id == QueueJobStatus::PAUSED->value);

		if($queue_job->worker_id == $active_worker->id)
			return $is_paused ? 'process_paused' : 'process';

		if(Context_QueueJob::isWriteableByActor($queue_job, $active_worker))
			return 'process_paused';

		return 'view';
	}

	public static function handleSetStatus(Model_QueueJob $queue_job, QueueJobStatus $new_status) : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		DAO_QueueJob::setStatus([$queue_job->id], $new_status);

		echo json_encode(['status_id' => $new_status->value]);
	}

	public static function handleRefresh(Model_QueueJob $queue_job) : void {
		$tpl = DevblocksPlatform::services()->template();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			$tpl->assign('progress', $queue_job->getProgress());

			if(!$queue_job->isDone() && 0 == ($queue_job->count_available + $queue_job->count_inflight)) {
				DAO_QueueJob::setStatus([$queue_job->id], QueueJobStatus::DONE);
				$queue_job->status_id = QueueJobStatus::DONE->value;
			}

			echo json_encode([
				'job_status' => $queue_job->status_id,
				'progress_html' => $tpl->fetch('devblocks:cerberusweb.core::internal/queue/progress_bar.tpl'),
			]);

		} catch(Throwable $e) {
			DevblocksPlatform::logException($e);
		}
	}

	public static function handleWorker(Model_QueueJob $queue_job) : void {
		$queue_service = DevblocksPlatform::services()->queue();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		if(null === ($slot = $queue_service->getConcurrencySlot())) {
			echo json_encode(['slot' => false]);
			return;
		}

		$stop_time = time() + 10;

		if(!($queue = DAO_Queue::get($queue_job->queue_id))
			|| !($queue_extension = $queue->getExtension())) {
			echo json_encode(['slot' => true, 'processed' => 0]);
			return;
		}

		$processed = $queue_extension->processQueueMessages($queue, $stop_time, 0, $queue_job);

		echo json_encode(['slot' => true, 'processed' => $processed]);
	}
}
