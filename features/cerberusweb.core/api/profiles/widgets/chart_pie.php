<?php
class ProfileWidget_ChartPie extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.pie';

	function __construct($manifest=null) {
		parent::__construct($manifest);
	}
	
	function invoke(string $action, Model_ProfileWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_ProfileWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function render(Model_ProfileWidget $model, $context, $context_id) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'] ?? null, 'string', null);
		$chart_as = DevblocksPlatform::importGPC($model->extension_params['chart_as'] ?? null, 'string', null);
		$options = DevblocksPlatform::importGPC($model->extension_params['options'] ?? null, 'array', []);
		$height = DevblocksPlatform::importGPC($model->extension_params['height'] ?? null, 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'record__context' => $context,
			'record_id' => $context_id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $model->id,
		]);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query)
			return;
		
		$error = null;
		
		$bindings = $dict->getDictionary();
		
		if(false === ($results = $data->executeQuery($query, $bindings, $error))) {
			echo DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.error') . ': ' . $error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$tpl->assign('data', json_encode($results['data'] ?? []));
		$tpl->assign('chart_as', $chart_as == 'pie' ? 'pie' : 'donut');
		$tpl->assign('show_legend', !empty($options['show_legend']));
		$tpl->assign('height', $height ?: 320);

		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));

		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/pie/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/pie/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
