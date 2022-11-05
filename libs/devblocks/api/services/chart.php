<?php
class _DevblocksChartService {
	private static ?_DevblocksChartService $_instance = null;

	private array $valid_chart_types = [
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
	
	private array $color_pattern_scheme10 = [
		"#1f77b4",
		"#ff7f0e",
		"#2ca02c",
		"#d62728",
		"#9467bd",
		"#8c564b",
		"#e377c2",
		"#7f7f7f",
		"#bcbd22",
		"#17becf"
	];
	
	static function getInstance(): _DevblocksChartService {
		if (is_null(self::$_instance))
			self::$_instance = new _DevblocksChartService();
		
		return self::$_instance;
	}
	
	private function __construct() {}
	
	function parse(array $chart_kata, array $datasets_kata, array $chart_options=[], &$error=null) {
		$is_dark_mode = $chart_options['dark_mode'] ?? 0;
		
		try {
			$error = null;
			
			// Dark and light defaults
			
			if($is_dark_mode) {
				$default_color_pattern =
					$chart_kata['color']['patterns']['default_dark']
					?? $chart_kata['color']['patterns']['default']
					?? $this->color_pattern_scheme10
				;
				
			} else {
				$default_color_pattern = $chart_kata['color']['patterns']['default']
					?? $this->color_pattern_scheme10
				;
			}
			
			$chart_json = [
				'size' => [
					'height' => 500,
				],
				'data' => [
					'type' => 'line',
					'columns' => [],
					'names' => [],
					'colors' => [],
					'types' => [],
					'axes' => [],
					'groups' => [],
				],
				'axis' => [
					'x' => [
						'type' => 'linear',
					],
					'y' => [
						'type' => 'linear',
					],
					'y2' => [
						'type' => 'linear',
					]
				],
				'grid' => [
					'x' => [],
					'y' => [],
				],
				'color' => [
					'pattern' => $default_color_pattern,
				],
				'legend' => [
					'show' => true,
				],
				'tooltip' => [
					'show' => true,
					'grouped' => true,
				]
			];
			
			$this->_parseChartData($chart_kata, $chart_json);
			
			$this->_parseChartAxis('x', $chart_kata, $chart_json);
			$this->_parseChartAxis('y', $chart_kata, $chart_json);
			$this->_parseChartAxis('y2', $chart_kata, $chart_json);
			
			$this->_parseChartGrid('x', $chart_kata, $chart_json);
			$this->_parseChartGrid('y', $chart_kata, $chart_json);
			
			$this->_parseChartDataSeries($chart_kata, $datasets_kata, $chart_options, $chart_json);
			
			$this->_parseChartLegend($chart_kata, $chart_json);
			
			$this->_parseChartTooltip($chart_kata, $chart_json);
			
			return $chart_json;
			
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
	
	/**
	 * @param array $chart_kata
	 * @param array $chart_json
	 * @return void
	 * @throws Exception_DevblocksValidationError
	 */
	private function _parseChartData(array $chart_kata, array &$chart_json) : void {
		if(!($chart_kata['data']['type'] ?? false))
			$chart_kata['data']['type'] = 'line';
		
		// Sanitize type
		if(!in_array($chart_kata['data']['type'], $this->valid_chart_types)) {
			throw new Exception_DevblocksValidationError(sprintf('Chart type (%s) must be one of: %s',
				$chart_kata['data']['type'],
				implode(', ', $this->valid_chart_types)
			));
		}
		
		if($chart_kata['data']['type'] ?? null)
			$chart_json['data']['type'] = $chart_kata['data']['type'];
		
		if($chart_kata['data']['names'] ?? null)
			$chart_json['data']['names'] = $chart_kata['data']['names'];
	}
	
	/**
	 * @param array $chart_kata
	 * @param array $datasets_kata
	 * @param array $chart_options
	 * @param array $chart_json
	 * @return void
	 * @throws Exception_DevblocksValidationError
	 */
	private function _parseChartDataSeries(array $chart_kata, array $datasets_kata, array $chart_options, array &$chart_json) : void {
		$validation = DevblocksPlatform::services()->validation();
		
		$is_dark_mode = $chart_options['dark_mode'] ?? 0;
		$x_labels = [];
		$error = null;
		
		foreach($chart_kata['data']['series'] ?? [] as $dataset_key => $dataset_params) {
			if(!array_key_exists($dataset_key, $datasets_kata)) {
				throw new Exception_DevblocksValidationError(sprintf(
					"Unknown dataset `%s`",
					$dataset_key
				));
			}
			
			$x_key = $dataset_params['x_key'] ?? null;
			
			// Do we need to normalize timeseries tick values?
			if($x_key && 'timeseries' == ($chart_kata['axis']['x']['type'] ?? null)) {
				if(!array_key_exists($x_key, $datasets_kata[$dataset_key]))
					continue;
				
				$datasets_kata[$dataset_key][$x_key] = array_map(
					function($xtick) {
						// Handle YYYY format
						if(is_numeric($xtick) && $xtick < 10000) {
							$dt = new DateTime();
							$dt->setDate($xtick, 1, 1);
							$dt->setTime(0, 0);
							
						} else {
							try {
								$dt = new DateTime($xtick);
							} catch (Throwable) {
								throw new Exception_DevblocksValidationError(sprintf("Unknown x-axis timeseries `%s`", $xtick));
							}
						}
						return $dt->format('Y-m-d\TH:i:s');
					},
					$datasets_kata[$dataset_key][$x_key]
				);
			}
			
			$x_labels = array_replace($x_labels, $datasets_kata[$dataset_key][$x_key] ?? []);
		}
		
		if($x_labels) {
			$x_labels = array_unique($x_labels);
			
			if('timeseries' == ($chart_kata['axis']['x']['type'] ?? null)) {
				sort($x_labels);
				
			} else if(
				'category' == ($chart_kata['axis']['x']['type'] ?? null)
				&& array_key_exists('categories', $chart_kata['axis']['x'])
				&& is_array($chart_kata['axis']['x']['categories'])
			) {
				$order = array_flip(array_values($chart_kata['axis']['x']['categories']));
				
				usort($x_labels, function($a, $b) use ($order) {
					return ($order[$a] ?? PHP_INT_MAX) <=> ($order[$b] ?? PHP_INT_MAX);
				});
			}
			
			if('scatter' == ($chart_kata['data']['type'] ?? null)) {
				DevblocksPlatform::noop();
			} else {
				$chart_json['data']['x'] = 'x';
				$chart_json['data']['columns'][] = ['x', ...$x_labels];
			}
		}
		
		if('timeseries' == ($chart_kata['axis']['x']['type'] ?? null)) {
			$chart_json['data']['xFormat'] = '%Y-%m-%dT%H:%M:%S';
			$xtick_format = $chart_kata['axis']['x']['tick']['format'] ?? '%Y-%m-%d %H:%M';
			$chart_json['axis']['x']['tick']['format'] = $xtick_format;
		}
		
		$x_labels = array_fill_keys($x_labels, 0);
		
		$color_groups = [];
		
		foreach($chart_kata['data']['series'] ?? [] as $dataset_key => $dataset_params) {
			if(!array_key_exists($dataset_key, $datasets_kata)) {
				throw new Exception_DevblocksValidationError(sprintf(
					"Unknown dataset `%s` in `data:series:`",
					$dataset_key,
				));
			}
			
			$dataset_name = $dataset_params['name'] ?? null;
			$x_key = $dataset_params['x_key'] ?? null;
			$ytype = $dataset_params['y_type'] ?? null;
			$yaxis = $dataset_params['y_axis'] ?? null;
			
			if($ytype && !in_array($ytype, $this->valid_chart_types))
				throw new Exception_DevblocksValidationError(sprintf('`data:series:%s:y_type:` (%s) must be one of: %s',
					$dataset_key,
					$ytype,
					implode(', ', $this->valid_chart_types)
				));
			
			$series_count = count(array_filter(array_keys($datasets_kata[$dataset_key] ?? []), fn($k) => $k != $x_key));
			
			$color_group = $chart_kata['data']['series'][$dataset_key]['color_pattern'] ?? null;
			$color_pattern = null;
			
			// Try dark mode first
			if($is_dark_mode) {
				if($color_pattern = $chart_kata['color']['patterns'][$color_group . '_dark'] ?? null)
					$color_group .= '_dark';
			}
			
			// Or use the color pattern name
			if(!$color_pattern)
				$color_pattern = $chart_kata['color']['patterns'][$color_group] ?? null;
			
			if($color_group && !$color_pattern)
				throw new Exception_DevblocksValidationError(sprintf("Unknown `color:patterns:%s:`", $color_group));
			
			if($color_pattern && !$validation->validators()->colorsHex()($color_pattern, $error))
				throw new Exception_DevblocksValidationError(sprintf("`color:patterns:%s:` %s", $color_group, $error));
			
			if($color_group && !array_key_exists($color_group, $color_groups))
				$color_groups[$color_group] = [];
			
			foreach($datasets_kata[$dataset_key] as $key => $values) {
				$series = [];
				
				if($x_key && 'scatter' != ($chart_kata['data']['type'] ?? null)) {
					// Skip the `x` series
					if($key == $x_key)
						continue;
					
					if(!array_key_exists($x_key, $datasets_kata[$dataset_key]))
						throw new Exception_DevblocksValidationError(sprintf('`data:series:%s:x_key:` (%s) must be one of: %s',
							$dataset_key,
							$x_key,
							implode(', ', array_keys($datasets_kata[$dataset_key]))
						));
					
					if(count($datasets_kata[$dataset_key][$x_key]) == count($values)) {
						$series = array_values(array_replace($x_labels, array_combine($datasets_kata[$dataset_key][$x_key], $values)));
					}
					
				} else {
					if(is_array($values)) {
						$series = $values;
					} else if(is_scalar($values)) {
						$series = [$values];
					}
				}
				
				$series_key = $dataset_key . '__' . $key;
				
				if(1 == $series_count) {
					$series_name = $dataset_name ?: $key;
				} else if ($dataset_name) {
					$series_name = sprintf("%s (%s)", $dataset_name, $key);
				} else {
					$series_name = $key;
				}
				
				if(!DevblocksPlatform::strEndsWith($key,'__click')) {
					if($color_group && !DevblocksPlatform::strEndsWith($key, '_x')) {
						if(!array_key_exists($key, $color_groups[$color_group]))
							$color_groups[$color_group][$key] = $color_pattern[count($color_groups[$color_group]) % count($color_pattern)];
						
						if(!array_key_exists($series_key, $chart_json['data']['colors'] ?? []))
							$chart_json['data']['colors'][$series_key] = $color_groups[$color_group][$key];
					}
					
					if(!array_key_exists($series_key, $chart_json['data']['names'] ?? []))
						$chart_json['data']['names'][$series_key] = $series_name;
					
					$chart_json['data']['columns'][] = [$series_key, ...$series];
					
					if($ytype)
						$chart_json['data']['types'][$series_key] = $ytype;
					
					if($yaxis == 'y2') {
						$chart_json['data']['axes'][$series_key] = $yaxis;
						$chart_json['axis']['y2']['show'] = true;
					}
					
				} else {
					$chart_json['data']['click_search'][$series_key] = [...$series];
					
				}
			}
			
			if('scatter' == ($chart_kata['data']['type'] ?? null)) {
				foreach(array_keys($datasets_kata[$dataset_key]) as $k) {
					if(DevblocksPlatform::strEndsWith($k, '_x'))
						continue;
					
					$chart_json['data']['xs'][$dataset_key . '__' . $k] = $dataset_key . '__' . $k . '_x';
				}
				
			} else {
				unset($datasets_kata[$dataset_key][$x_key]);
			}
		}
		
		// Sort legend by dataset names
		if($chart_kata['legend']['sorted'] ?? false) {
			usort($chart_json['data']['columns'], function($a, $b) use ($chart_json) {
				return ($chart_json['data']['names'][$a[0]] ?? '') <=> ($chart_json['data']['names'][$b[0]] ?? '');
			});
		}
		
		foreach($chart_kata['data']['stacks'] ?? [] as $dataset_keys) {
			if(!is_array($dataset_keys))
				continue;
			
			$group = [];
			
			foreach($dataset_keys as $dataset_key) {
				if(!array_key_exists($dataset_key, $datasets_kata))
					continue;
				
				foreach(array_keys($datasets_kata[$dataset_key]) as $k) {
					if(!DevblocksPlatform::strEndsWith($k, '__click'))
						$group[] = $dataset_key . '__' . $k;
				}
			}
			
			$chart_json['data']['groups'][] = $group;
		}
	}
	
	/**
	 * @param string $axis
	 * @param array $chart_kata
	 * @param array $chart_json
	 * @return void
	 * @throws Exception_DevblocksValidationError
	 */
	private function _parseChartAxis(string $axis, array $chart_kata, array &$chart_json) : void {
		if(!($chart_kata['axis'][$axis] ?? []))
			return;
		
		if(!($chart_kata['axis'][$axis]['type'] ?? false))
			$chart_kata['axis'][$axis]['type'] = 'linear';
		
		$chart_json['axis'][$axis]['type'] = $chart_kata['axis'][$axis]['type'];
		
		// Label
		if(($chart_kata['axis'][$axis]['label'] ?? false))
			$chart_json['axis'][$axis]['label'] = $chart_kata['axis'][$axis]['label'];
		
		// Category axis
		if('category' == $chart_kata['axis'][$axis]['type']) {
			if(($chart_kata['axis'][$axis]['categories'] ?? []) && is_array($chart_kata['axis'][$axis]['categories'])) {
				$chart_json['axis'][$axis]['categories'] = $chart_kata['axis'][$axis]['categories'];
			}
		}
		
		// Tick
		if($chart_kata['axis'][$axis]['tick'] ?? false) {
			$chart_json['axis'][$axis]['tick'] = [];
			
			if(array_key_exists('format', $chart_kata['axis'][$axis]['tick'])) {
				$format = $chart_kata['axis'][$axis]['tick']['format'];
				
				if('linear' == $chart_kata['axis'][$axis]['type']) {
					if(is_string($format))
						$format = ['number' => ['pattern' => $format]];
					
					if('duration' == array_key_first($format)) {
						$unit = $format['duration']['unit'] ?? null;
						
						if(!is_string($unit))
							throw new Exception_DevblocksValidationError(sprintf('`axis:%s:tick:format:duration:unit:` must be one of: milliseconds, seconds, minutes, hours.', $axis));
						
						if(!in_array($unit, ['milliseconds', 'seconds', 'minutes', 'hours']))
							$unit = 'seconds';
						
						$chart_json['axis'][$axis]['tick']['format_options'] = [
							'as' => 'duration',
							'params' => [
								'unit' => $unit,
								'precision' => intval($format['duration']['precision'] ?? 2),
							],
						];
						
					} else {
						$format = $format['number']['pattern'] ?? '';
						
						if(!is_string($format))
							throw new Exception_DevblocksValidationError(sprintf('`axis:%s:tick:format:duration:pattern:` must be a string.', $axis));
						
						// https://github.com/d3/d3-format#locale_format
						// [[fill]align][sign][symbol][0][width][,][.precision][~][type]
						
						$chart_json['axis'][$axis]['tick']['format_options'] = [
							'as' => 'number',
							'params' => [
								'pattern' => $format,
							],
						];
					}
					
				} else if('timeseries' == $chart_kata['axis'][$axis]['type']) {
					if(is_string($format))
						$format = ['date' => ['pattern' => $format]];
					
					if('date' == array_key_first($format)) {
						$pattern = $format['date']['pattern'] ?? '';
						
						if(!is_string($pattern))
							throw new Exception_DevblocksValidationError(sprintf('`axis:%s:tick:format:date:pattern:` must be a string.', $axis));
						
						$chart_json['axis'][$axis]['tick']['format_options'] = [
							'as' => 'date',
							'params' => [
								'pattern' => $pattern,
							],
						];
					}
				}
			}
			
			if(array_key_exists('fit', $chart_kata['axis'][$axis]['tick'])) {
				$chart_json['axis'][$axis]['tick']['fit'] = boolval($chart_kata['axis'][$axis]['tick']['fit']);
			}
			
			if(array_key_exists('multiline', $chart_kata['axis'][$axis]['tick'])) {
				$chart_json['axis'][$axis]['tick']['multiline'] = boolval($chart_kata['axis'][$axis]['tick']['multiline']);
			}
			
			if(array_key_exists('rotate', $chart_kata['axis'][$axis]['tick'])) {
				$chart_json['axis'][$axis]['tick']['rotate'] = DevblocksPlatform::intClamp($chart_kata['axis'][$axis]['tick']['rotate'], -360, 360);
			}
			
			if(empty($chart_json['axis'][$axis]['tick']))
				unset($chart_json['axis'][$axis]['tick']);
		}
	}
	
	private function _parseChartGrid(string $axis, array $chart_kata, array &$chart_json) : void {
		if(!($chart_kata['grid'][$axis] ?? []))
			return;
		
		if(array_key_exists('lines', $chart_kata['grid'][$axis])) {
			$chart_json['grid'][$axis]['lines'] = $chart_kata['grid'][$axis]['lines'];
		}
	}
	
	private function _parseChartLegend(array $chart_kata, array &$chart_json) : void {
		if(array_key_exists('legend', $chart_kata)) {
			if(array_key_exists('show', $chart_kata['legend']))
				$chart_json['legend']['show'] = boolval($chart_kata['legend']['show']);
		}
	}
	
	private function _parseChartTooltip(array $chart_kata, array &$chart_json) : void {
		if(array_key_exists('tooltip', $chart_kata)) {
			if(array_key_exists('show', $chart_kata['tooltip']))
				$chart_json['tooltip']['show'] = boolval($chart_kata['tooltip']['show']);
			
			if(array_key_exists('grouped', $chart_kata['tooltip']))
				$chart_json['tooltip']['grouped'] = boolval($chart_kata['tooltip']['grouped']);
		}
	}	
}