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
		
		// Each series is a pair of columns: y-values in "<name>" and x-values in "<name>_x".
		$by_id = [];
		foreach(($results['data'] ?? []) as $col) {
			$col = array_values($col);
			$id = array_shift($col);
			$by_id[$id] = $col;
		}

		$series = [];
		foreach($by_id as $id => $vals) {
			if(DevblocksPlatform::strEndsWith($id, '_x'))
				continue;
			$series[] = [
				'key' => (string)$id,
				'x' => array_map('floatval', $by_id[$id . '_x'] ?? []),
				'values' => array_map('floatval', $vals),
			];
		}

		$tpl->assign('el_id', 'widget' . $widget->id);
		$tpl->assign('series', json_encode($series));
		$tpl->assign('xaxis_format', $xaxis_format);
		$tpl->assign('yaxis_format', $yaxis_format);
		$tpl->assign('xaxis_label', $xaxis_label);
		$tpl->assign('yaxis_label', $yaxis_label);
		$tpl->assign('height', $height ?: 320);
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
		], escape:'');
		
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
			fputcsv($fp, $d, escape:'');
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