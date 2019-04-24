<?php
class ProfileWidget_ChartTable extends Extension_ProfileWidget {
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_PROFILE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		if(0 != strcasecmp('table', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'table' format.");
			return;
		}
		
		$tpl->assign('widget', $model);
		$tpl->assign('results', $results);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/table/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/table/config.tpl');
	}
};