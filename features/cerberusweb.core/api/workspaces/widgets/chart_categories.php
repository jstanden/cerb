<?php
class WorkspaceWidget_ChartCategories extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		$active_worker = CerberusApplication::getActiveWorker();
		
		@$data_query = DevblocksPlatform::importGPC($widget->params['data_query'], 'string', null);
		@$cache_secs = DevblocksPlatform::importGPC($widget->params['cache_secs'], 'integer', 0);
		
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
		
		if(false === ($results = $data->executeQuery($query, $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$xaxis_format = DevblocksPlatform::importGPC($widget->params['xaxis_format'], 'string', '');
		@$yaxis_format = DevblocksPlatform::importGPC($widget->params['yaxis_format'], 'string', '');
		@$height = DevblocksPlatform::importGPC($widget->params['height'], 'integer', 0);
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		@$xaxis_key = $results['_']['format_params']['xaxis_key'] ?: '';
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
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
						'rotate' => -90,
						'format' => null
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
			
		} else {
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
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/categories/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/categories/config.tpl');
	}
	
	function saveConfig(Model_WorkspaceWidget $widget) {
		@$params = DevblocksPlatform::importGPC($_POST['params'], 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
	}
	
		// Export
	
	function exportData(Model_WorkspaceWidget $widget, $format=null) {
		switch(DevblocksPlatform::strLower($format)) {
			case 'csv':
				return $this->_exportDataAsCsv($widget);
				break;
				
			default:
			case 'json':
				return $this->_exportDataAsJson($widget);
				break;
		}
		
		return false;
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		$error = null;
		
		if(false == ($data = $this->getData($widget, $error)))
			return;
		
		$fp = fopen("php://temp", 'r+');
		
		foreach($data['data'] as $d) {
			fputcsv($fp, $d);
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
				'type' => 'chart_pie',
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $data,
			),
		);
		
		return DevblocksPlatform::strFormatJson($results);
	}
};