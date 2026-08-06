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
		$xaxis_key = $results['_']['format_params']['xaxis_key'] ?? null;
		$xaxis_format = $results['_']['format_params']['xaxis_format'] ?? null;
		
		// Convert PHP format to Javascript
		if($xaxis_format == '%Y-%m-%d %H:%i')
			$xaxis_format = '%Y-%m-%d %H:%M';
		
		// First key 'ts' = the timestamp column; each remaining key is a numeric series aligned to it.
		$data = $results['data'] ?? [];
		$ts = $data['ts'] ?? [];

		$series = [];
		foreach($data as $key => $values) {
			if($key === 'ts')
				continue;
			$series[] = ['key' => (string)$key, 'values' => array_map('floatval', $values)];
		}

		if(false != ($chart_meta = @$results['_']))
			$tpl->assign('chart_meta_json', json_encode($chart_meta));

		$tpl->assign('el_id', 'widget' . $model->id);
		$tpl->assign('ts', json_encode($ts));
		$tpl->assign('series', json_encode($series));
		$tpl->assign('groups', json_encode($results['_']['groups'] ?? null));
		$tpl->assign('chart_as', $chart_as);
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('xaxis_label', $xaxis_label);
		$tpl->assign('yaxis_label', $yaxis_label);
		$tpl->assign('show_legend', (bool)($options['show_legend'] ?? false));
		$tpl->assign('show_points', (bool)($options['show_points'] ?? false));
		$tpl->assign('height', $height ?: 320);
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
