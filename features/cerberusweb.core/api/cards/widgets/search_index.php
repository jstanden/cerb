<?php
class CardWidget_SearchIndex extends Extension_CardWidget {
	const ID = 'cerb.card.widget.search_index';
	
	function invoke(string $action, Model_CardWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if (!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		if ('reindex' == $action) {
			$this->_cardWidgetAction_reindex($model);
			return true;
		}
		
		return false;
	}
	
	private function _cardWidgetAction_reindex(Model_CardWidget $model): void {
		$index_id = DevblocksPlatform::importGPC($_POST['index_id'] ?? null, 'integer', 0);
		
		// Must be logged in
		if (!($active_worker = CerberusApplication::getActiveWorker()))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// Worker must be an admin
		if (!$active_worker->is_superuser)
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		// [TODO] reindex
		/*
		When we click reindex, it needs to assign us a temp namespace token (the overall job ID).
		This allows us to monitor job progress and spin up parallel workers from the observer.
		The cron.search scheduler would handle reindex catchall with low priority.
		This needs to be a reusable UI service since we'd reuse it everywhere (bulk update, import, automations, embeddings).
		Queue jobs need a way to link to a record (ex. search index) so it can be displayed on the card.
		All running queue jobs and their status/concurrency should be observable from somewhere (ex. search, setup).
		*/
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		if (!$index_id || !($search_index = DAO_SearchIndex::get($index_id))) {
			echo json_encode(['status' => 'error', 'error' => 'Invalid index']);
			return;
		}
		
		if(!($queue_job = $search_index->getExtension()->reindexDocumentsByModel($search_index))) {
			echo json_encode(['status' => 'error', 'error' => 'Failed to queue reindex job']);
			return;
		}
		
		// Client can begin monitoring job status, which triggers workers
		echo json_encode(['status' => 'ok', 'job_id' => $queue_job->id]);
	}
	
	function render(Model_CardWidget $model, $context, $context_id) {
		$target_context_id = $model->extension_params['search_index_id'] ?? null;
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$dict = DevblocksDictionaryDelegate::instance([
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_CARD_WIDGET,
			'widget_id' => $model->id,
			'worker__context' => CerberusContexts::CONTEXT_WORKER,
			'worker_id' => $active_worker->id,
		]);
		
		$target_context_id = intval($tpl_builder->build($target_context_id, $dict));
		
		if (!($search_index = DAO_SearchIndex::get($target_context_id)))
			return;
		
		if (!($search_extension = $search_index->getExtension()))
			return;
		
		$dict = DevblocksDictionaryDelegate::getDictionaryFromModel($search_index, Context_SearchIndex::ID);

		$queue_job_key = sprintf('search_index:%d:reindex', $search_index->id);

		// Is there an active job?
		$queue_job = DAO_QueueJob::getOpenByQueueAndSingleton('cerb.search.index', $queue_job_key);

		// Stats
		$registry = DevblocksPlatform::services()->registry();
		$stats = [
			'type_label' => $search_extension->manifest->name,
			'record_count' => $search_extension->getRecordCount($search_index),
			'indexed_count' => $search_extension->getIndexRecordCount($search_index),
			'last_indexed_at' => intval($registry->get(sprintf('search_index_%d.last_indexed_at', $search_index->id), DevblocksRegistryEntry::TYPE_NUMBER, 0)),
		];

		// Recent jobs (running, paused, or done) for this singleton key
		$recent_jobs = DAO_QueueJob::getWhere(
			sprintf('%s = %s', DAO_QueueJob::SINGLETON_KEY, Cerb_ORMHelper::qstr($queue_job_key)),
			DAO_QueueJob::CREATED_AT, false, 10,
		);

		// Build sheet dicts + a job_id => status_id map (used client-side to
		// decide whether closing the queue-job popup should refresh this widget)
		$status_labels = [
			QueueJobStatus::RUNNING->value => 'Running',
			QueueJobStatus::PAUSED->value => 'Paused',
			QueueJobStatus::DONE->value => 'Done',
		];

		$sheet_dicts = [];
		$job_statuses = [];

		foreach($recent_jobs as $job) {
			$sheet_dicts[] = DevblocksDictionaryDelegate::instance([
				'_context' => Context_QueueJob::ID,
				'id' => $job->id,
				'_label' => $job->name,
				'name' => $job->name,
				'status' => $status_labels[$job->status_id] ?? '',
				'counts' => sprintf('%d / %d', $job->count_done, $job->count_total),
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
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/search_index/render.tpl');
	}
	
	function renderConfig(Model_CardWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/cards/widgets/search_index/config.tpl');
	}
	
	function invokeConfig($action, Model_CardWidget $model) {
		return false;
	}
}