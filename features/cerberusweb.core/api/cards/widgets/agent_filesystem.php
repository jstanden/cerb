<?php
use Cerb\Agent\FilesystemImporter;

class CardWidget_AgentFilesystem extends Extension_CardWidget {
	const ID = 'cerb.card.widget.agent_filesystem';

	// The upload/import actions live on the `agent_filesystem` profile page section, since the popup
	// that drives them outlives this widget's render.
	function invoke(string $action, Model_CardWidget $model) {
		return false;
	}

	function render(Model_CardWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();

		$target_context_id = $model->extension_params['filesystem_id'] ?? null;

		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);

		$target_context_id = intval($tpl_builder->build($target_context_id, $dict));

		if(!($filesystem = DAO_AgentFilesystem::get($target_context_id)))
			return;

		$dict = DevblocksDictionaryDelegate::getDictionaryFromModel($filesystem, Context_AgentFilesystem::ID);

		$queue_job_key = FilesystemImporter::getSingletonKey($filesystem->id);

		// Is there an active job? While one runs, the import button hides.
		$queue_job = DAO_QueueJob::getOpenByQueueAndSingleton('cerb.records.import', $queue_job_key);

		$stats = [
			'file_count' => $filesystem->file_count,
			'total_bytes' => $filesystem->total_bytes,
		];

		// Recent jobs (running, paused, or done) for this singleton key
		$recent_jobs = DAO_QueueJob::getWhere(
			sprintf('%s = %s', DAO_QueueJob::SINGLETON_KEY, Cerb_ORMHelper::qstr($queue_job_key)),
			DAO_QueueJob::CREATED_AT, false, 10,
		);

		$status_labels = [
			QueueJobStatus::RUNNING->value => 'Running',
			QueueJobStatus::PAUSED->value => 'Paused',
			QueueJobStatus::DONE->value => 'Done',
			QueueJobStatus::CANCELED->value => 'Canceled',
		];

		// Done counts come live from queue_message; recently finished rows age off on retention.
		$live_counts = DAO_QueueJob::getLiveCountsForJobs(array_map(fn($job) => $job->id, $recent_jobs));

		$sheet_dicts = [];
		$job_statuses = [];

		foreach($recent_jobs as $job) {
			$sheet_dicts[] = DevblocksDictionaryDelegate::instance([
				'_context' => Context_QueueJob::ID,
				'id' => $job->id,
				'_label' => $job->name,
				'name' => $job->name,
				'status' => $status_labels[$job->status_id] ?? '',
				'counts' => sprintf('%d / %d', $live_counts[$job->id]['done'] ?? 0, $job->count_total),
				'created_at' => $job->created_at,
			]);
			$job_statuses[$job->id] = $job->status_id;
		}

		$sheets = DevblocksPlatform::services()->sheet()->withDefaultTypes();
		$sheet_kata = <<<KATA
		layout:
		  style: table
		  headings@bool: yes
		  paging@bool: no

		columns:
		  date/created_at:
		    label: When
		  card/name:
		    label: Job
		    params:
		      context_key: _context
		      id_key: id
		      label_key: _label
		  text/status:
		    label: Status
		  text/counts:
		    label: Progress
		KATA;

		$error = null;
		$sheet = $sheets->parse($sheet_kata, $error);
		$layout = $sheets->getLayout($sheet);
		$columns = $sheets->getColumns($sheet);
		$rows = $sheets->getRows($sheet, $sheet_dicts);

		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $model);
		$tpl->assign('queue_job', $queue_job);
		$tpl->assign('stats', $stats);
		$tpl->assign('layout', $layout);
		$tpl->assign('columns', $columns);
		$tpl->assign('rows', $rows);
		$tpl->assign('job_statuses', $job_statuses);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/agent_filesystem/render.tpl');
	}

	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();

		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/agent_filesystem/config.tpl');
	}

	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}
