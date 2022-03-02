<?php
class ProfileWidget_ChartScatterplot extends Extension_ProfileWidget {
	const ID = 'cerb.profile.tab.widget.chart.scatterplot';

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
		$xaxis_label = DevblocksPlatform::importGPC($model->extension_params['xaxis_label'] ?? null, 'string', '');
		$xaxis_format = DevblocksPlatform::importGPC($model->extension_params['xaxis_format'] ?? null, 'string', '');
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
		
		if(false === ($results = $data->executeQuery($query, [], $error))) {
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
				'xs' => [],
				'columns' => $results['data'],
				'type' => 'scatter',
			],
			'axis' => [
				'x' => [
					'tick' => [
						'format' => null,
						'fit' => false,
						'rotate' => -90,
					]
				],
				'y' => [
					'tick' => [
						'fit' => false,
						'format' => null,
					]
				]
			],
		];
		
		foreach($results['data'] as $result) {
			if(@DevblocksPlatform::strEndsWith($result[0], '_x'))
				$config_json['data']['xs'][mb_substr($result[0],0,-2)] = $result[0];
		}
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/scatterplot/render.tpl');
	}
	
	function renderConfig(Model_ProfileWidget $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $model);
		$tpl->display('devblocks:cerberusweb.core::internal/profiles/widgets/chart/scatterplot/config.tpl');
	}
	
	function invokeConfig($action, Model_ProfileWidget $model) {
		return false;
	}
}
