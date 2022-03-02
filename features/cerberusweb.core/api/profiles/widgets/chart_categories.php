<?php
class ProfileWidget_ChartCategories extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.categories';

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
		$data_query = DevblocksPlatform::importGPC($model->extension_params['data_query'] ?? null, 'string', null);
		$xaxis_format = DevblocksPlatform::importGPC($model->extension_params['xaxis_format'] ?? null, 'string', 'label');
		$yaxis_format = DevblocksPlatform::importGPC($model->extension_params['yaxis_format'] ?? null, 'string', 'label');
		$height = DevblocksPlatform::importGPC($model->extension_params['height'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$error = null;
		
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
		
		if(false === ($results = $data->executeQuery($query, [], $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		@$xaxis_key = $results['_']['format_params']['xaxis_key'] ?: '';
		
		if(!array_key_exists('data', $results))
			return;
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'padding' => [
				'left' => 150,
			],
			'data' => [
				'x' => $xaxis_key,
				'columns' => $results['data'],
				'type' => 'bar',
				'colors' => [
					'hits' => '#1f77b4'
				]
			],
			'axis' => [
				// [TODO] Configurable
				'rotated' => true,
				'x' => [
					'type' => 'category',
					'tick' => [
						'format' => null,
						'multiline' => true,
						'multilineMax' => 2,
						'width' => 150,
					]
				],
				'y' => [
					'tick' => [
						'format' => null,
						'multiline' => true,
						'multilineMax' => 2,
						'rotate' => -90,
					]
				]
			],
			'legend' => [
				'show' => true,
			]
		];
		
		if(@$results['_']['stacked']) {
			$config_json['data']['type']  = 'bar';
			$groups = array_column($results['data'], 0);
			array_shift($groups);
			$config_json['data']['groups'] = [array_values($groups)];
			$config_json['legend']['show'] = true;
			
			if(!$height)
				$height = 100 + (50 * @count($results['data'][0]));
			
		} else if ($results) {
			$config_json['data']['type']  = 'bar';
			$config_json['legend']['show'] = false;
			
			if(!$height)
				$height = 100 + (50 * @count($results['data'][0]));
		}
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/categories/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/categories/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
