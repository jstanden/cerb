<?php
class WorkspaceWidget_ChartKata extends Extension_WorkspaceWidget {
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
		$kata = DevblocksPlatform::services()->kata();
		$active_worker = CerberusApplication::getActiveWorker();
		
		$datasets_kata = DevblocksPlatform::importGPC($widget->params['datasets_kata'] ?? '', 'string');
		$chart_kata = DevblocksPlatform::importGPC($widget->params['chart_kata'] ?? '', 'string');
		
		$valid_chart_types = [
			'area',
			'area-spline',
			'area-step',
			'bar',
			'donut',
			'gauge',
			'line',
			'pie',
			'scatter',
			'spline',
			'step',
		];

		try {
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
			
			$chart_kata = $kata->parse($chart_kata, $error);
			
			$datasets = $this->_loadDatasets($datasets_kata, $chart_dict, $error);
			
			$chart_dict->set('datasets', $datasets);
			
			$chart = $kata->formatTree($chart_kata, $chart_dict, $error);
			
			$chart_json = [
				'size' => [
					'height' => 500,
				],
				'data' => [
					'type' => 'line',
					'columns' => [],
					'names' => [],
					'types' => [],
					'axes' => [],
					'groups' => [],
				],
				'tooltip' => [
					'show' => true,
					'grouped' => true,
				]
			];
			
			if($chart['data']['type'] ?? null)
				$chart_json['data']['type'] = $chart['data']['type'];
			
			// Sanitize types
			if(!in_array($chart_json['data']['type'], $valid_chart_types)) {
				throw new Exception_DevblocksValidationError(sprintf('Chart type (%s) must be one of: %s',
					$chart_json['data']['type'],
					implode(', ', $valid_chart_types)
				));
			}
			
			if($chart['data']['names'] ?? null)
				$chart_json['data']['names'] = $chart['data']['names'];
			
			$chart_json['axis'] = $chart['axis'] ?? [];
			$chart_json['grid'] = $chart['grid'] ?? [];
			$chart_json['pie'] = $chart['pie'] ?? [];
			
			$x_labels = [];
			
			foreach($chart['data']['series'] ?? [] as $dataset_key => $dataset_params) {
				if(!array_key_exists($dataset_key, $datasets)) {
					throw new Exception_DevblocksValidationError(sprintf(
						"Unknown dataset `%s`",
						$dataset_key
					));
				}
				
				$xkey = $dataset_params['x_key'] ?? null;
				
				// Do we need to normalize timeseries tick values?
				if($xkey && 'timeseries' == ($chart['axis']['x']['type'] ?? null)) {
					if(!array_key_exists($xkey, $datasets[$dataset_key]))
						continue;
					
					$datasets[$dataset_key][$xkey] = array_map(
						function($xtick) {
							// Handle YYYY format
							if(is_numeric($xtick) && $xtick < 10000) {
								$dt = new DateTime();
								$dt->setDate($xtick, 1, 1);
								$dt->setTime(0, 0);
								
							} else {
								$dt = new DateTime($xtick);
							}
							return $dt->format('Y-m-d\TH:i:s');
						},
						$datasets[$dataset_key][$xkey]
					);
				}
				
				$x_labels = array_merge($x_labels, $datasets[$dataset_key][$xkey] ?? []);
			}
			
			if($x_labels) {
				$x_labels = array_unique($x_labels);
				
				// [TODO] Lerp
				if('timeseries' == ($chart['axis']['x']['type'] ?? null)) {
					sort($x_labels);
					
				} else if(
					'category' == ($chart['axis']['x']['type'] ?? null)
					&& array_key_exists('categories', $chart['axis']['x'])
					&& is_array($chart['axis']['x']['categories'])
				) {
					$order = array_flip(array_values($chart['axis']['x']['categories']));
					
					usort($x_labels, function($a, $b) use ($order) {
						return ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX);
					});
				}
				
				if('scatter' == ($chart['data']['type'] ?? null)) {
					DevblocksPlatform::noop();
				} else {
					$chart_json['data']['x'] = 'x';
					$chart_json['data']['columns'][] = ['x', ...$x_labels];
				}
			}
			
			if('timeseries' == ($chart['axis']['x']['type'] ?? null)) {
				$chart_json['data']['xFormat'] = '%Y-%m-%dT%H:%M:%S';
				$xtick_format = $chart['axis']['x']['tick']['format'] ?? '%Y-%m-%d %H:%M';
				$chart_json['axis']['x']['tick']['format'] = $xtick_format;
			}
			
			$x_labels = array_fill_keys($x_labels, 0);
			
			foreach($chart['data']['series'] ?? [] as $dataset_key => $dataset_params) {
				if(!array_key_exists($dataset_key, $datasets)) {
					throw new Exception_DevblocksValidationError(sprintf(
						"Unknown dataset `%s` in `data:series:`",
						$dataset_key,
					));
				}
				
				$dataset_name = $dataset_params['name'] ?? null;
				$xkey = $dataset_params['x_key'] ?? null;
				$ytype = $dataset_params['y_type'] ?? null;
				$yaxis = $dataset_params['y_axis'] ?? null;
				
				if($ytype && !in_array($ytype, $valid_chart_types))
					throw new Exception_DevblocksValidationError(sprintf('`data:series:%s:y_type:` (%s) must be one of: %s',
						$dataset_key,
						$ytype,
						implode(', ', $valid_chart_types)
					));
				
				$series_count = count(array_filter(array_keys($datasets[$dataset_key] ?? []), fn($k) => $k != $xkey));
				
				foreach($datasets[$dataset_key] as $key => $values) {
					$series = [];
					
					if($xkey && 'scatter' != ($chart['data']['type'] ?? null)) {
						// Skip the `x` series
						if($key == $xkey)
							continue;
						
						if(!array_key_exists($xkey, $datasets[$dataset_key]))
							throw new Exception_DevblocksValidationError(sprintf('`data:series:%s:x_key:` (%s) must be one of: %s',
								$dataset_key,
								$xkey,
							implode(', ', array_keys($datasets[$dataset_key]))
							));
						
						$series = array_values(array_merge($x_labels, array_combine($datasets[$dataset_key][$xkey], $values)));
					} else {
						if(is_array($values)) {
							$series = $values;
						} else if(is_scalar($values)) {
							$series = [$values];
						}
					}
					
					if(1 == $series_count) {
						$series_name = $dataset_name ?: $key;
					} else if ($dataset_name) {
						$series_name = sprintf("%s (%s)", $dataset_name, $key);
					} else {
						$series_name = $key;
					}
					
					$series_key = $dataset_key . '_' . $key;
					
					if(!array_key_exists($series_key, $chart_json['data']['names'] ?? []))
						$chart_json['data']['names'][$series_key] = $series_name;
					
					$chart_json['data']['columns'][] = [$series_key, ...$series];
					
					if($ytype)
						$chart_json['data']['types'][$series_key] = $ytype;
					
					if($yaxis == 'y2') {
						$chart_json['data']['axes'][$series_key] = $yaxis;
						$chart_json['axis']['y2']['show'] = true;
					}
				}
				
				if('scatter' == ($chart['data']['type'] ?? null)) {
					foreach(array_keys($datasets[$dataset_key]) as $k) {
						if(DevblocksPlatform::strEndsWith($k, '_x'))
							continue;
						
						// [TODO] Right now this forces the suffix
						$chart_json['data']['xs'][$dataset_key . '_' . $k] = $dataset_key . '_' . $k . '_x';
					}
					
				} else {
					unset($datasets[$dataset_key][$xkey]);
				}
			}
			
			foreach($chart['data']['stacks'] ?? [] as $dataset_keys) {
				if(!is_array($dataset_keys))
					continue;
				
				$group = [];
				
				foreach($dataset_keys as $dataset_key) {
					if(!array_key_exists($dataset_key, $datasets))
						continue;
					
					foreach(array_keys($datasets[$dataset_key]) as $k) {
						$group[] = $dataset_key . '_' . $k;
					}
				}
				
				$chart_json['data']['groups'][] = $group;
			}
			
			if(array_key_exists('tooltip', $chart)) {
				if(array_key_exists('show', $chart['tooltip']))
					$chart_json['tooltip']['show'] = boolval($chart['tooltip']['show']);
					
				if(array_key_exists('grouped', $chart['tooltip']))
					$chart_json['tooltip']['grouped'] = boolval($chart['tooltip']['grouped']);
			}
			
			$tpl->assign('chart_json', json_encode($chart_json));
			$tpl->assign('widget', $widget);
			$tpl->display('devblocks:cerberusweb.core::internal/workspaces/widgets/chart/kata/render.tpl');
			
		} catch (Exception_DevblocksValidationError $e) {
			echo sprintf("ERROR: %s",
				DevblocksPlatform::strEscapeHtml($e->getMessage())
			);
			
		} catch (Throwable $e) {
			echo "An unexpected configuration error occurred.";
			DevblocksPlatform::logError($e->getMessage());
		}
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
		
		$datasets_kata = $kata->parse($datasets_kata, $error);
		
		$datasets_kata = $kata->formatTree($datasets_kata, $chart_dict, $error);
		
		$datasets = [];
		
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
					
					if(!($automation = DAO_Automation::getByUri($uri))) {
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
					
					if(!($query_results = $data->executeQuery($data_params['query'], $data_params['query_params'] ?? [], $error)))
						return null;
					
					if(in_array($query_results['_']['format'], ['categories','scatterplot'])) {
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
						
					} else {
						$datasets[$dataset_name] = $query_results['data'];
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
};