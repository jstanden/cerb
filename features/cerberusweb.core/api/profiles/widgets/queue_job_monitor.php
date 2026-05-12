<?php
use Cerb\Records\QueueJobMonitor;

class ProfileWidget_QueueJobMonitor extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.queue.job.monitor';

	function render(Model_ProfileWidget $model, $context, $context_id) {
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

		// Surface any attachments produced by the job (e.g. worklist exports)
		$attachments = $queue_job->isDone()
			? DAO_Attachment::getByContextIds(CerberusContexts::CONTEXT_QUEUE_JOB, $queue_job->id)
			: [];
		$tpl->assign('attachments', $attachments);

		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/queue_job_monitor/render.tpl');
	}

	function invoke(string $action, Model_ProfileWidget $model) {
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

	function renderConfig(Model_ProfileWidget $model) {
	}

	function invokeConfig($config_action, Model_ProfileWidget $model) {
		return false;
	}
}
