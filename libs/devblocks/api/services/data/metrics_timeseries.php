<?php
class _DevblocksDataProviderMetricsTimeseries extends _DevblocksDataProvider {
	public function getSuggestions($type, array $params = []) {
		$metrics = DAO_Metric::getAll();
		$metric_names = array_column($metrics, 'name');
		
		return [
			'' => [
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:name}:(\n  \n)",
				],
				'period:',
				'range:',
				'timeout:',
				'timezone:',
				'format:',
			],
			'period:' => [
				'minute',
				'hour',
				'day',
				'month',
				'week',
				'year',
			],
			'range:' => [
				'today',
				'yesterday',
				'"this week"',
				'"this month"',
				'"last week"',
				'"last month"',
				'"Jan 1 to Dec 31"',
			],
			'timeout:' => [
				'20000'
			],
			'timezone:' => DevblocksPlatform::services()->date()->getTimezones(),
			'format:' => [
				'timeseries',
			],
			'series.*:' => [
				'' => [
					[
						'caption' => 'by:',
						'snippet' => 'by:[${1:dimension_name}]',
					],
					'function:',
					'label:',
					'metric:',
					'missing:',
					'query:',
				],
				'function:' => [
					'average',
					'avg',
					'count',
					'max',
					'min',
					'samples',
					'sum',
				],
				'metric:' => $metric_names,
				'missing:' => [
					'carry',
					'null',
					'zero',
				]
			],
		];
	}
	
	public function getData($query, $chart_fields, &$error = null, array $options = []) {
		$chart_model = [
			'type' => 'metrics.timeseries',
			'series' => [],
			'period' => 'day',
			'period_unit' => 86400,
			'range' => '-6 hours',
			'xaxis' => [],
			'timeout' => 20000,
		];
		
		$allowed_periods = [
			'minute' => 300,
			'hour' => 3600,
			'day' => 86400,
			'week' => 86400,
			'month' => 86400,
			'year' => 86400,
		];
		
		$allowed_formats = [
			'timeseries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			// Do nothing
			if($field->key == 'type') {
				continue;
				
			} else if($field->key == 'range') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['range'] = DevblocksPlatform::strLower($value);
				
			} else if($field->key == 'period') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$period = DevblocksPlatform::strLower($value);
				
				if(!array_key_exists($period, $allowed_periods)) {
					$error = sprintf("Unknown `period:` (%s). Must be one of: %s",
						$period,
						implode(', ', array_keys($allowed_periods))
					);
					return false;
				}
				
				$chart_model['period'] = $period;
				$chart_model['period_unit'] = $allowed_periods[$period];
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$format = DevblocksPlatform::strLower($value);
				
				if(false === array_search($format, $allowed_formats)) {
					$error = sprintf("Unknown `format:` (%s). Must be one of: %s",
						$format,
						implode(', ', $allowed_formats)
					);
					return false;
				}
				
				$chart_model['format'] = $format;
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$series_model = [
					'id' => $series_id,
					'metric' => '',
					'by' => [],
					'query' => [],
					'missing' => null,
					'function' => 'count',
				];
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'metric') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['metric'] = $value;
						
					} else if($series_field->key == 'function') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$function = DevblocksPlatform::strLower($value);
						
						$allowed_functions = [
							'average' => 'average',
							'avg' => 'average',
							'count' => 'count',
							'max' => 'max',
							'min' => 'max',
							'samples' => 'count',
							'sum' => 'sum',
						];
						
						if(!array_key_exists($function, $allowed_functions)) {
							$error = sprintf("Unknown value for `function:` (%s). Must be one of: %s",
								$function,
								implode(', ', array_keys($allowed_functions))
							);
							return false;
						}
						
						$series_model['function'] = $allowed_functions[$value];
						
					} else if($series_field->key == 'missing') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$value = DevblocksPlatform::strLower($value);
						
						$allowed_missing = [
							'carry',
							'null',
							'zero',
						];
						
						if(false === array_search($value, $allowed_missing)) {
							$error = sprintf("Unknown value for `missing:` (%s). Must be one of: %s",
								$value,
								implode(', ', $allowed_missing)
							);
							return false;
						}
						
						$series_model['missing'] = $value;
						
					} else if($series_field->key == 'by') {
						CerbQuickSearchLexer::getOperArrayFromTokens($series_field->tokens, $oper, $value);
						
						if(is_array($value)) {
							foreach ($value as $k) {
								$series_model['by'][] = [
									'with' => $k,
									'as' => 'string',
									'params' => [],
								];
							}
						}
						
					} else if(DevblocksPlatform::strStartsWith($series_field->key, 'by.')) {
						$by_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$by_query = substr($by_query, 1, -1);
						
						$by_fields = CerbQuickSearchLexer::getFieldsFromQuery($by_query);
						
						$by_key = explode('.', $series_field->key, 2)[1];
						
						$by_meta = [
							'with' => $by_key,
							'as' => 'string',
							'params' => [],
						];
						
						$with = current(C4_AbstractView::findKey('with', $by_fields, false));
						
						if($with instanceof DevblocksSearchCriteria) {
							CerbQuickSearchLexer::getOperStringFromTokens($with->tokens, $oper, $value);
							$by_meta['with'] = $value;
						}
						
						$as = current(C4_AbstractView::findKey('as', $by_fields, false));
						
						if($as instanceof DevblocksSearchCriteria) {
							CerbQuickSearchLexer::getOperStringFromTokens($as->tokens, $oper, $value);
							
							switch($value) {
								case 'number':
								case 'string':
									break;
								
								case 'extension':
									$by_meta['as'] = 'extension';
									break;
								
								case 'record':
									$by_meta['as'] = 'record';
									
									$as_record_type = current(C4_AbstractView::findKey('record_type', $by_fields, false));
									$with_record_label = current(C4_AbstractView::findKey('record_label', $by_fields, false));
									
									if($as_record_type instanceof DevblocksSearchCriteria) {
										CerbQuickSearchLexer::getOperStringFromTokens($as_record_type->tokens, $oper, $value);
										$by_meta['params']['record_type'] = $value;
										$by_meta['params']['record_label'] = '_label';
										
										if($with_record_label) {
											CerbQuickSearchLexer::getOperStringFromTokens($with_record_label->tokens, $oper, $value);
											$by_meta['params']['record_label'] = $value ?: '_label';
										}
										
									} else {
										$error = sprintf("Unknown series `by:` record type (%s)",
											$by_key
										);
										return false;
									}
									break;
								
								default:
									$error = sprintf("Unknown series `by:` type (%s)",
										$by_key
									);
									return false;
							}
						}
						
						$series_model['by'][] = $by_meta;

					} else if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
						
					} else if($series_field->key == 'query') {
						$series_filter_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$series_filter_query = substr($series_filter_query, 1, -1);
						
						$series_model['query'] = [];
						
						$series_filter_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_filter_query);
						
						foreach($series_filter_fields as $series_filter_field) {
							CerbQuickSearchLexer::getOperArrayFromTokens($series_filter_field->tokens, $oper, $value);
							
							$series_model['query'][] = [
								'key' => $series_filter_field->key,
								'oper' => $oper,
								'value' => $value,
							];
						}
						
					} else {
						$error = sprintf("The series parameter '%s' is unknown.", $series_field->key);
						return false;
					}
				}
				
				$chart_model['series'][] = $series_model;
				
			} else if($field->key == 'timezone') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				
				if(!$value)
					$value = DevblocksPlatform::getTimezone();
				
				if(!DevblocksPlatform::services()->date()->isValidTimezoneLocation($value)) {
					$error = sprintf('Timezone `%s` is unknown.', $value);
					return false;
				}
				
				$chart_model['timezone'] = $value;
				
			} else if($field->key == 'timeout') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['timeout'] = DevblocksPlatform::intClamp($value, 0, 60000);
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Sanitize
		
		if(!array_key_exists('timezone', $chart_model)) {
			// If aggregating by day, use UTC time by default
			if(
				array_key_exists('period', $chart_model)
				&& in_array($chart_model['period'], ['day','week','month','year'])
			) {
				$chart_model['timezone'] = 'UTC';
			} else { // Otherwise, aggregate in the local timezone for mins + hours
				$chart_model['timezone'] = DevblocksPlatform::getTimezone();
			}
		}
		
		$chart_model['timezone_location'] = $chart_model['timezone'];
		
		$db = DevblocksPlatform::services()->database();
		$platform_timezone = DevblocksPlatform::getTimezone();
		
		try {
			// Override the platform timezone
			DevblocksPlatform::setTimezone($chart_model['timezone_location']);
			$db->SetReaderTimezone($chart_model['timezone_location']);
			
			return $this->_getData($chart_model, $error);
			
		} catch (Exception $e) {
			$error = "An unexpected error occurred.";
			return false;
			
		} finally {
			// Restore the platform timezone
			DevblocksPlatform::setTimezone($platform_timezone);
			$db->ResetReaderTimezone();
		}
	}
	
	private function _getData(array $chart_model, &$error=null) {
		$range = DevblocksPlatform::services()->date()->parseDateRange($chart_model['range']);
		
		// Normalize
		if($chart_model['period_unit'] == 86400) {
			try {
				// Our range should start at midnight UTC
				$ts = new DateTime();
				$ts->setTimestamp($range['from_ts']);
				$ts->setTime(0, 0, 0);
				
				// Weeks need to start on Monday
				if ('week' == $chart_model['period']) {
					$ts->modify('Monday this week');
				}
				
				$range['from_ts'] = $ts->getTimestamp();
				$range['from_string'] = $ts->format('Y-m-d H:i:s');
				
				// Our range should end right before midnight UTC
				$ts->setTimestamp($range['to_ts']);
				$ts->setTime(23, 59, 59);
				$range['to_ts'] = $ts->getTimestamp();
				$range['to_string'] = $ts->format('Y-m-d H:i:s');
				
			} catch(Exception $e) {
				$error = 'Invalid date range';
				return false;
			}
			
		} else {
			$range['from_ts'] -= $range['from_ts'] % $chart_model['period_unit'];
			$range['from_string'] = date('Y-m-d H:i', $range['from_ts']);
			$range['to_ts'] -= $range['to_ts'] % $chart_model['period_unit'];
			$range['to_string'] = date('Y-m-d H:i', $range['to_ts']);
		}
		
		$step = 1;
		
		switch($chart_model['period']) {
			case 'minute':
				$unit = 'minute';
				$unit_format_js = '%Y-%m-%d %H:%M';
				$unit_format_php = 'Y-m-d H:i';
				$step = 5;
				break;
				
			case 'hour':
				$unit = 'hour';
				$unit_format_js = '%Y-%m-%d %H:00';
				$unit_format_php = 'Y-m-d H:00';
				break;
				
			case 'day':
				$unit = 'day';
				$unit_format_js = '%Y-%m-%d';
				$unit_format_php = 'Y-m-d';
				break;
				
			case 'week':
				$unit = 'week';
				$unit_format_js = '%Y-%m-%d';
				$unit_format_php = 'Y-m-d';
				break;
				
			case 'month':
				$unit = 'month';
				$unit_format_js = '%Y-%m';
				$unit_format_php = 'Y-m';
				break;
				
			case 'year':
				$unit = 'year';
				$unit_format_js = '%Y';
				$unit_format_php = 'Y';
				break;
				
			default:
				$error = "`period:` is invalid.";
				return false;
		}
		
		$chart_model['xaxis'] = DevblocksPlatform::services()->date()->formatTimestamps(
			DevblocksPlatform::dateLerpArray([$range['from_string'], $range['to_string']], $unit, $step, 1001),
			$unit_format_php
		);
		
		if(count($chart_model['xaxis']) > 1000) {
			$error = "Exceeded the limit of 1000 x-axis ticks. Reduce the `range:` or use a larger `period:`";
			return false;
		}
		
		$results = [
			'ts' => $chart_model['xaxis'],
		];
		
		$chart_model['groups'] = [];
		
		// Group expanded series together
		foreach(array_keys($chart_model['series']) as $series_idx) {
			if(false === ($series_data = $this->_loadSeriesData($series_idx, $chart_model, $range, $unit_format_php, $error)))
				return false;
			
			$chart_model['groups'][] = array_keys($series_data);
			$results = $results + $series_data;
		}
		
		// [TODO] handle multiple formats
		
		return ['data' => $results, '_' => [
			'type' => 'metrics.timeseries',
			'groups' => $chart_model['groups'] ?? [],
			'format' => 'timeseries',
			'format_params' => [
				'xaxis_key' => 'ts',
				'xaxis_step' => $unit,
				'xaxis_format' => $unit_format_js,
			],
		]];
	}
	
	private function _loadSeriesData($series_idx, array $chart_model, array $range, string $unit_format, &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		$series_model = $chart_model['series'][$series_idx] ?? [];
		
		$results = [];
		
		if(!$series_model)
			return [];
		
		if(false == ($metric = DAO_Metric::getByName($series_model['metric'] ?? null)))
			return [];
		
		$metric_dimensions = $metric->getDimensions();
		
		$sql_wheres = [];
		
		if(array_key_exists('query', $series_model) && is_array($series_model['query'])) {
			foreach($series_model['query'] as $filter) {
				$metric_dimension = $metric_dimensions[$filter['key']] ?? null;
				
				if(is_null($metric_dimension)) {
					$error = sprintf("Query filter `%s:` is unknown.",
						$filter['key']
					);
					return false;
				}
				
				$dim_key = sprintf('dim%d_value_id', array_search($filter['key'], array_keys($metric_dimensions)));
				
				switch($metric_dimension['type']) {
					case 'number':
					case 'record':
						if($filter['oper'] == DevblocksSearchCriteria::OPER_IN && is_array($filter['value']) && $filter['value']) {
							if($metric_dimension)
								$sql_wheres[] = sprintf('%s IN (%s)',
									$db->escape($dim_key),
									implode(',', $db->qstrArray($filter['value']))
								);
						}
						break;
						
					default:
						if($filter['oper'] == DevblocksSearchCriteria::OPER_IN && is_array($filter['value']) && $filter['value']) {
							if($metric_dimension)
								$sql_wheres[] = sprintf('%s IN (SELECT id FROM metric_dimension WHERE name IN (%s))',
									$db->escape($dim_key),
									implode(',', $db->qstrArray($filter['value']))
								);
						}
						break;
				}
			}
		}
		
		$func = DevblocksPlatform::strLower($series_model['function'] ?? 'count');
		$sql_select_keys = 'bin';
		$sql_group_by = 'bin';
		$granularity = $chart_model['period_unit']; // 300, 3600, 86400
		
		// [TODO] Limit TOP(n) and BOTTOM(n)
		
		if('year' == $chart_model['period']) {
			$sql_select_keys = "UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME(bin), '%Y-01-01 00:00:00')) AS bin";
			
		} else if('month' == $chart_model['period']) {
			$sql_select_keys = "UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME(bin), '%Y-%m-01 00:00:00')) AS bin";
			
		} else if('week' == $chart_model['period']) {
			$sql_select_keys = "UNIX_TIMESTAMP(STR_TO_DATE(CONCAT(YEARWEEK(FROM_UNIXTIME(bin),1),' Monday'),'%x%v %W')) AS bin";	
		}
		
		$sql = sprintf("SELECT %s, %s AS value FROM metric_value WHERE metric_value.metric_id = %d AND metric_value.granularity = %d AND metric_value.bin BETWEEN %d AND %d %s GROUP BY %s",
			'%s',
			'%s',
			$metric->id,
			$granularity,
			$range['from_ts'] ?? 0,
			$range['to_ts'] ?? 0,
			$sql_wheres ? ('AND ' . implode(' AND ', $sql_wheres)) : '',
			'%s'
		);
		
		// Metric types
		if('gauge' == $metric->type) {
			if($func == 'average') {
			$sql_select_func = 'SUM(sum/samples)';				
				
			} else if($func == 'sum') {
				$sql_select_func = 'SUM(sum)';
				
			} else if($func == 'min') {
				$sql_select_func = 'SUM(min)';
				
			} else if($func == 'max') {
				$sql_select_func = 'SUM(max)';
				
			} else if($func == 'count') {
				$sql_select_func = 'SUM(samples)';
				
			} else {
				return [];
			}
			
		} else { // Counter
			if($func == 'average') {
				$sql_select_func = 'AVG(sum/samples)';
				
			} else if($func == 'sum') {
				$sql_select_func = 'SUM(sum)';
				
			} else if($func == 'min') {
				$sql_select_func = 'MIN(min)';
				
			} else if($func == 'max') {
				$sql_select_func = 'MAX(max)';
				
			} else if($func == 'count') {
				$sql_select_func = 'SUM(samples)';
				
			} else {
				return [];
			}
		}
		
		if(array_key_exists('by', $series_model) && $series_model['by']) {
			foreach ($series_model['by'] as $by_idx => $by_meta) {
				if(array_key_exists($by_meta['with'], $metric_dimensions)) {
					$dimension_index = array_search($by_meta['with'], array_keys($metric_dimensions));
				
					$sql_select_keys .= sprintf(', dim%d_value_id AS dim_%d',
						$dimension_index,
						$by_idx
					);
					
					$sql_group_by .= sprintf(',dim_%d', $by_idx);
					
				} else {
					$error = sprintf("Unknown series dimension (%s). Should be one of: %s",
						$by_meta['with'],
						implode(', ', array_keys($metric_dimensions))
					);
					return false;
				}
			}
		}
		
		$sql = sprintf($sql,
			$sql_select_keys,
			$sql_select_func,
			$sql_group_by
		);
		
		$rows = $db->GetArrayReader($sql);
		
		// [TODO] Any limit on this expansion?
		
		if($series_model['by']) {
			foreach($series_model['by'] as $by_idx => $by_meta) {
				$dim_key = sprintf("dim_%d", $by_idx);
				
				$by_dimension = $metric_dimensions[$by_meta['with']] ?? null;
				$by_type = $by_dimension['type'] ?? null;
				
				if ('record' == $by_type) {
					$record_type = $by_dimension['params']['record_type'] ?? null;
					$record_label = $by_meta['params']['record_label'] ?? '_label';
					
					$record_ids = array_unique(array_column($rows, $dim_key));
					
					$models = CerberusContexts::getModels($record_type, $record_ids);
					$dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $record_type, [$record_label]);
					
					// Replace the underlying row data
					foreach ($rows as $row_idx => $row) {
						if (!array_key_exists($row[$dim_key], $dicts))
							continue;
						
						// Preserve the original ID!
						$rows[$row_idx][$dim_key . '__context'] = $record_type;
						$rows[$row_idx][$dim_key . '_id'] = $row[$dim_key];
						$rows[$row_idx][$dim_key] = $dicts[$row[$dim_key]]->get($record_label);
					}
					
				} else if ('number' == $by_type) { // Number
					DevblocksPlatform::noop();
					
				} else { // String
					$dimension_labels = array_column(DAO_MetricDimension::getIds(array_unique(array_column($rows, $dim_key))), 'name', 'id');
					
					foreach ($rows as $row_idx => $row) {
						$rows[$row_idx][$dim_key] = $dimension_labels[$row[$dim_key]] ?? $row[$dim_key];
					}
				}

				// These happen after strings are labeled
				
				if ('extension' == $by_dimension['type']) {
					$extensions = DevblocksPlatform::getExtensionRegistry();
					
					foreach ($rows as $row_idx => $row) {
						if (false == ($ext_mft = $extensions[$row[$dim_key]]))
							continue;
						
						$rows[$row_idx][$dim_key] = $ext_mft->name;
					}
				}
			}
		}
		
		if(is_array($rows) && $rows) {
			foreach ($rows as $row) {
				$series_label = [];
				
				// If more than one series, include metric name in labels
				
				if (array_key_exists('label', $series_model)) {
					$series_label[] = $series_model['label'];
				} else if (count($chart_model['series']) > 1) {
					$series_label[] = $metric->name;
				}
				
				foreach (array_keys($series_model['by']) as $dim_idx) {
					$dim_key = 'dim_' . $dim_idx;
					if (array_key_exists($dim_key, $row)) {
						$by_label = $row[$dim_key];
						$series_label[] = $by_label;
					}
				}
				
				if (empty($series_label))
					$series_label[] = $metric->name;
				
				$series_label = implode(' :: ', $series_label);
				
				if (!array_key_exists($series_label, $results))
					$results[$series_label] = array_fill_keys($chart_model['xaxis'], null);
				
				$dt = new DateTime();
				$dt->setTimestamp($row['bin']);
				
				$bin = $dt->format($unit_format);
				
				$results[$series_label][$bin] = floatval($row['value']);
			}
			
		} else {
			// If series rows are empty, insert a null series
			$series_label = ($series_model['label'] ?? null) ?: $metric->name;
			$results[$series_label] = array_fill_keys($chart_model['xaxis'], null);
		}
		
		// Gauges carry missing data by default
		if(!$series_model['missing'] && 'gauge' == $metric->type)
			$series_model['missing'] = 'carry';
		
		foreach($results as $series_label => $data) {
			// Convert back to sequential indexes
			$data = array_values($data);
			
			// Format missing data
			switch($series_model['missing'] ?? 'null') {
				case 'carry':
					$last_idx = count($data)-1;
					foreach($data as $idx => $v) {
						if(is_null($v) && $idx > 0 && $idx <= $last_idx)
							$data[$idx] = $data[$idx-1];
					}
					break;
					
				case 'zero':
					$data = array_map(fn($v) => $v ?: 0, $data);
					break;
			}
			
			$results[$series_label] = $data;
		}
		
		return $results;
	}
}