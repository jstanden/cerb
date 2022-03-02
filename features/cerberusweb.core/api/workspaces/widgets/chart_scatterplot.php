<?php
class WorkspaceWidget_ChartScatterplot extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
	
		return false;
	}
	
	function getData(Model_WorkspaceWidget $widget, &$error=null) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
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
		
		if(false === ($results = $data->executeQuery($query, [], $error, $cache_secs)))
			return false;
		
		return $results;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$xaxis_format = DevblocksPlatform::importGPC($widget->params['xaxis_format'] ?? null, 'string', '');
		$xaxis_label = DevblocksPlatform::importGPC($widget->params['xaxis_label'] ?? null, 'string', '');
		$yaxis_format = DevblocksPlatform::importGPC($widget->params['yaxis_format'] ?? null, 'string', '');
		$yaxis_label = DevblocksPlatform::importGPC($widget->params['yaxis_label'] ?? null, 'string', '');
		$height = DevblocksPlatform::importGPC($widget->params['height'] ?? null, 'integer', 0);
		
		$tpl = DevblocksPlatform::services()->template();
		
		$error = null;
		
		if(false == ($results = $this->getData($widget, $error))) {
			echo DevblocksPlatform::strEscapeHtml($error);
			return;
		}
		
		if(empty($results)) {
			echo "(no data)";
			return;
		}
		
		$config_json = [
			'bindto' => sprintf("#widget%d", $widget->id),
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
		
		$config_json['size'] = ['height' => $height ?: 320];
		
		if($xaxis_label)
			$config_json['axis']['x']['label'] = $xaxis_label;
		
		if($yaxis_label)
			$config_json['axis']['y']['label'] = $yaxis_label;
		
		$tpl->assign('config_json', json_encode($config_json));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/scatterplot/render.tpl');
	}
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/scatterplot/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		return true;
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
		
		// Headings
		fputcsv($fp, [
			'Label',
			'X',
			'Y',
		]);
		
		foreach($data['data'] as $idx => $result) {
			$label = array_shift($result);
			$data['data'][$label] = $result;
			unset($data['data'][$idx]);
		}
		
		$points = [];
		
		foreach($data['data'] as $key => $result) {
			if(DevblocksPlatform::strEndsWith($key, '_x')) {
				$new_key = mb_substr($key,0,-2);
				
				foreach($result as $idx => $x) {
					$points[] = [
						$new_key,
						$x,
						$data['data'][$new_key][$idx]
					];
				}
			}
		}
		
		foreach($points as $label => $d) {
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