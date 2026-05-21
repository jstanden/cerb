<?php
namespace Cerb\Records;

use Context_QueueJob;
use DAO_Queue;
use DAO_QueueJob;
use DAO_QueueMessage;
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

	public static function handleCancel(Model_QueueJob $queue_job) : void {
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		// Status flip first so subsequent refresh polls / cron consumers see the
		// terminal state and don't try to spawn workers or run finalization.
		DAO_QueueJob::setStatus([$queue_job->id], QueueJobStatus::CANCELED);

		// Then drop any remaining work so no consumer can pick it up. Completed
		// and failed message rows stay for audit until DAO_QueueMessage::maint()
		// sweeps them on retention.
		DAO_QueueMessage::deleteOpenByJob($queue_job);

		// Re-aggregate queue_job.count_* from queue_message so the progress bar
		// (which reads the cached counts) reflects reality after the delete.
		DAO_QueueJob::syncProgress($queue_job->id);

		echo json_encode(['status_id' => QueueJobStatus::CANCELED->value]);
	}

	public static function handleRefresh(Model_QueueJob $queue_job) : void {
		$tpl = DevblocksPlatform::services()->template();

		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');

		try {
			// Route through the queue service so onQueueJobComplete() fires (creates
			// the export attachment, sends the worker notification, etc.). Flipping
			// status_id directly here would beat the worker request's shutdown
			// publish() to the per-job lock and silently skip finalization.
			if(!$queue_job->isDone()) {
				DevblocksPlatform::services()->queue()->finalizeJobsIfReady([$queue_job->id]);

				if($latest = DAO_QueueJob::get($queue_job->id))
					$queue_job = $latest;
			}

			$tpl->assign('progress', $queue_job->getProgress());

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

		if(null === ($queue_service->getConcurrencySlot())) {
			// Surface remaining so the widget can distinguish "throttled with work"
			// (yellow THROTTLED card) from "throttled but nothing left" (IDLE).
			echo json_encode([
				'slot' => false,
				'remaining' => DAO_QueueJob::getAvailableAndInFlightMessages($queue_job),
			]);
			return;
		}

		$stop_time = time() + 10;

		if(!($queue = DAO_Queue::get($queue_job->queue_id))
			|| !($queue_extension = $queue->getExtension())) {
			echo json_encode(['slot' => true, 'processed' => 0, 'remaining' => 0]);
			return;
		}

		$processed = $queue_extension->processQueueMessages($queue, $stop_time, 0, $queue_job);

		// Read open messages directly so the widget can size its worker pool to
		// the actual remaining work — cheaper and more accurate than waiting for
		// publish() at shutdown to refresh queue_job.count_*.
		$remaining = DAO_QueueJob::getAvailableAndInFlightMessages($queue_job);

		echo json_encode([
			'slot' => true,
			'processed' => $processed,
			'remaining' => $remaining,
		]);
	}
}
