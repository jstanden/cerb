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
		
		header('Content-Type: application/json; charset=utf-8');
		
		if (!$index_id || !($search_index = DAO_SearchIndex::get($index_id))) {
			echo json_encode(['status' => 'error', 'error' => 'Invalid index']);
			return;
		}
		
		if (!($queue = DAO_Queue::getByName('cerb.search.index'))) {
			echo json_encode(['status' => 'error', 'error' => 'Invalid queue']);
			return;
		}
		
		$job_key = sprintf('search_index:%d:reindex', $search_index->id);
		
		// If there's already a job running, return its ID'
		if (($model = DAO_QueueJob::getByUniqueKey($job_key))) {
			echo json_encode(['status' => 'ok', 'job_id' => $model->id, 'is_dupe' => true]);
			return;
			
		} else {
			// [TODO] If no other jobs, create a queue job and return its ID
			// [TODO] We'd count the records needed to reindex
			// [TODO] Insert the batched queue messages
			
			$model = new Model_QueueJob();
			$model->queue_id = $queue->id;
			$model->unique_key = $job_key; // One job per index at a time
			$model->progress_total = 12_345;
			$model->progress_current = 0;
			$model->max_attempts = 2;
			$model->concurrency = 3;
			$model->started_at = time();
			
			if (!($model = DAO_QueueJob::create($model))) {
				echo json_encode(['status' => 'error', 'error' => 'Failed to create job']);
				// [TODO] Error
				return;
			}
		}
		
		// [TODO] Client can begin monitoring job status, which triggers workers
		
		echo json_encode(['status' => 'ok', 'job_id' => $model->id]);
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
		
		// [TODO] We need a better way to find previous jobs on this record (links?)
		
		// Is there an active job?
		if (($job = DAO_QueueJob::getByUniqueKey(sprintf('search_index:%d:reindex', $search_index->id)))) {
			echo '<pre>';
			print_r($job);
			// [TODO] TEMP!
			$tpl->assign('job', $job);
			echo '</pre>';
		}
		
		// [TODO] Sync info
		// $search_extension->getStatistics();
		// $search_extension->indexDocumentsByModel();
		
		// [TODO] Batch all IDs in a single query
		// [TODO] When we reindex, we batch IDs and update the progress so new records + past index in parallel
		/*
		 insert into queue_message (uuid, queue_id, job_id, status_id, status_at, message) select uuid_to_bin(uuid()) as uuid, 3 as queue_id, 7 as job_id, 0 as status_id, unix_timestamp() as status_at, concat('{"index_id":',7,',"ids":[',group_concat(id ORDER BY id),']}') as message from (select id, ceil(row_number() over (order by id) / 100) AS batch FROM custom_record_1) batched group by batch
		*/
		
		var_dump($search_extension->manifest->name);
		
		$registry = DevblocksPlatform::services()->registry();
		var_dump($registry->get(sprintf('search_index_%d.last_indexed_at', $search_index->id)));
		var_dump($registry->get(sprintf('search_index_%d.last_indexed_id', $search_index->id)));
		
		// [TODO] Get the desired record count vs index count
		// [TODO] getFilterRecordCount
		var_dump($search_extension->getRecordCount($search_index));
		// [TODO] Add to interface
		//var_dump($search_extension->getIndexRecordCount($search_index));
		
		// [TODO] Test queries?
		
		$tpl->assign('dict', $dict);
		$tpl->assign('widget', $model);
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