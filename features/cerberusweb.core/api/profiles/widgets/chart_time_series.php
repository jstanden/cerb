<?php
class ProfileWidget_ChartTimeSeries extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.timeseries';

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
		$chart_as = DevblocksPlatform::importGPC($model->extension_params['chart_as'] ?? null, 'string', 'line');
		$options = DevblocksPlatform::importGPC($model->extension_params['options'] ?? null, 'array', []);
		$xaxis_label = DevblocksPlatform::importGPC($model->extension_params['xaxis_label'] ?? null, 'string', '');
		$yaxis_label = DevblocksPlatform::importGPC($model->extension_params['yaxis_label'] ?? null, 'string', '');
		$yaxis_format = DevblocksPlatform::importGPC($model->extension_params['yaxis_format'] ?? null, 'string', '');
		$height = DevblocksPlatform::importGPC($model->extension_params['height'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
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
		
		$bindings = $dict->getDictionary();
		
		if(false === ($results = $data->executeQuery($query, $bindings, $error))) {
			echo DevblocksPlatform::strEscapeHtml(DevblocksPlatform::translateCapitalized('common.error') . ': ' . $error);
			return;
		}
		
		if(!$results) {
			echo "(no data)";
			return;
		}
		if(0 != strcasecmp('timeseries', @$results['_']['format'])) {
			echo DevblocksPlatform::strEscapeHtml("The data should be in 'timeseries' format.");
			return;
		}
		
		// Error
		$xaxis_key = @$results['_']['format_params']['xaxis_key'];
		$xaxis_format = @$results['_']['format_params']['xaxis_format'];
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $model->id),
			'data' => [
				'x' => 'ts',
				'xFormat' => '%Y-%m-%d',
				'json' => $results['data'],
				'type' => 'line'
			],
			'axis' => [
				'x' => [
					'type' => 'timeseries',
					'tick' => [
						'rotate' => -90,
						'fit' => true,
					]
				],
				'y' => [
					'tick' => [
						'fit' => true,
					]
				]
			],
			'grid' => [
				'y' => [
					'show' => true,
				]
			],
			'subchart' => [
				'show' => false,
				'size' => [
					'height' => 50,
				]
			],
			'legend' => [
				'show' => true,
			],
			'point' => [
				'show' => true,
			]
		];
		
		$config_json['data']['xFormat']  = $xaxis_format;
		
		if($xaxis_format)
			$config_json['axis']['x']['tick']['format']  = $xaxis_format;
		
		$config_json['subchart']['show']  = (bool)($options['subchart'] ?? null);
		$config_json['legend']['show']  = (bool)($options['show_legend'] ?? null);
		$config_json['point']['show']  = (bool)($options['show_points'] ?? null);
		
		switch($chart_as) {
			case 'line':
				$config_json['data']['type']  = 'line';
				break;
				
			case 'spline':
				$config_json['data']['type']  = 'spline';
				break;
				
			case 'area':
				$config_json['data']['type']  = 'area-step';
				$config_json['data']['groups'] = [array_values(array_diff(array_keys($results['data']), [$xaxis_key]))];
				break;
				
			case 'bar':
				$config_json['data']['type'] = 'bar';
				$config_json['bar']['width'] = [
					'ratio' => 0.75,
				];
				break;
				
			case 'bar_stacked':
				$config_json['data']['type']  = 'bar';
				$config_json['bar']['width'] = [
					'ratio' => 0.75,
				];
				
				if(array_key_exists('groups', $results['_']) && $results['_']['groups']) {
					$config_json['data']['groups'] = $results['_']['groups'];
				} else {
					$config_json['data']['groups'] = [array_values(array_diff(array_keys($results['data']), [$xaxis_key]))];
				}
				break;
		}
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/timeseries/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/timeseries/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
