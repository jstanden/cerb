<?php
class WorkspaceWidget_ChartTimeSeries extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
		
		return false;
	}
	
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$data = DevblocksPlatform::services()->data();
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$data_query = DevblocksPlatform::importGPC($widget->params['data_query'] ?? null, 'string', null);
		$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'] ?? null, 'integer', 0);
		
		$dict = DevblocksDictionaryDelegate::instance([
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		]);
		
		$widget->_loadDashboardPrefsForWorker($active_worker, $dict);
		
		$query = $tpl_builder->build($data_query, $dict);
		
		if(!$query) {
			$error = "Invalid data query.";
			return false;
		}
		
		$bindings = $dict->getDictionary();
		
		if(false === ($results = $data->executeQuery($query, $bindings, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		$chart_as = DevblocksPlatform::importGPC($widget->params['chart_as'] ?? null, 'string', 'line');
		$options = DevblocksPlatform::importGPC($widget->params['options'] ?? null, 'array', []);
		$xaxis_label = DevblocksPlatform::importGPC($widget->params['xaxis_label'] ?? null, 'string', '');
		$yaxis_label = DevblocksPlatform::importGPC($widget->params['yaxis_label'] ?? null, 'string', '');
		$yaxis_format = DevblocksPlatform::importGPC($widget->params['yaxis_format'] ?? null, 'string', '');
		$height = DevblocksPlatform::importGPC($widget->params['height'] ?? null, 'integer', 0);
		
		$error = null;
		
		if(false === ($results = $this->getData($widget, $error))) {
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
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
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
		
		$config_json['subchart']['show']  = (bool)($options['subchart'] ?? false);
		$config_json['legend']['show']  = (bool)($options['show_legend'] ?? false);
		$config_json['point']['show']  = (bool)($options['show_points'] ?? false);
		
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
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/timeseries/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/timeseries/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		// [TODO] Validate prompted placeholders JSON?
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		return true;
	}
	
	// Prompted placeholders
	
	function getPlaceholderPrompts(Model_WorkspaceWidget $widget) {
		$json = DevblocksPlatform::importVar($widget->params['data_query_inputs'], 'string', '');
		
		if(!$json)
			return [];
		
		if(false == ($prompts = json_decode($json, true)))
			return [];
		
		return $prompts;
	}
	
	// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		// Headings
		fputcsv($fp, [
			'Date',
			'Label',
			'Value',
		]);
		
		if(!isset($data['data']))
			return;
		
		if(!isset($data['data']['ts']))
			return;
		
		$x_dates = $data['data']['ts'];
		unset($data['data']['ts']);
		
		foreach($x_dates as $x_idx => $x_date) {
			foreach($data['data'] as $series_label => $series_data) {
				$row = [
					$x_date,
					$series_label,
					$series_data[$x_idx],
				];
				fputcsv($fp, $row);
			}
		}
		
		rewind($fp);
		
		$output = "";
		
		while(!feof($fp)) {
			$output .= fgets($fp);
		}
		
		fclose($fp);
		
		return $output;
	}
	
	private function _exportDataAsJson(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$results = array(
			'widget' => array(
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};