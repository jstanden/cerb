<?php
use Cerb\Records\QueueJobMonitor;

class CardWidget_QueueJobMonitor extends Extension_CardWidget {
	const ID = 'cerb.card.widget.queue.job.monitor';

	function render(Model_CardWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$active_worker = CerberusApplication::getActiveWorker();

		if(!($queue_job = DAO_QueueJob::get($context_id)))
			return;

		if(!Context_QueueJob::isReadableByActor($queue_job, $active_worker))
			return;

		$tpl->assign('widget', $model);
		$tpl->assign('queue_job', $queue_job);
		$tpl->assign('mode', QueueJobMonitor::determineMode($queue_job, $active_worker));
		$tpl->assign('progress', $queue_job->getProgress());

		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/queue_job_monitor/render.tpl');
	}

	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();

		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		$job_id = DevblocksPlatform::importGPC($_POST['card_context_id'] ?? null, 'int', 0);

		if(!($queue_job = DAO_QueueJob::get($job_id)))
			DevblocksPlatform::dieWithHttpError(null, 404);

		if(!Context_QueueJob::isReadableByActor($queue_job, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);

		switch($action) {
			case 'refresh':
				QueueJobMonitor::handleRefresh($queue_job);
				return true;
			case 'worker':
				if($queue_job->worker_id != $active_worker->id
					&& !Context_QueueJob::isWriteableByActor($queue_job, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				QueueJobMonitor::handleWorker($queue_job);
				return true;
			case 'pause':
				if($queue_job->worker_id != $active_worker->id
					&& !Context_QueueJob::isWriteableByActor($queue_job, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				QueueJobMonitor::handleSetStatus($queue_job, QueueJobStatus::PAUSED);
				return true;
			case 'resume':
				if($queue_job->worker_id != $active_worker->id
					&& !Context_QueueJob::isWriteableByActor($queue_job, $active_worker))
					DevblocksPlatform::dieWithHttpError(null, 403);
				QueueJobMonitor::handleSetStatus($queue_job, QueueJobStatus::RUNNING);
				return true;
		}

		return false;
	}

	function renderConfig(Model_CardWidget $model) {
	}

	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}
