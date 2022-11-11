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
		
		if(!($datasets_kata = $this->_loadDatasets($datasets_kata, $chart_dict, $error)))
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
		$active_worker = CerberusApplication::getActiveWorker();
		
		$params = DevblocksPlatform::importGPC($_POST['params'] ?? [], 'array', []);
		
		$datasets_kata = $params['datasets_kata'] ?? '';
		$placeholders_kata = $params['placeholder_simulator_kata'] ?? '';
		
		header('Content-Type: application/json; charset=utf-8');
		
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
		
		if(!($datasets = $this->_loadDatasets($datasets_kata, $chart_dict, $error))) {
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
	
	private function _loadDatasets($datasets_kata, $chart_dict, &$error=null) {
		$kata = DevblocksPlatform::services()->kata();
		
		if(false === ($datasets_kata = $kata->parse($datasets_kata, $error)))
			return null;
		
		if(false === ($datasets_kata = $kata->formatTree($datasets_kata, $chart_dict, $error)))
			return null;
		
		$datasets = [];
		
		$allowed_data_query_formats = [
			'categories',
			'pie',
			'scatterplot',
			'timeseries',
		];
		
		foreach($datasets_kata ?? [] as $key => $data_params) {
			list($dataset_type, $dataset_name) = array_pad(explode('/', $key, 2), 2, null);
			
			switch($dataset_type) {
				case 'automation':
					$automator = DevblocksPlatform::services()->automation();
					
					$uri = $data_params['uri'] ?? null;
					$inputs = $data_params['inputs'] ?? [];
					
					$initial_state = [
						'inputs' => $inputs,
					];
					
					if(!$uri || !is_scalar($uri)) {
						$error = sprintf("`uri:` is required on automation dataset `%s:`", $key);
						return null;
					}
					
					if(!($automation = DAO_Automation::getByUri($uri, AutomationTrigger_UiChartData::ID))) {
						$error = sprintf("Failed to load automation `%s`", $uri);
						return null;
					}
					
					if(!($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
						$error = sprintf("Failed to invoke automation `%s`", $uri);
						return null;
					}
					
					$datasets[$dataset_name] = [];
					
					foreach($automation_results->getKeyPath('__return.data', []) as $series_key => $series_data) {
						$datasets[$dataset_name][$series_key] = $series_data;
					}
					
					break;
				
				case 'dataQuery':
					$data = DevblocksPlatform::services()->data();
					
					if(!array_key_exists('query', $data_params)) {
						$error = 'A dataset `dataQuery:query:` is required.';
						return null;
					}
					
					if(!is_string($data_params['query'])) {
						$error = 'A dataset `dataQuery:query:` must be text.';
						return null;
					}
					
					if(!($query_results = $data->executeQuery($data_params['query'], $data_params['query_params'] ?? [], $error, intval($data_params['cache_secs'] ?? 0))))
						return null;
					
					$query_format = DevblocksPlatform::strLower($query_results['_']['format'] ?? '');
					
					if(!in_array($query_format, $allowed_data_query_formats)) {
						$error = sprintf('A dataset `dataQuery:query:format:` (%s) must be one of: %s',
							$query_format,
							implode(', ', $allowed_data_query_formats)
						);
						return null;
					}
					
					switch($query_format) {
						case 'categories':
						case 'pie':
						case 'scatterplot':
							$datasets[$dataset_name] = array_combine(
								array_map(
									fn($arr) => current($arr),
									$query_results['data']
								),
								array_map(
									fn($arr) => array_slice($arr, 1),
									$query_results['data']
								)
							);
							break;
						
						case 'timeseries':
							$datasets[$dataset_name] = $query_results['data'];
							break;
					}
					
					$key_map = DevblocksPlatform::importVar($data_params['key_map'] ?? [], 'array', []);
					
					if(DevblocksPlatform::arrayIsIndexed($key_map) && 0 == count($key_map) % 2) {
						$key_map = array_combine(
							array_filter($key_map, fn($v,$k) => (0 == $k % 2), ARRAY_FILTER_USE_BOTH),
							array_filter($key_map, fn($v,$k) => (0 != $k % 2), ARRAY_FILTER_USE_BOTH),
						);
					}
					
					if($key_map) {
						$datasets[$dataset_name] = array_combine(
							array_map(fn($k) => $key_map[$k] ?? $k, array_keys($datasets[$dataset_name])),
							array_values($datasets[$dataset_name])
						);
					}
					
					if('categories' == $query_format) {
						if($results = $this->_dataQueryQueriesFromCategories($query_results, $datasets[$dataset_name], $key_map)) {
							foreach($results as $k => $v)
								$datasets[$dataset_name][$k] = $v;
						}
					} else if('pie' == $query_format) {
						if($results = $this->_dataQueryQueriesFromPie($query_results, $datasets[$dataset_name], $key_map)) {
							foreach($results as $k => $v)
								$datasets[$dataset_name][$k] = $v;
						}
					} else if('timeseries' == $query_format) {
						if($results = $this->_dataQueryQueriesFromTimeseries($query_results, $datasets[$dataset_name], $key_map)) {
							foreach($results as $k => $v)
								$datasets[$dataset_name][$k] = $v;
						}
					}
					break;
				
				case 'manual':
					$datasets[$dataset_name] = [];
					
					foreach($data_params['data'] ?? [] as $data_param_key => $data_param) {
						$datasets[$dataset_name][$data_param_key] = $data_param;
					}
					break;
				
				default:
					break;
			}
		}
		
		return $datasets;
	}
	
	private function _dataQueryQueriesFromCategories(array $query_results, array $series=[], array $key_map=[]) : array {
		$results = [];
		
		$x_labels = [];
		
		$is_stacked = $query_results['_']['stacked'] ?? false;
		
		// If we specified an `x_key:`
		if($query_results['_']['format_params']['xaxis_key'] ?? null)
			$x_labels = array_flip(array_slice($query_results['data'][0], 1));
		
		if($is_stacked) {
			foreach($query_results['_']['series'] ?? [] as $x_label => $y_series) {
				foreach($y_series as $y_series_k => $y_series_v) {
					if($key_map && array_key_exists($y_series_k, $key_map))
						$y_series_k = $key_map[$y_series_k];
					
					if(!DevblocksPlatform::strStartsWith($y_series_k, '_')) {
						if(array_key_exists($x_label, $x_labels)) {
							$results[$y_series_k . '__click'] = array_fill_keys($x_labels, '');
						}
					}
				}
			}
			
		} else { // Not stacked
			foreach($query_results['_']['series'] ?? [] as $x_label => $y_series) {
				if($key_map && array_key_exists($x_label, $key_map))
					$x_label = $key_map[$x_label];
				
				$results[$x_label . '__click'] = array_fill_keys($x_labels, '');
			}
		}
		
		foreach($query_results['_']['series'] ?? [] as $x_label => $y_series) {
			foreach($y_series as $y_series_k => $y_series_v) {
				
				if($is_stacked) {
					if($key_map && array_key_exists($y_series_k, $key_map))
						$y_series_k = $key_map[$y_series_k];
				} else {
					if($key_map && array_key_exists($x_label, $key_map))
						$x_label = $key_map[$x_label];
				}
				
				if (!DevblocksPlatform::strStartsWith($y_series_k, '_')) {
					if (
						array_key_exists($is_stacked ? $x_label : $y_series_k, $x_labels)
						&& array_key_exists('query', $y_series_v)
					) {
						$results[($is_stacked ? $y_series_k : $x_label) . '__click'][$x_labels[$is_stacked ? $x_label : $y_series_k]] = $query_results['_']['context'] . ' ' . $y_series_v['query'];
					}
				}
			}
		}
		
		return $results;
	}
	
	private function _dataQueryQueriesFromPie(array $query_results, array $series=[], array $key_map=[]) : array {
		$results = [];
		
		if(
			!array_key_exists('series', $query_results['_'])
			|| !array_key_exists('context', $query_results['_'])
		)
			return [];
		
		foreach(array_keys($series) as $series_key) {
			if($key_map && array_key_exists($series_key, $key_map))
				$series_key = $key_map[$series_key];
			
			if(
				array_key_exists($series_key, $query_results['_']['series'])
				&& array_key_exists('query', $query_results['_']['series'][$series_key])
			) {
				$results[$series_key . '__click'] = $query_results['_']['context'] . ' ' . $query_results['_']['series'][$series_key]['query'];
			}
		}
		
		return $results;
	}
	
	private function _dataQueryQueriesFromTimeseries(array $query_results, array $series=[], array $key_map=[]) : array {
		$results = [];

		$x_labels = [];
		
		if($query_results['_']['format_params']['xaxis_key'] ?? null) {
			$xaxis_key = array_key_first($query_results['data']);
			$x_labels = array_flip($query_results['data'][$xaxis_key]);
		}

		$series_keys = array_keys(array_slice($query_results['data'], 1, null, true));
		
		foreach($series_keys as $series_key) {
			if($key_map && array_key_exists($series_key, $key_map))
				$series_key = $key_map[$series_key];
			
			$results[$series_key . '__click'] = array_fill_keys($x_labels, '');
		}
		
		foreach($query_results['_']['series'] ?? [] as $x_label => $y_series) {
			if($key_map && array_key_exists($x_label, $key_map))
				$x_label = $key_map[$x_label];
			
			foreach($y_series as $y_series_k => $y_series_v) {
				if($key_map && array_key_exists($y_series_k, $key_map))
					$y_series_k = $key_map[$y_series_k];
				
				if(array_key_exists('query', $y_series_v)) {
					if(array_key_exists($y_series_k, $x_labels)) {
						$results[$x_label . '__click'][$x_labels[$y_series_k]] = $query_results['_']['context'] . ' ' . $y_series_v['query'];
					}
				}
			}
		}
		
		return $results;
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