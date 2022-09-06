<?php
class _DevblocksDataProviderWorklistSubtotals extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$of = $params['of'] ?? null;
		$of_schema = null;
		
		// Sanitize `of:`
		if($of) {
			if(
				false != ($context_ext = Extension_DevblocksContext::getByAlias($of, true))
				&& false != ($view = $context_ext->getTempView()))
			{
				$of_schema = $view->getQueryAutocompleteSuggestions();
			}
		}
		
		if(!$of_schema) {
			return [
				'' => [
					'of:',
				],
				'type:' => array_keys(DevblocksPlatform::services()->data()->getTypes()),
				'of:' => array_values(Extension_DevblocksContext::getUris()),
			];
		}
		
		$suggestions = [
			'' => [
				'of:',
				[
					'caption' => 'by.count:',
					'snippet' => 'by.count:[${1}]',
				],
				[
					'caption' => 'by.avg:',
					'snippet' => 'by.avg:[${1}]',
				],
				[
					'caption' => 'by.sum:',
					'snippet' => 'by.sum:[${1}]',
				],
				[
					'caption' => 'by.distinct:',
					'snippet' => 'by.distinct:[${1}]',
				],
				[
					'caption' => 'by.min:',
					'snippet' => 'by.min:[${1}]',
				],
				[
					'caption' => 'by.max:',
					'snippet' => 'by.max:[${1}]',
				],
				[
					'caption' => 'query:',
					'snippet' => 'query:(${1})',
				],
				[
					'caption' => 'query.required:',
					'snippet' => 'query.required:(${1})',
				],
				'timeout:',
				'timezone:',
				'format:',
				'metric:',
				[
					'caption' => 'expand:',
					'snippet' => 'expand:[${1}]',
				],
			],
			'of:' => array_values(Extension_DevblocksContext::getUris()),
			'by:' => [],
			'by.count:' => [],
			'by.avg:' => [],
			'by.sum:' => [],
			'by.distinct:' => [],
			'by.min:' => [],
			'by.max:' => [],
			'query:' => [],
			'query.required:' => [],
			'metric:' => [
				[
					'caption' => '(expression)',
					'snippet' => '"${1:x*1}"',
				]
			],
			'timezone:' => DevblocksPlatform::services()->date()->getTimezones(),
			'format:' => [
				'tree',
				'dictionaries',
				'categories',
				'table',
				'timeseries',
				'pie',
			]
		];
		
		foreach($of_schema as $of_path => $of_suggestions) {
			if('_contexts' == $of_path) {
				if(!array_key_exists('_contexts', $suggestions))
					$suggestions['_contexts'] = [];
				
				foreach($of_suggestions as $ctx_path => $ctx_suggestion) {
					$suggestions['_contexts']['query:' . $ctx_path] = $ctx_suggestion;
					$suggestions['_contexts']['query.required:' . $ctx_path] = $ctx_suggestion;
				}
				
			} else {
				$suggestions['query:' . $of_path] = $of_suggestions;
				$suggestions['query.required:' . $of_path] = $of_suggestions;
				
				if('subtotal:' == $of_path) {
					// Merge virtual grouping suggestions
					if(isset($context_ext)) {
						if($context_ext->id == CerberusContexts::CONTEXT_TICKET) {
							$of_suggestions = array_merge($of_suggestions, SearchFields_Ticket::getVirtualSubtotalKeys());
						}
					}
					
					$suggestions['by:'] = $of_suggestions;
					$suggestions['by.count:'] = $of_suggestions;
					$suggestions['by.avg:'] = $of_suggestions;
					$suggestions['by.sum:'] = $of_suggestions;
					$suggestions['by.distinct:'] = $of_suggestions;
					$suggestions['by.min:'] = $of_suggestions;
					$suggestions['by.max:'] = $of_suggestions;
				}
			}
		}
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.subtotals',
			'function' => 'count',
			'timeout' => 20000,
		];
		
		$subtotals_context = null;
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				// Do nothing
				true;
				
			} else if($field->key == 'function') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['function'] = DevblocksPlatform::strLower($value);
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = DevblocksPlatform::strLower($value);
				
			} else if($field->key == 'of') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				if(false == ($subtotals_context = Extension_DevblocksContext::getByAlias($value, true)))
					continue;
				
				$chart_model['context'] = $subtotals_context->id;
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'by.')) {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['by'] = $value;
				
				list(,$func) = explode('.', $field->key, 2);
				$chart_model['function'] = DevblocksPlatform::strLower($func);
				
			} else if($field->key == 'by') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['by'] = $value;
				
			} else if($field->key == 'expand') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['expand'] = $value;
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'group.')) {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['group'] = $value;
				
				list(,$func) = explode('.', $field->key, 2);
				$chart_model['group_function'] = DevblocksPlatform::strLower($func);
				
			} else if($field->key == 'group') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['group'] = $value;
				$chart_model['group_function'] = 'sum';
				
			} else if($field->key == 'metric') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['metric'] = $value;
				
			} else if($field->key == 'query') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['query'] = $data_query;
				
			} else if(in_array($field->key, ['query.require','query.required'])) {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['query_required'] = $data_query;
				
			} else if($field->key == 'timezone') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				
				if(!$value)
					$value = DevblocksPlatform::getTimezone();
				
				if(!is_string($value)) {
					$error = 'The value for `timezone:` must be a string.';
					return false;
				}
				
				if(DevblocksPlatform::services()->date()->isValidTimezoneLocation($value)) {
					$chart_model['timezone'] = $value;
				}
				
			} else if($field->key == 'timeout') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['timeout'] = DevblocksPlatform::intClamp($value, 0, 60000);
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Sanitize
		
		if(!array_key_exists('timezone', $chart_model))
			$chart_model['timezone'] = DevblocksPlatform::getTimezone();
		
		$chart_model['timezone_location'] = $chart_model['timezone'];
		
		if(!isset($chart_model['by'])) {
			$error = "The `by:` field is required.";
			return false;
		}
		
		if(!$subtotals_context) {
			$error = "The `of:` field is not a valid context type.";
			return false;
		}
		
		$platform_timezone = DevblocksPlatform::getTimezone();
		
		try {
			// Override the platform and database timezone 
			DevblocksPlatform::setTimezone($chart_model['timezone_location']);
			$db->SetReaderTimezone($chart_model['timezone_location']);
			
			// Fetch the data with the given timezone
			if(false === ($data = $this->_getData($chart_model, $subtotals_context, $error)))
				return false;
			
			return $data;
			
		} catch(Exception $e) {
			DevblocksPlatform::logError($e->getMessage());
			$error = "An unexpected error occurred";
			return false;
			
		} finally {
			// Reset the platform and database timezone
			DevblocksPlatform::setTimezone($platform_timezone);
			$db->ResetReaderTimezone();
		}
	}
	
	private function _getData($chart_model, $subtotals_context, &$error=null) {
		$db = DevblocksPlatform::services()->database();
		
		// Convert 'by:' keys to fields
		
		$dao_class = $subtotals_context->getDaoClass();
		$search_class = $subtotals_context->getSearchClass();
		$view = $subtotals_context->getTempView();
		
		if(false === $view->addParamsRequiredWithQuickSearch($chart_model['query_required'] ?? null, true, [], $error))
			return false;
			
		if(false === $view->addParamsWithQuickSearch($chart_model['query'] ?? null, true, [], $error))
			return false;
		
		if(!method_exists($view, 'getQuickSearchFields')) {
			$error = sprintf('%s::getQuickSearchFields() not implemented', get_class($view));
			return false;
		}
		
		$query_fields = $view->getQuickSearchFields();
		$search_fields = $view->getFields();
		
		if($chart_model['by']) {
			$subtotal_by = $chart_model['by'];
			unset($chart_model['by']);
			
			if(!is_array($subtotal_by))
				$subtotal_by = [$subtotal_by];
			
			$group_by = ($chart_model['group'] ?? null) ?: [];
			unset($chart_model['group']);
			
			if(!is_array($group_by))
				$group_by = [$group_by];
			
			$by_keys = [];
			
			foreach($subtotal_by as $by) {
				// Handle limits and orders
				list($by, $limit) = array_pad(explode('~', $by, 2), 2, null);
				
				if(false == ($subtotal_field = $search_class::getFieldForSubtotalKey($by, $subtotals_context->id, $query_fields, $search_fields, $search_class::getPrimaryKey()))) {
					$error = sprintf("Unknown `by:` field: %s", $by);
					return false;
				}
				
				// If it's a date time-step, allow the full range
				if(is_null($limit) && array_key_exists('timestamp_step', $subtotal_field)) {
					$limit = '250';
				}
				
				$limit_desc = DevblocksPlatform::strStartsWith($limit, '-') ? false : true;
				$limit = DevblocksPlatform::intClamp(abs($limit ?? 0), 0, 2000) ?: 25;
				
				$subtotal_field['limit'] = $limit;
				$subtotal_field['limit_desc'] = $limit_desc;
				
				// If we reference the same field multiple times, make it unique
				if(array_key_exists($subtotal_field['key_select'], $by_keys)) {
					$subtotal_field['key_select'] = $subtotal_field['key_select'] . '_' . uniqid();
				} else {
					$by_keys[$subtotal_field['key_select']] = true;
				}
				
				$chart_model['by'][] = $subtotal_field;
				
				if(in_array($by, $group_by)) {
					$chart_model['group'][] = $subtotal_field;
				}
			}
		}
		
		if(!isset($chart_model['by']) || !$chart_model['by']) {
			$error = "The `by:` field is not a valid context type.";
			return false;
		}
		
		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
		
		// Aggregate function
		// [TODO] Are limits and sort orders included in agg functions?
		
		$func = 'COUNT(*)';
		$by_fields = $chart_model['by'];
		
		$func_map = [
			'avg' => 'AVG',
			'average' => 'AVG',
			'distinct' => null,
			'sum' => 'SUM',
			'min' => 'MIN',
			'max' => 'MAX',
		];
		
		if(array_key_exists($chart_model['function'], $func_map)) {
			$func_field = array_pop($by_fields);
			
			if('distinct' == $chart_model['function']) {
				$func = sprintf('COUNT(DISTINCT %s)', $func_field['sql_select']);
			} else {
				$func = sprintf('%s(%s)', $func_map[$chart_model['function']], $func_field['sql_select']);
			}
			
		}
		
		// Pre-filter
		
		$sql_where = $query_parts['where'];
		
		foreach($by_fields as $by) {
			$limit = DevblocksPlatform::intClamp($by['limit'], 0, 2000) ?: 25;
			$limit_desc = $by['limit_desc'] ?? null;
			
			$sql = sprintf("SELECT COUNT(*) AS hits, %s %s %s %s GROUP BY `%s` ORDER BY hits %s LIMIT %d",
				sprintf("%s AS `%s`",
					$by['sql_select'],
					$db->escape($by['key_select'])
				),
				$query_parts['join'],
				($by['sql_join'] ?? null) ?: '',
				$query_parts['where'],
				$db->escape($by['key_select']),
				$limit_desc ? 'DESC' : 'ASC',
				$limit
			);
			
			try {
				$results = $db->GetArrayReader($sql, $chart_model['timeout']);
			} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
				$error = sprintf('Query timed out (%d ms)', $chart_model['timeout']);
				return false;
			}
			
			if(!is_array($results)) {
				$error = sprintf("The `%s` field does not support subtotals.", $by['key_query']);
				return false;
			}
			
			$values = array_column($results, $by['key_select']);
			
			if(!empty($values)) {
				$sql_where .= sprintf(" AND %s IN (%s)",
					$by['sql_select'],
					implode(',', array_map(function($v) use ($db) {
						return $db->qstr($v);
					}, $values))
				);
				
			} else {
				$sql_where .= ' AND 0';
			}
		}
		
		if(!$by_fields) {
			$sql = sprintf("SELECT %s AS hits %s %s",
				$func,
				$query_parts['join'],
				$sql_where
			);
			
		} else {
			$sql = sprintf("SELECT %s AS hits, %s %s %s %s GROUP BY %s",
				$func,
				implode(', ', array_map(function($e) use ($db) {
					return sprintf("%s AS `%s`",
						$e['sql_select'],
						$db->escape($e['key_select'])
					);
				}, $by_fields)),
				$query_parts['join'],
				implode(' ', array_map(function($e) use ($db) {
					return $e['sql_join'] ?? null;
				}, $by_fields)),
				$sql_where,
				implode(', ', array_map(function($e) use ($db) {
					return sprintf("`%s`",
						$db->escape($e['key_select'])
					);
				}, $by_fields))
			);
		}
		
		if($by_fields && array_key_exists('group', $chart_model) && is_array($chart_model['group']) && !empty($chart_model['group'])) {
			$group_func = @$chart_model['group_function'] ?: 'sum';
			
			if('distinct' == $group_func) {
				$group_func_select = 'COUNT(DISTINCT _stats.hits)';
			} else {
				$group_func_select = sprintf('%s(_stats.hits)', $func_map[$group_func]);
			}
			
			$outer_sql = sprintf("SELECT %s AS hits, %s FROM (%s) AS _stats GROUP BY %s",
				$group_func_select,
				implode(', ', array_map(function($e) use ($db) {
					return sprintf("_stats.`%s`",
						$db->escape($e['key_select'])
					);
				}, $chart_model['group'])),
				$sql,
				implode(', ', array_map(function($e) use ($db) {
					return sprintf("_stats.`%s`",
						$db->escape($e['key_select'])
					);
				}, $chart_model['group']))
			);
			
			$by_fields = $chart_model['group'];
			
			if($func == 'COUNT(*)') {
				$chart_model['by'] = $by_fields;
				
			} else {
				$chart_model['by'] = array_merge($by_fields, array_slice($chart_model['by'],-1,1));
			}
			
			$sql = $outer_sql;
		}
		
		try {
			if(false == ($rows = $db->GetArrayReader($sql, $chart_model['timeout'])))
				$rows = [];
			
		} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
			$error = sprintf('Query timed out (%d ms)', $chart_model['timeout']);
			return false;
		}
			
		$labels = [];
		$queries = [];
		
		foreach($chart_model['by'] as $by) {
			$key_select = $by['key_select'];
			$values = array_column($rows, $key_select);
			
			// Re-label
			switch($by['type']) {
				case Model_CustomField::TYPE_CURRENCY:
					$currency_id = $by['type_options']['currency_id'] ?? null;
					
					if(!$currency_id || false == ($currency = DAO_Currency::get($currency_id)))
						break;
					
					foreach($values as $row_idx => $value) {
						$value = $currency->format($value, false, '.', '');
						$values[$row_idx] = $value;
						$rows[$row_idx][$key_select] = $value;
					}
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					$decimal_at = $by['type_options']['decimal_at'] ?? null;
					
					foreach($values as $row_idx => $value) {
						$value = DevblocksPlatform::strFormatDecimal($value, $decimal_at, '.', '');
						$values[$row_idx] = $value;
						$rows[$row_idx][$key_select] = $value;
					}
					break;
					
				case Model_CustomField::TYPE_WORKER:
					if(!DevblocksPlatform::strEndsWith($by['key_query'], '.id')) {
						$worker_names = DAO_Worker::getNames(false);
						
						foreach ($values as $row_idx => $value) {
							$rows[$row_idx][$key_select] = $worker_names[$value] ?? $value;
						}
					}
					break;
			}
			
			// Labels
			if(array_key_exists('get_labels_callback', $by) && is_callable($by['get_labels_callback'])) {
				$labels[$key_select] = $by['get_labels_callback']($values);
			} else {
				if(false !== ($by_labels = $search_class::getLabelsForKeyValues($key_select, $values)))
					$labels[$key_select] = $by_labels;
			}
			
			// Queries
			foreach($values as $value) {
				@$filter = $by['key_query'] . ':%s';
				
				// [TODO] Implement other types
				// [TODO] Can this be abstracted?
				// [TODO] Sometimes we filter by raw value and sometimes by label
				switch($by['type']) {
					case 'context':
						if(array_key_exists('get_value_as_filter_callback',$by) && is_callable($by['get_value_as_filter_callback'])) {
							$query_value = $by['get_value_as_filter_callback']($value, $filter);
						} else {
							$query_value = $value;
						}
						$queries[$by['key_select']][$value] = sprintf($filter, $query_value);
						break;
					
					case 'text':
						if(array_key_exists('get_value_as_filter_callback',$by) && is_callable($by['get_value_as_filter_callback'])) {
							$query_value = $by['get_value_as_filter_callback']($value, $filter);
						} else {
							$query_value = $value;
						}
						
						if(array_key_exists('timestamp_step', $by)) {
							$from_date = $to_date = $query_value;
							
							switch($by['timestamp_step']) {
								case 'hour':
									$to_date = str_replace(':00',':59:59',$from_date);
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
								case 'hourofday':
									$query_value = sprintf('(times:"%s-%s")', $from_date, str_replace(':00',':59:59',$from_date));
									break;
								case 'hourofdayofweek':
									list($days, $hours) = explode(' ', $from_date);
									$query_value = sprintf('(days:%s times:"%s-%s")', $days, $hours, str_replace(':00',':59:59',$hours));
									break;
								case 'day':
									$to_date .= ' 23:59:59';
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
								case 'dayofmonth':
									$query_value = sprintf('(dom:%d)', $from_date);
									break;
								case 'dayofweek':
									$query_value = sprintf('(days:%s)', $from_date);
									break;
								case 'week':
								case 'week-mon':
								case 'week-monday':
									$to_date = date('Y-m-d', strtotime('+6 days', strtotime($to_date))) . ' 23:59:59';
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
								case 'week-sun':
								case 'week-sunday':
									$to_date = date('Y-m-d', strtotime('+6 days', strtotime($to_date))) . ' 23:59:59';
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
								case 'weekofyear':
									$query_value = sprintf('(weeks:%s)', $from_date);
									break;
								case 'month':
									$from_date .= '-01';
									$to_date = date('Y-m-d', strtotime('last day of this month', strtotime($to_date))) . ' 23:59:59';
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
								case 'monthofyear':
									$query_value = sprintf('(months:%s)', $from_date);
									break;
								case 'year':
									$from_date .= '-01-01';
									$to_date .= '-12-31 23:59:59';
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
								default:
									$query_value = '"' . $from_date . ' to ' . $to_date . '"';
									break;
							}
							
						} else if(is_numeric($query_value)) {
							if(false !== strpos($query_value, '.')) {
								$query_value = floatval($query_value);
							} else {
								$query_value = intval($query_value);
							}
						} else if(false !== strpos(strval($query_value), ' ')) {
							// [TODO] This needs to be more type aware
							$query_value = '"' . $query_value . '"';
						}
						
						$queries[$by['key_select']][$value] = sprintf($filter, $query_value);
						break;
					
					case 'worker':
					default:
						if(array_key_exists('get_value_as_filter_callback',$by) && is_callable($by['get_value_as_filter_callback'])) {
							$query_value = $by['get_value_as_filter_callback']($value, $filter);
						} else {
							$query_value = $value;
						}
						
						if(is_numeric($query_value)) {
							if(false !== strpos($query_value, '.')) {
								$query_value = floatval($query_value);
							} else {
								$query_value = intval($query_value);
							}
						} else if(false !== strpos($query_value, ' ')) {
							// [TODO] This needs to be more type aware
							$query_value = '"' . $query_value . '"';
						}
						
						$queries[$by['key_select']][$value] = sprintf($filter, $query_value);
						break;
				}
			}
		}

		$response = ['children' => []];
		$last_k = (array_slice(array_keys($rows[0] ?? []), -1, 1)[0] ?? null) ?: [];
		
		foreach($rows as $row) {
			$ptr =& $response['children'];
			
			if(($chart_model['query'] ?? null)) {
				$query = ['(' . $chart_model['query'] . ')'];
			} else {
				$query = [];
			}
			
			if(!$by_fields && 1 == count($row) && !in_array($chart_model['function'], ['','count'])) {
				$data = [
					'name' => $chart_model['function'],
					'value' => $row['hits'],
					'hits' => $row['hits'],
				];
				
				$ptr[] = $data;
				
			} else {
				foreach(array_slice(array_keys($row),1) as $k) {
					$label = (array_key_exists($k, $labels) && array_key_exists($row[$k], $labels[$k])) ? $labels[$k][$row[$k]] : $row[$k];
					$query[] = array_key_exists($row[$k], $queries[$k]) ? $queries[$k][$row[$k]] : '';
					
					$data = [
						'name' => $label,
						'value' => $row[$k],
						'hits' => $row['hits'],
						'query' => implode(' ', $query),
					];
					
					$ptr[] = $data;
					end($ptr);
					$ptr =& $ptr[key($ptr)];
					
					if($k != $last_k) {
						if(!array_key_exists('children', $ptr))
							$ptr['children'] = [];
						
						$ptr =& $ptr['children'];
					}
				}
			}
		}
		
		if(array_key_exists('metric', $chart_model)) {
			$tpl_builder = DevblocksPlatform::services()->templateBuilder();
			
			$metric_template = sprintf('{{%s}}',
				$chart_model['metric']
			);
			
			array_walk_recursive($response['children'], function(&$v, $k) use ($tpl_builder, $metric_template) {
				if($k != 'hits')
					return;
				
				$out = $tpl_builder->build($metric_template, [
					'x' => $v,
				]);
				
				if(false === $out || !is_numeric($out)) {
					$v = 0;
					return;
				}
				
				$v = floatval($out);
			});
		}
		
		if(array_key_exists('timezone_location', $chart_model)) {
			array_walk_recursive($response['children'], function (&$v, $k) use ($chart_model) {
				if ($k != 'query')
					return;
				
				if (!empty($v))
					$v .= ' ';
				
				$v .= sprintf('set.timezone:%s', $chart_model['timezone_location']);
			});
		}
		
		// [TODO] Sort by label/metric?
		// [TODO] Handle currency/decimal fields
		
		$sort_children = function(&$children) use (&$sort_children) {
			usort($children, function($a, $b) {
				if($a['hits'] == $b['hits'])
					return 0;
				
				return $a['hits'] < $b['hits'] ? 1 : -1;
			});
			
			foreach($children as &$child) {
				if(array_key_exists('children', $child))
					$sort_children($child['children']);
			}
		};
		
		$sort_children($response['children']);
		
		$format = ($chart_model['format'] ?? null) ?: 'tree';
		
		switch($format) {
			case 'categories':
				return $this->_formatDataAsCategories($response, $chart_model);
				
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($response, $chart_model);
				
			case 'pie':
				return $this->_formatDataAsPie($response, $chart_model);
				
			case 'table':
				return $this->_formatDataAsTable($response, $chart_model);
				
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($response, $chart_model);
				
			case 'tree':
				return $this->_formatDataAsTree($response, $chart_model);
				
			default:
				$error = sprintf("'format:%s' is not valid for `type:%s`. Must be one of: categories, pie, table, timeseries, tree",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsTree($response, array $chart_model=[]) {
		return [
			'data' => $response, 
			'_' => [
				'type' => 'worklist.subtotals',
				'context' => @$chart_model['context'],
				'format' => 'tree',
			]
		];
	}
	
	function _formatDataAsCategories($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		// Do we have nested data?
		$nested = (bool)($response['children'][0]['children'] ?? null);
		$series_meta = [];
		
		if($nested) {
			$xvalues = [];
			
			$output = [
				['label'],
			];
			
			$parents = array_column($response['children'], 'name');
			
			foreach($response['children'] as $parent) {
				$series_meta[$parent['name']] = [
					'_key' => $parent['value'],
					'_query' => $parent['query'],
				];
				
				foreach($parent['children'] as $child) {
					$xvalues[$child['name']] = array_fill_keys($parents, 0);
				}
			}
			
			foreach($response['children'] as $parent) {
				foreach($parent['children'] as $child) {
					$xvalues[$child['name']][$parent['name']] = $child['hits'];
					$series_meta[$parent['name']][$child['name']] = [
						'key' => $child['value'],
						'query' => $child['query'],
					];
				}
			}
			
			$output[0] = array_merge($output[0], array_keys($series_meta));
			
			foreach($xvalues as $child_name => $parents) {
				$values = array_values($parents);
				array_unshift($values, $child_name);
				$output[] = $values;
			}
			
		} else {
			$output = [
				['label'], ['hits']
			];
			
			$series_meta['hits'] = [];
			
			foreach($response['children'] as $subtotal) {
				$output[0][] = $subtotal['name'];
				$output[1][] = $subtotal['hits'];
				
				$series_meta['hits'][$subtotal['name']] = [
					'key' => $subtotal['value'],
					'query' => $subtotal['query'],
				];
			}
		}
		
		return ['data' => $output, '_' => [
			'type' => 'worklist.subtotals',
			'context' => @$chart_model['context'],
			'stacked' => $nested,
			'format' => 'categories',
			'format_params' => [
				'xaxis_key' => 'label',
			],
			'series' => $series_meta,
		]];
	}
	
	function _formatDataAsDictionaries($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		$rows = [];
		
		$output = [
			'columns' => [],
			'rows' => &$rows,
		];
		
		foreach($chart_model['by'] as $by) {
			$label = null;
			@$type = $by['type'] ?: DevblocksSearchCriteria::TYPE_TEXT;
			
			switch($type) {
				case DevblocksSearchCriteria::TYPE_CONTEXT:
					$type_options = [];
					
					if(array_key_exists('type_options', $by)) {
						if(array_key_exists('context', $by['type_options']))
							$type_options['context'] = $by['type_options']['context'];
						
						if(array_key_exists('context_key', $by['type_options']))
							$type_options['context_key'] = $by['type_options']['context_key'];
					
						$type_options['context_id_key'] = ($by['type_options']['context_id_key'] ?? null) ?: ($by['key_query'] ?? null);
						
					} else {
						$type_options['context_key'] = ($by['key_query'] ?? null) . '__context';
						$type_options['context_id_key'] = $by['key_query'] ?? null;
					}
					
					$output['columns'][$by['key_query'] . '_label'] = [
						'label' => $label . DevblocksPlatform::strTitleCase(@$by['label'] ?: $by['key_query']),
						'type' => $type,
						'type_options' => $type_options,
					];
					break;
					
				case DevblocksSearchCriteria::TYPE_WORKER:
					$output['columns'][$by['key_query'] . '_label'] = [
						'label' => $label . DevblocksPlatform::strTitleCase(@$by['label'] ?: $by['key_query']),
						'type' => $type,
						'type_options' => [
							'context_id_key' => $by['key_query'],
						]
					];
					break;
				
				default:
					$output['columns'][$by['key_query']] = [
						'label' => $label . DevblocksPlatform::strTitleCase(@$by['label'] ?: $by['key_query']),
						'type' => $type,
					];
					break;
			}
		}
		
		// If we're counting, add a 'Count' column to the end
		if(in_array($chart_model['function'],['','count'])) {
			$output['columns']['count'] = [
				'label' => DevblocksPlatform::translateCapitalized('common.count'),
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
			];
		}
		
		$rows = [];
		
		// Build a table recursively from the tree
		$recurse = function($node, $depth=0, $parents=[]) use (&$recurse, $chart_model, &$rows) {
			if(array_key_exists('name', $node))
				$parents[] = &$node;
			
			// If this is a leaf
			if(!array_key_exists('children', $node)) {
				$row = [];
				
				foreach($chart_model['by'] as $column_index => $column) {
					$key_prefix = str_replace('.', '_', $column['key_query']);
					@$type = $column['type'] ?: DevblocksSearchCriteria::TYPE_TEXT;
					$value = null;
					
					if(!in_array($chart_model['function'], ['','count']) && 1 == count($chart_model['by'])) {
						$value = $node['hits'];
						
						$row[$key_prefix] = $value;
						$row[$key_prefix . '_' . 'func'] = $chart_model['function'];
						$rows[] = $row;
						return;
						
					} else if($column_index == $depth) {
						$value = $node['hits'];
						
						if(!array_key_exists($column_index, $parents)) {
							$row[$key_prefix] = $value;
							$row[$key_prefix . '_' . 'func'] = $chart_model['function'];
							$rows[] = $row;
							return;
						}
						
					} else {
						if(array_key_exists($column_index, $parents))
							$value = $parents[$column_index]['name'];
					}
					
					switch($type) {
						case DevblocksSearchCriteria::TYPE_CONTEXT:
							$name = $parents[$column_index]['name'];
							$value = $parents[$column_index]['value'];
							
							if(DevblocksPlatform::strEndsWith($key_prefix, '_id')) {
								$key_prefix = substr($key_prefix, 0, -3) . '_';
							} else if($key_prefix == 'id') {
								$key_prefix = '';
							}
							
							if(!is_numeric($value) && false !== strpos($value,':')) {
								list($context, $context_id) = explode(':', $value);
								$row[$key_prefix . '_context'] = $context;
								$row[$key_prefix . '_id'] = $context_id;
								$row[$key_prefix . '_label'] = $name;
								
							} else {
								$value = intval($value);
								
								if(!array_key_exists('type_options', $column)) {
									if(false !== (strpos($value,':'))) {
										list($context, $context_id) = array_pad(explode(':', $value, 2), 2, null);
										$row[$key_prefix . '_context'] = $context;
										$row[$key_prefix . 'id'] = $context_id;
										$row[$key_prefix . '_label'] = $name;
									}
								} else {
									$row[$key_prefix . 'id'] = $value;
									$row[$key_prefix . '_context'] = $column['type_options']['context'];
									$row[$key_prefix . '_label'] = $name;
								}
							}
							
							break;
							
						case DevblocksSearchCriteria::TYPE_WORKER:
							$name = $parents[$column_index]['name'];
							$value = intval($parents[$column_index]['value']);
							
							if(DevblocksPlatform::strEndsWith($key_prefix, '_id')) {
								$key_prefix = substr($key_prefix, 0, -3) . '_';
							} else if($key_prefix == 'id') {
								$key_prefix = '';
							}
							
							$row[$key_prefix . '_context'] = CerberusContexts::CONTEXT_WORKER;
							$row[$key_prefix . '_label'] = $name;
							$row[$key_prefix . 'id'] = $value;
							break;
							
						default:
							if(array_key_exists($column_index, $parents)) {
								$name = $parents[$column_index]['name'];
								$value = $parents[$column_index]['value'];
								
								$row[$key_prefix] = $value;
								$row[$key_prefix . '_label'] = $name;
								
							} else {
								$row[$key_prefix] = $value;
							}
							break;
					}
				}
				
				if(in_array($chart_model['function'], ['','count'])) {
					$row['count'] = $node['hits'];
					
					if(array_key_exists('query', $node)) {
						$row['count_query_context'] = $chart_model['context'];
						$row['count_query'] = $node['query'];
					}
				}
				
				$rows[] = DevblocksDictionaryDelegate::instance($row);
				return;
			}
			
			foreach($node['children'] as $child) {
				$recurse($child, $depth+1, $parents);
			}
		};
		
		$recurse($response, 0);
		
		if(array_key_exists('expand', $chart_model))
		foreach($chart_model['expand'] as $expand_token)
			DevblocksDictionaryDelegate::bulkLazyLoad($output['rows'], $expand_token, true);
		
		return ['data' => $output['rows'], '_' => [
			'type' => 'worklist.subtotals',
			'context' => @$chart_model['context'],
			'format' => 'dictionaries',
		]];
	}
	
	function _formatDataAsPie($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		$output = [];
		$series_meta = [];
		
		foreach($response['children'] as $subtotal) {
			$output[] = [$subtotal['name'], $subtotal['hits']];
			
			$series_meta[$subtotal['name']] = [
				'key' => $subtotal['value'],
				'query' => @$subtotal['query'] ?: '',
			];
		}
		
		return [
			'data' => $output,
			'_' => [
				'type' => 'worklist.subtotals',
				'context' => @$chart_model['context'],
				'format' => 'pie',
				'series' => $series_meta,
			]
		];
	}
	
	function _formatDataAsTable($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		$rows = [];
		
		$output = [
			'columns' => [],
			'rows' => &$rows,
		];
		
		$by_len = count($chart_model['by']);
		foreach($chart_model['by'] as $by_index => $by) {
			$label = null;
			
			// If this is the last column
			if($by_index+1 == $by_len) {
				$func_label = $chart_model['function'] ?? null;
				
				switch($chart_model['function']) {
					case 'avg':
					case 'average':
						$func_label = 'Avg.';
						break;
						
					case 'distinct':
						$func_label = 'Distinct';
						break;
						
					case 'max':
						$func_label = 'Max.';
						break;
						
					case 'min':
						$func_label = 'Min.';
						break;
						
					case 'sum':
						$func_label = 'Total';
						break;
						
					default:
						$func_label = '';
						break;
				}
				
				$label = $func_label ? ($func_label . ' ') : '';
			}
			
			@$type = $by['type'] ?: DevblocksSearchCriteria::TYPE_TEXT;
			
			switch($type) {
				case DevblocksSearchCriteria::TYPE_CONTEXT:
					$type_options = [];
					
					if(array_key_exists('type_options', $by)) {
						if(array_key_exists('context', $by['type_options']))
							$type_options['context'] = $by['type_options']['context'];
						
						if(array_key_exists('context_key', $by['type_options']))
							$type_options['context_key'] = $by['type_options']['context_key'];
					
						$type_options['context_id_key'] = ($by['type_options']['context_id_key'] ?? null) ?: ($by['key_query'] ?? null);
						
					} else {
						$type_options['context_key'] = ($by['key_query'] ?? null) . '__context';
						$type_options['context_id_key'] = $by['key_query'] ?? null;
					}
					
					$output['columns'][$by['key_query'] . '_label'] = [
						'label' => $label . DevblocksPlatform::strTitleCase(($by['label'] ?? null) ?: ($by['key_query'] ?? null)),
						'type' => $type,
						'type_options' => $type_options,
					];
					break;
					
				case DevblocksSearchCriteria::TYPE_WORKER:
					$output['columns'][$by['key_query'] . '_label'] = [
						'label' => $label . DevblocksPlatform::strTitleCase(($by['label'] ?? null) ?: ($by['key_query'] ?? null)),
						'type' => $type,
						'type_options' => [
							'context_id_key' => $by['key_query'],
						]
					];
					break;
				
				default:
					$output['columns'][$by['key_query']] = [
						'label' => $label . DevblocksPlatform::strTitleCase(($by['label'] ?? null) ?: ($by['key_query'] ?? null)),
						'type' => $type,
					];
					break;
			}
		}
		
		// If we're counting, add a 'Count' column to the end
		if(in_array($chart_model['function'],['','count'])) {
			$output['columns']['count'] = [
				'label' => DevblocksPlatform::translateCapitalized('common.count'),
				'type' => DevblocksSearchCriteria::TYPE_NUMBER,
			];
		}
		
		$rows = [];
		
		// Build a table recursively from the tree
		$recurse = function($node, $depth=0, $parents=[]) use (&$recurse, $chart_model, &$rows) {
			if(array_key_exists('name', $node))
				$parents[] = &$node;
			
			// If this is a leaf
			if(!array_key_exists('children', $node)) {
				$row = [];
				
				// If a top-level aggregation
				foreach($chart_model['by'] as $column_index => $column) {
					@$type = $column['type'] ?: DevblocksSearchCriteria::TYPE_TEXT;
					
					$value = null;
					
					if(!in_array($chart_model['function'], ['','count']) && 1 == count($chart_model['by'])) {
						$value = $node['hits'];
						
					} else if($column_index == $depth) {
						$value = $node['hits'];
						
					} else {
						if(array_key_exists($column_index, $parents))
							$value = $parents[$column_index]['name'];
					}
					
					switch ($type) {
						case DevblocksSearchCriteria::TYPE_CONTEXT:
							if(!array_key_exists($column_index, $parents))
								break;
							
							$value = $parents[$column_index]['value'];
							
							if(!array_key_exists('type_options', $column)) {
								if(false !== (strpos($value, ':'))) {
									list($context, $context_id) = array_pad(explode(':', $value, 2), 2, null);
									$row[$column['key_query'] . '__context'] = $context;
									$row[$column['key_query']] = $context_id;
									$row[$column['key_query'] . '_label'] = $parents[$column_index]['name'];
								}
							} else {
								$row[$column['key_query']] = $value;
								$row[$column['key_query'] . '_label'] = $parents[$column_index]['name'];
							}
							break;
						
						case DevblocksSearchCriteria::TYPE_WORKER:
							if(!array_key_exists($column_index, $parents))
								break;
							
							$row[$column['key_query'] . '_label'] = $parents[$column_index]['name'];
							$row[$column['key_query']] = $parents[$column_index]['value'];
							break;
						
						default:
							$row[$column['key_query']] = $value;
							break;
					}
				}
				
				if(in_array($chart_model['function'], ['','count'])) {
					$row['count'] = $node['hits'];
				}
				
				if(array_key_exists('query', $node)) {
					$row['_types'] = [
						'count' => [
							'type' => DevblocksSearchCriteria::TYPE_SEARCH,
							'options' => [
								'context' => $chart_model['context'],
								'query' => $node['query']
							],
						]
					];
				}
				
				$rows[] = $row;
				return;
			}
			
			foreach($node['children'] as $child) {
				$recurse($child, $depth+1, $parents);
			}
		};
		
		$recurse($response, 0);
		
		return ['data' => $output, '_' => [
			'type' => 'worklist.subtotals',
			'context' => @$chart_model['context'],
			'format' => 'table',
		]];
	}
	
	function _formatDataAsTimeSeries($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		@$xaxis_format = @$chart_model['by'][0]['timestamp_format'];
		@$xaxis_step = @$chart_model['by'][0]['timestamp_step'];
		
		$x_series = array_column($response['children'], 'name');
		
		$x_series = DevblocksPlatform::services()->date()->formatTimestamps(
			DevblocksPlatform::dateLerpArray($x_series, $xaxis_step),
			DevblocksPlatform::services()->date()->formatByUnit($xaxis_step)
		);
		$x_series = array_fill_keys($x_series, 0);
		
		$output = [ 'ts' => array_map(function($d) { return strval($d); }, array_keys($x_series)) ];
		
		$series_meta = [];
		
		foreach($response['children'] as $date) {
			// If we have multiple series, use them
			if(array_key_exists('children', $date)) {
				$children = $date['children'];
				
			// Synthesize a single series from aggregate (group:) data
			} else {
				$children = [
					[
						'name' => $chart_model['group_function'] ?? 'count',
						'value' => $date['value'],
						'query' => $date['query'] ?? null,
						'hits' => $date['hits'],
						'children' => [
							$date,
						],
					]
				];
			}
			
			foreach($children as $series) {
				if(!array_key_exists($series['name'], $output))
					$output[$series['name']] = $x_series;
				
				$output[$series['name']][$date['name']] = $series['hits'];
				
				if(!array_key_exists($series['name'], $series_meta))
					$series_meta[$series['name']] = array_fill_keys(array_keys($x_series), []);
				
				$series_meta[$series['name']][$date['name']] = [
					'key' => $series['value'],
					'query' => $series['query'],
				];
			}
		}
		
		foreach(array_keys($output) as $series_key) {
			$output[$series_key] = array_values($output[$series_key]);
		}
		
		return ['data' => $output, '_' => [
			'type' => 'worklist.subtotals',
			'context' => @$chart_model['context'],
			'format' => 'timeseries',
			'format_params' => [
				'xaxis_key' => 'ts',
				'xaxis_step' => $xaxis_step,
				'xaxis_format' => $xaxis_format,
			],
			'series' => $series_meta,
		]];
	}
};