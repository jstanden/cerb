<?php
class _DevblocksDataProviderWorklistSeries extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:alias}:(\n  of:\${2:ticket}\n  x:\${3:id}\n  function:\${4:count}\n  query:(\n    \${5}\n  )\n)",
					'suppress_autocomplete' => true,
				],
				'x.label:',
				'format:',
				'timeout:',
				'timezone:',
			],
			'series.*:' => [
				'' => [
					'of:',
					'label:',
					'x:',
					'y:',
					'y.metric:',
					'function:',
					[
						'caption' => 'query:',
						'snippet' => 'query:(${1})',
					],
					[
						'caption' => 'query.required:',
						'snippet' => 'query.required:(${1})',
					],
				],
				'of:' => array_values(Extension_DevblocksContext::getUris()),
				'function:' => [
					'count',
					'sum',
					'avg',
					'min',
					'max',
				],
				'x.label' => [],
				'x:' => [
					'_type' => 'series_of_field',
					'of_types' => 'bool,context,currency,date,decimal,number,number_minutes,number_ms,number_seconds',
				],
				'y:' => [
					'_type' => 'series_of_field',
					'of_types' => 'bool,context,currency,date,decimal,number,number_minutes,number_ms,number_seconds',
				],
				'query:' => [
					'_type' => 'series_of_query',
				],
				'query.required:' => [
					'_type' => 'series_of_query',
				],
			],
			'timezone:' => DevblocksPlatform::services()->date()->getTimezones(),
			'format:' => [
				'pie',
				'table',
				'timeseries',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.series',
			'series' => [],
			'x.label' => 'Metric',
			'format' => 'timeseries',
			'timeout' => 20000,
		];
		
		foreach ($chart_fields as $field) {
			$oper = $value = null;
			
			if (!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if ($field->key == 'type') {
				// Do nothing
				DevblocksPlatform::noop();
				
			} else if ($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = DevblocksPlatform::strLower($value);
				
			} else if ($field->key == 'x.label') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['x.label'] = $value;
				
			} else if ($field->key == 'timeout') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['timeout'] = DevblocksPlatform::intClamp($value, 0, 60000);
				
			} else if ($field->key == 'timezone') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				
				if (!$value)
					$value = DevblocksPlatform::getTimezone();
				
				if (!is_string($value)) {
					$error = 'The value for `timezone:` must be a string.';
					return false;
				}
				
				if (DevblocksPlatform::services()->date()->isValidTimezoneLocation($value)) {
					$chart_model['timezone'] = $value;
				}
				
			} else if (DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$series_model = [
					'id' => $series_id,
					'label' => $series_id,
					'x' => '',
					'y' => 'id',
					'function' => 'count',
				];
				
				$series_context = null;
				
				foreach ($series_fields as $series_field) {
					if ($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if (false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
					} else if ($series_field->key == 'function') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['function'] = $value;
						
					} else if ($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
						
					} else if ($series_field->key == 'x') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['x'] = $value;
						
					} else if ($series_field->key == 'y') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y'] = $value;
						
					} else if ($series_field->key == 'y.metric') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y_metric'] = $value;
						
					} else if ($series_field->key == 'query') {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query'] = $data_query;
						
					} else if (in_array($series_field->key, ['query.require', 'query.required'])) {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query_required'] = $data_query;
						
					} else {
						$error = sprintf("The series parameter '%s' is unknown.", $series_field->key);
						return false;
					}
				}
				
				// If we aren't given a bin, default to months
				if (!strpos($series_model['x'], '@'))
					$series_model['x'] .= '@month';
				
				// Convert series x/y to SearchFields_* using context
				
				if ($series_context) {
					$view = $series_context->getTempView();
					$search_class = $series_context->getSearchClass();
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
					// [TODO] The field has to be a date type
					// [TODO] Handle currency/decimal
					
					if(array_key_exists('x', $series_model)) {
						if(false == ($x_field = $search_class::getFieldForSubtotalKey($series_model['x'], $series_context->id, $query_fields, $search_fields, $search_class::getPrimaryKey()))) {
							unset($series_model['x']);
						} else {
							$series_model['x'] = $x_field;
						}
					}
					
					if(array_key_exists('y', $series_model)) {
						if(false == ($y_field = $search_class::getFieldForSubtotalKey($series_model['y'], $series_context->id, $query_fields, $search_fields, $search_class::getPrimaryKey()))) {
							unset($series_model['y']);
						} else {
							$series_model['y'] = $y_field;
						}
					}
				}
				
				$chart_model['series'][] = $series_model;
			
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		if (!array_key_exists('timezone', $chart_model))
			$chart_model['timezone'] = DevblocksPlatform::getTimezone();
		
		$chart_model['timezone_location'] = $chart_model['timezone'];
		
		$platform_timezone = DevblocksPlatform::getTimezone();
		
		try {
			// Override the platform timezone 
			DevblocksPlatform::setTimezone($chart_model['timezone_location']);
			$db->SetReaderTimezone($chart_model['timezone_location']);
			
			// Fetch the data with the given timezone
			if(false === ($data = $this->_getData($chart_model, $error)))
				return false;
			
			return $data;
			
		} catch (Exception $e) {
			DevblocksPlatform::logError($e->getMessage());
			$error = "An unexpected error occurred";
			return false;
			
		} finally {
			// Reset the platform timezone
			DevblocksPlatform::setTimezone($platform_timezone);
			$db->ResetReaderTimezone();
		}
		
	}
	
	private function _getData(array $chart_model, &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		// Fetch data for each series
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series_idx => $series) {
			if(!isset($series['context']))
				continue;
			
			$query = $series['query'] ?? null;
			$query_required = $series['query_required'] ?? null;
			
			$context_ext = Extension_DevblocksContext::get($series['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$view = $context_ext->getTempView();
			
			if(false === $view->addParamsRequiredWithQuickSearch($query_required, true, [], $error))
				return false;
			
			if(false === $view->addParamsWithQuickSearch($query, true, [], $error))
				return false;
			
			if(!method_exists($dao_class, 'getSearchQueryComponents')) {
				$error = sprintf('%s::getSearchQueryComponents() not implemented', $dao_class);
				return false;
			}
			
			$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
			
			$date_field = $series['x']['sql_select'] ?? null;
			$function = $series['function'] ?? null;
			
			switch($function) {
				case 'average':
					$metric_field = sprintf("AVG(%s)",
						$series['y']['sql_select']
					);
					break;
					
				default:
				case 'count':
					$metric_field = "COUNT(*)";
					break;
					
				case 'max':
					$metric_field = sprintf("MAX(%s)",
						$series['y']['sql_select']
					);
					break;
					
				case 'min':
					$metric_field = sprintf("MIN(%s)",
						$series['y']['sql_select']
					);
					break;
					
				case 'sum':
					$metric_field = sprintf("SUM(%s)",
						$series['y']['sql_select']
					);
					break;
			}
			
			if(!$date_field || !$metric_field)
				continue;
			
			$sql = sprintf("SELECT %s AS metric, %s AS value %s %s GROUP BY %s LIMIT %d",
				$metric_field,
				$date_field,
				$query_parts['join'],
				$query_parts['where'],
				$date_field,
				$view->renderLimit
			);
			
			try {
				$results = $db->GetArrayReader($sql, $chart_model['timeout']);
				
			} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
				$error = sprintf('Query timed out (%d ms)', $chart_model['timeout']);
				return false;
			}
			
			$results = array_column($results, 'metric', 'value');
			ksort($results);
			
			// Metric expression
			if(array_key_exists('y_metric', $series)) {
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$metric_template = sprintf('{{%s}}',
					$series['y_metric']
				);
				
				$results = array_map(function($v) use ($tpl_builder, $metric_template) {
					$out = $tpl_builder->build($metric_template, [
						'y' => $v,
					]);
					
					if(false === $out || !is_numeric($out)) {
						return 0;
					}
					
					return floatval($out);
				}, $results);
			}
			
			$chart_model['series'][$series_idx]['data'] = $results;
		}
		
		@$format = $chart_model['format'] ?: 'timeseries';
		
		switch($format) {
			case 'pie':
				return $this->_formatDataAsPie($chart_model);
				
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($chart_model);
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: table, tree",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	private function _formatDataAsPie(array $chart_model) : array {
		// Domain
		
		$x_domain = [];
		
		if(array_key_exists('series', $chart_model) && is_array($chart_model['series']))
			foreach($chart_model['series'] as $series) {
				if(!isset($series['data']))
					continue;
				
				// Add the unique x values
				$x_domain = array_unique(array_merge($x_domain, array_keys($series['data'])));
			}
		
		// Make sure timestamps are strings (for c3.js)
		$x_domain = array_map(function($v) { return strval($v); }, $x_domain);
		
		sort($x_domain);
		
		// Table
		
		$wedges = array_fill_keys(array_column($chart_model['series'], 'id'), 0);
		
		foreach($x_domain as $k) {
			foreach($chart_model['series'] as $series) {
				$wedges[$series['id']] += @$series['data'][$k] ?: 0;
			}
		}
		
		$wedges = array_map(fn($k) => [$k,$wedges[$k]], array_keys($wedges));
		
		return [
			'data' => $wedges,
			'_' => [
				'type' => 'worklist.series',
				'format' => 'pie',
			]
		];
	}
	
	private function _formatDataAsTable(array $chart_model) : array {
		$columns = [
			'x' => [
				'label' => @$chart_model['x.label'],
				'type' => DevblocksSearchCriteria::TYPE_TEXT,
			]
		];
		
		// Domain
		
		$x_domain = [];
		
		if(array_key_exists('series', $chart_model) && is_array($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			if(!isset($series['data']))
				continue;
			
			// Add a column per series
			$columns[$series['id']] = [
				'label' => $series['label'],
				'type' => @$series['y']['type'] ?: DevblocksSearchCriteria::TYPE_TEXT,
				'type_options' => @$series['y']['type_options'] ?: [],
			];
			
			// Add the unique x values
			$x_domain = array_unique(array_merge($x_domain, array_keys($series['data'])));
		}
		
		// Make sure timestamps are strings (for c3.js)
		$x_domain = array_map(function($v) { return strval($v); }, $x_domain);
		
		sort($x_domain);
		
		// Table
		
		$rows = [];
		
		foreach($x_domain as $k) {
			$row = [
				'x' => $k,
			];
			
			// [TODO] Inefficient
			foreach($chart_model['series'] as $series) {
				$row[$series['id']] = @$series['data'][$k] ?: 0;
			}
			
			$rows[] = $row;
		}
		
		$table = [
			'columns' => $columns,
			'rows' => $rows,
		];
		
		return [
			'data' => $table,
			'_' => [
				'type' => 'worklist.series',
				'format' => 'table',
			]
		];
	}
	
	private function _formatDataAsTimeSeries(array $chart_model) : array {
		// [TODO] Verify that 'x' is a date
		
		// Domain
		
		$xaxis_format = @$chart_model['series'][0]['x']['timestamp_format'] ?: '';
		$xaxis_unit = @$chart_model['series'][0]['x']['timestamp_step'] ?: '';
		
		if(false !== strpos($xaxis_unit, '/'))
			list($xaxis_unit, $xaxis_step) = array_pad(explode('/', $xaxis_unit), 2, null);
		
		$xaxis_step = intval($xaxis_step ?? null);
		
		$x_domain = [];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			if(!isset($series['data']))
				continue;
			
			$x_domain = array_unique(array_merge($x_domain, array_keys($series['data'])));
		}
		
		// Make sure timestamps are strings (for c3.js)
		$x_domain = array_map(function($v) { return strval($v); }, $x_domain);
		
		sort($x_domain);
		
		$x_domain = DevblocksPlatform::services()->date()->formatTimestamps(
			DevblocksPlatform::dateLerpArray($x_domain, $xaxis_unit, $xaxis_step, 2500),
			DevblocksPlatform::services()->date()->formatByUnit($xaxis_unit)
		);
		
		$response = [
			'ts' => $x_domain,
		];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			$values = [];
			
			foreach($x_domain as $k) {
				$values[] = floatval(($series['data'][$k] ?? null) ?: 0);
			}
			
			$response[$series['label']] = $values;
		}
		
		return [
			'data' => $response,
			'_' => [
				'type' => 'worklist.series',
				'format' => 'timeseries',
				'format_params' => [
					'xaxis_key' => 'ts',
					'xaxis_step' => $xaxis_unit,
					'xaxis_format' => $xaxis_format, // [TODO] Multi-series?
				],
			]
		];
	}
};