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
		
		@$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'], 'string', null);
		@$chart_as = DevblocksPlatform::importGPC($model->extension_params['chart_as'], 'string', null);
		@$options = DevblocksPlatform::importGPC($model->extension_params['options'], 'array', []);
		@$height = DevblocksPlatform::importGPC($model->extension_params['height'], 'integer', 0);
		
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
		
		if(false === ($results = $data->executeQuery($query, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'data' => [
				'columns' => $results['data'],
				'type' => $chart_as == 'pie' ? 'pie' : 'donut',
			],
			'donut' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'pie' => [
				'label' => [
					'show' => false,
					'format' => null,
				],
			],
			'tooltip' => [
				'format' => [
					'value' => null,
				],
			],
			'legend' => [
				'show' => true,
			]
		];
		
		$config_json['legend']['show']  = @$options['show_legend'] ? true : false;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
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
