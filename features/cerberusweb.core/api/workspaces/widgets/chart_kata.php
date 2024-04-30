<?php
class WorkspaceWidget_ChartKata extends Extension_WorkspaceWidget implements ICerbWorkspaceWidget_ExportData {
	const ID = 'cerb.workspace.widget.chart.kata';
	
	function renderConfig(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/kata/config.tpl');
	}
	
	function invokeConfig($action, Model_WorkspaceWidget $model) {
		switch($action) {
			case 'previewChart':
				return $this->_widgetConfig_previewChart($model);
			case 'previewDataset':
				return $this->_widgetConfig_previewDataset($model);
		}
		return false;
	}
	
	function saveConfig(Model_WorkspaceWidget $widget, ?string &$error=null) : bool {
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? null, 'array', []);
		
		DAO_WorkspaceWidget::update($widget->id, array(
			DAO_WorkspaceWidget::PARAMS_JSON => json_encode($params),
		));
		
		DAO_RecordChangeset::create(
			'workspace_widget',
			$widget->id,
			[
				'datasets_kata' => $params['datasets_kata'] ?? '',
				'chart_kata' => $params['chart_kata'] ?? '',
			],
			$active_worker->id ?? 0
		);
		
		return true;
	}
	
	public function invoke(string $action, Model_WorkspaceWidget $model) {
		$active_worker = CerberusApplication::getActiveWorker();
		
		if(!Context_WorkspaceWidget::isReadableByActor($model, $active_worker))
			DevblocksPlatform::dieWithHttpError(null, 403);
	
		return false;
	}
	
	function render(Model_WorkspaceWidget $widget) {
		$tpl = DevblocksPlatform::services()->template();
		
		try {
			$chart_json = $this->_getChartJsonFromWidget($widget);
			
		} catch (Exception_DevblocksValidationError $e) {
				echo DevblocksPlatform::strEscapeHtml($e->getMessage());
				return;
				
		} catch (Throwable $e) {
				echo DevblocksPlatform::strEscapeHtml('An unexpected error occurred.');
				DevblocksPlatform::logException($e);
				return;
		}
		
		$tpl->assign('chart_json', json_encode($chart_json));
		
		$tpl->assign('widget', $widget);
		$tpl->display('devblocks:cerberusweb.core::internal/chart_kata/render.tpl');
	}
	
	/**
	 * @param Model_WorkspaceWidget $widget
	 * @return array
	 * @throws Exception_DevblocksValidationError
	 */
	private function _getChartJsonFromWidget(Model_WorkspaceWidget $widget) : array {
		$chart = DevblocksPlatform::services()->chart();
		$dataset = DevblocksPlatform::services()->dataset();
		$kata = DevblocksPlatform::services()->kata();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$chart_kata = DevblocksPlatform::importGPC($widget->params['chart_kata'] ?? '', 'string');
		$datasets_kata = DevblocksPlatform::importGPC($widget->params['datasets_kata'] ?? '', 'string');
		$error = null;
		
		$initial_state = [
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $widget->id,
		];
		
		$chart_dict = DevblocksDictionaryDelegate::instance($initial_state);
		
		// Dashboard prefs
		$widget->_loadDashboardPrefsForWorker($active_worker, $chart_dict);
		
		if(!($chart_kata = $kata->parse($chart_kata, $error)))
			throw new Exception_DevblocksValidationError($error);
		
		if(!($chart_kata = $kata->formatTree($chart_kata, $chart_dict, $error)))
			throw new Exception_DevblocksValidationError($error);
		
		if(!($datasets_kata = $dataset->parse($datasets_kata, $chart_dict, $error)))
			throw new Exception_DevblocksValidationError($error);
		
		$chart_options = [
			'dark_mode' => DAO_WorkerPref::get($active_worker->id,'dark_mode',0),
		];
		
		if(!$chart_json = $chart->parse($chart_kata, $datasets_kata, $chart_options, $error))
			throw new Exception_DevblocksValidationError($error);
		
		return $chart_json;
	}
	
	private function _widgetConfig_previewDataset(Model_WorkspaceWidget $model) {
		$kata = DevblocksPlatform::services()->kata();
		$dataset = DevblocksPlatform::services()->dataset();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		$datasets_kata = $params['datasets_kata'] ?? '';
		$placeholders_kata = $params['placeholder_simulator_kata'] ?? '';
		
		DevblocksPlatform::services()->http()->setHeader('Content-Type', 'application/json; charset=utf-8');
		
		$initial_state = [
			'current_worker__context' => CerberusContexts::CONTEXT_WORKER,
			'current_worker_id' => $active_worker->id,
			'widget__context' => CerberusContexts::CONTEXT_WORKSPACE_WIDGET,
			'widget_id' => $model->id,
		];
		
		if(false === ($placeholders = $kata->parse($placeholders_kata, $error)))
			return;
		
		if(false === ($placeholders = $kata->formatTree($placeholders, DevblocksDictionaryDelegate::instance([]), $error)))
			return;
		
		$initial_state = array_merge($initial_state, $placeholders);
		
		$chart_dict = DevblocksDictionaryDelegate::instance($initial_state);
		
		$error = null;
		
		if(!($datasets = $dataset->parse($datasets_kata, $chart_dict, $error))) {
			echo DevblocksPlatform::strFormatJson([
				'error' => 'ERROR: ' . $error,
			]);
			
		} else {
			// We don't need to show click series meta in the results
			foreach($datasets as $dataset_key => $dataset_series) {
				$datasets[$dataset_key] = array_filter($dataset_series, function ($k) {
					return !DevblocksPlatform::strEndsWith($k, '__click');
				}, ARRAY_FILTER_USE_KEY);
			}
			
			echo DevblocksPlatform::strFormatJson($datasets);
		}
	}
	
	private function _widgetConfig_previewChart(Model_WorkspaceWidget $model) {
		$kata = DevblocksPlatform::services()->kata();
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		$datasets_kata = $params['datasets_kata'] ?? '';
		$placeholders_kata = $params['placeholder_simulator_kata'] ?? '';
		$chart_kata = $params['chart_kata'] ?? '';
		
		if($placeholders_kata) {
			if(false === ($placeholders = $kata->parse($placeholders_kata, $error)))
				return;
			
			if(false === ($placeholders = $kata->formatTree($placeholders, DevblocksDictionaryDelegate::instance([]), $error)))
				return;
			
			if(false === ($datasets = $kata->parse($datasets_kata, $error)))
				return;
			
			if(false === ($datasets = $kata->formatTree($datasets, DevblocksDictionaryDelegate::instance($placeholders), $error)))
				return;
			
			$datasets_kata = $kata->emit($datasets);
		}
		
		$model->params['datasets_kata'] = $datasets_kata;
		$model->params['chart_kata'] = $chart_kata;
		
		$this->render($model);
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
	}
	
	private function _exportDataAsCsv(Model_WorkspaceWidget $widget) {
		try {
			$chart_json = $this->_getChartJsonFromWidget($widget);	
		} catch(Exception_DevblocksValidationError $e) {
			return null;
		}
		
		$fp = fopen("php://temp", 'r+');
		
		foreach(($chart_json['data']['columns'] ?? []) as $data) {
			fputcsv($fp, $data);
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
		try {
			$chart_json = $this->_getChartJsonFromWidget($widget);
		} catch(Exception_DevblocksValidationError $e) {
			return null;
		}
		
		$results = [
			'widget' => [
				'label' => $widget->label,
				'type' => $widget->extension_id,
				'version' => 'Cerb ' . APP_VERSION,
				'results' => $chart_json['data']['columns'] ?? [],
			],
		];
		
		return DevblocksPlatform::strFormatJson($results);
	}
};