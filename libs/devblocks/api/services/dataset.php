<?php
class _DevblocksDatasetService {
	private static ?_DevblocksDatasetService $_instance = null;

	static function getInstance(): _DevblocksDatasetService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksDatasetService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function parse(mixed $datasets_kata, DevblocksDictionaryDelegate $chart_dict=null, &$error=null) : array | false {
		try {
			$kata = DevblocksPlatform::services()->kata();
			
			if(is_string($datasets_kata)) {
				if(false === ($datasets_kata = $kata->parse($datasets_kata, $error)))
					throw new Exception_DevblocksValidationError($error);
				
				if(false === ($datasets_kata = $kata->formatTree($datasets_kata, $chart_dict, $error)))
					throw new Exception_DevblocksValidationError($error);
				
			} elseif(!is_array($datasets_kata)) {
				$error = 'Datasets must be a string or array';
				throw new Exception_DevblocksValidationError($error);
			}
			
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
							throw new Exception_DevblocksValidationError($error);
						}
						
						if(!($automation = DAO_Automation::getByUri($uri, AutomationTrigger_UiChartData::ID))) {
							$error = sprintf("Failed to load automation `%s`", $uri);
							throw new Exception_DevblocksValidationError($error);
						}
						
						if(!($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
							$error = sprintf("Failed to invoke automation `%s`", $uri);
							throw new Exception_DevblocksValidationError($error);
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
							throw new Exception_DevblocksValidationError($error);
						}
						
						if(!is_string($data_params['query'])) {
							$error = 'A dataset `dataQuery:query:` must be text.';
							throw new Exception_DevblocksValidationError($error);
						}
						
						if(!($query_results = $data->executeQuery($data_params['query'], $data_params['query_params'] ?? [], $error, intval($data_params['cache_secs'] ?? 0))))
							throw new Exception_DevblocksValidationError($error);
						
						$query_format = DevblocksPlatform::strLower($query_results['_']['format'] ?? '');
						
						if(!in_array($query_format, $allowed_data_query_formats)) {
							$error = sprintf('A dataset `dataQuery:query:format:` (%s) must be one of: %s',
								$query_format,
								implode(', ', $allowed_data_query_formats)
							);
							throw new Exception_DevblocksValidationError($error);
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
			
		} catch (Exception_DevblocksValidationError $e) {
			$error = sprintf("ERROR: %s",
				$e->getMessage()
			);
			return false;
			
		} catch (Throwable $e) {
			DevblocksPlatform::logError($e->getMessage());
			$error = "An unexpected configuration error occurred.";
			return false;
		}
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
}