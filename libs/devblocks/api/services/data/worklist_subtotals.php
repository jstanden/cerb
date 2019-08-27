<?php
class _DevblocksDataProviderWorklistSubtotals extends _DevblocksDataProvider {
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.subtotals',
			'function' => 'count',
		];
		
		$subtotals_context = null;
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				// Do nothing
				
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
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Sanitize
		
		if(!isset($chart_model['by'])) {
			$error = "The `by:` field is required.";
			return false;
		}
		
		if(!$subtotals_context) {
			$error = "The `of:` field is not a valid context type.";
			return false;
		}
		
		// Convert 'by:' keys to fields
		
		$dao_class = $subtotals_context->getDaoClass();
		$search_class = $subtotals_context->getSearchClass();
		$view = $subtotals_context->getTempView();
		
		$view->addParamsRequiredWithQuickSearch(@$chart_model['query_required']);
		$view->addParamsWithQuickSearch(@$chart_model['query']);
		
		$query_fields = $view->getQuickSearchFields();
		$search_fields = $view->getFields();
		
		if($chart_model['by']) {
			$subtotal_by = $chart_model['by'];
			unset($chart_model['by']);
			
			if(!is_array($subtotal_by))
				$subtotal_by = [$subtotal_by];
			
			$group_by = @$chart_model['group'] ?: [];
			unset($chart_model['group']);
			
			if(!is_array($group_by))
				$group_by = [$group_by];
				
			foreach($subtotal_by as $by) {
				// Handle limits and orders
				@list($by, $limit) = explode('~', $by, 2);
				
				if(false == ($subtotal_field = $search_class::getFieldForSubtotalKey($by, $subtotals_context->id, $query_fields, $search_fields, $search_class::getPrimaryKey())))
					continue;
				
				// If it's a date time-step, allow the full range
				if(is_null($limit) && array_key_exists('timestamp_step', $subtotal_field)) {
					$limit = '250';
				}
				
				@$limit_desc = DevblocksPlatform::strStartsWith($limit, '-') ? false : true;
				@$limit = DevblocksPlatform::intClamp(abs($limit), 0, 2000) ?: 25;
				
				$subtotal_field['limit'] = $limit;
				$subtotal_field['limit_desc'] = $limit_desc;
				
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
			'sum' => 'SUM',
			'min' => 'MIN',
			'max' => 'MAX',
		];
		
		if(array_key_exists($chart_model['function'], $func_map)) {
			$func_field = array_pop($by_fields);
			$func = sprintf('%s(%s)', $func_map[$chart_model['function']], $func_field['sql_select']);
		}
		
		// Pre-filter
		
		$sql_where = $query_parts['where'];
		
		foreach($by_fields as $by) {
			$limit = DevblocksPlatform::intClamp($by['limit'], 0, 2000) ?: 25;
			@$limit_desc = $by['limit_desc'];
			
			$sql = sprintf("SELECT COUNT(*) AS hits, %s %s %s %s GROUP BY `%s` ORDER BY hits %s LIMIT %d",
				sprintf("%s AS `%s`",
					$by['sql_select'],
					$db->escape($by['key_select'])
				),
				$query_parts['join'],
				@$by['sql_join'] ?: '',
				$query_parts['where'],
				$db->escape($by['key_select']),
				$limit_desc ? 'DESC' : 'ASC',
				$limit
			);
			
			$results = $db->GetArraySlave($sql);
			
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
		
		$response = [];
		
		if(!empty($by_fields)) {
			// [TODO] Rename `hits` to `metric`
			// [TODO] In `function` also accept data type (int, float, etc)
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
					return @$e['sql_join'];
				}, $by_fields)),
				$sql_where,
				implode(', ', array_map(function($e) use ($db) {
					return sprintf("`%s`",
						$db->escape($e['key_select'])
					);
				}, $by_fields))
			);
			
			if(array_key_exists('group', $chart_model) && is_array($chart_model['group']) && !empty($chart_model['group'])) {
				$group_func = @$chart_model['group_function'] ?: 'sum';
				$group_func_select = sprintf('%s(_stats.hits)', $func_map[$group_func]);
				
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
			
			if(false == ($rows = $db->GetArraySlave($sql)))
				return [];
			
		} else {
			$rows = [];
		}
		
		$labels = [];
		$queries = [];
		
		foreach($chart_model['by'] as $by) {
			$key_select = $by['key_select'];
			$values = array_column($rows, $key_select);
			
			switch($by['type']) {
				case Model_CustomField::TYPE_CURRENCY:
					@$currency_id = $by['type_options']['currency_id'];
					
					if(!$currency_id || false == ($currency = DAO_Currency::get($currency_id)))
						break;
					
					foreach($values as $row_idx => $value) {
						$value = $currency->format($value, false, '.', '');
						$values[$row_idx] = $value;
						$rows[$row_idx][$key_select] = $value;
					}
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					@$decimal_at = $by['type_options']['decimal_at'];
					
					foreach($values as $row_idx => $value) {
						$value = DevblocksPlatform::strFormatDecimal($value, $decimal_at, '.', '');
						$values[$row_idx] = $value;
						$rows[$row_idx][$key_select] = $value;
					}
					break;
			}
			
			if(false !== ($by_labels = $search_class::getLabelsForKeyValues($key_select, $values)))
				$labels[$key_select] = $by_labels;
			
			foreach($values as $value) {
				@$filter = $by['key_query'] . ':%s';
				
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
								case 'day':
									$to_date .= ' 23:59:59';
									break;
								case 'week':
								case 'week-mon':
								case 'week-monday':
									$to_date = date('Y-m-d', strtotime('+6 days', strtotime($to_date))) . ' 23:59:59';
									break;
								case 'week-sun':
								case 'week-sunday':
									$to_date = date('Y-m-d', strtotime('+6 days', strtotime($to_date))) . ' 23:59:59';
									break;
								case 'month':
									$from_date .= '-01';
									$to_date = date('Y-m-d', strtotime('last day of this month', strtotime($to_date))) . ' 23:59:59';
									break;
								case 'year':
									$from_date .= '-01-01';
									$to_date .= '-12-31 23:59:59';
									break;
							}
							
							$query_value = '"' . $from_date . ' to ' . $to_date . '"';
							
						} else if(is_numeric($query_value)) {
							if(false !== strpos($query_value, '.')) {
								$query_value = floatval($query_value);
							} else {
								$query_value = intval($query_value);
							}
						} else if(false !== strpos($query_value, ' ')) {
							$query_value = '"' . $query_value . '"';
						}
						
						$queries[$by['key_select']][$value] = sprintf($filter, $query_value);
						break;
					
					case 'worker':
					default:
						if(array_key_exists('get_value_as_filter_callback',$by) && is_callable($by['get_value_as_filter_callback'])) {
							$query_value = $by['get_value_as_filter_callback']($value);
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
							$query_value = '"' . $query_value . '"';
						}
						
						$queries[$by['key_select']][$value] = sprintf($filter, $query_value);
						break;
				}
			}
		}
		
		$response = ['children' => []];
		@$last_k = array_slice(array_keys($rows[0]), -1, 1)[0] ?: [];
		
		foreach($rows as $row) {
			$ptr =& $response['children'];
			
			if(@$chart_model['query']) {
				$query = ['(' . $chart_model['query'] . ')'];
			} else {
				$query = [];
			}
			
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
		
		// [TODO] Sort by label/metric?
		// [TODO] Handle currency/decimal fields
		
		$sort_children = null;
		
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
		
		@$format = $chart_model['format'] ?: 'tree';
		
		switch($format) {
			case 'categories':
				return $this->_formatDataAsCategories($response, $chart_model);
				break;
				
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($response, $chart_model);
				break;
				
			case 'pie':
				return $this->_formatDataAsPie($response, $chart_model);
				break;
				
			case 'table':
				return $this->_formatDataAsTable($response, $chart_model);
				break;
				
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($response, $chart_model);
				break;
				
			case 'tree':
				return $this->_formatDataAsTree($response, $chart_model);
				break;
				
			default:
				$error = sprintf("'format:%s' is not valid for `type:%s`. Must be one of: categories, pie, table, timeseries, tree",
					$format,
					$chart_model['type']
				);
				return false;
				break;
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
		$nested = @$response['children'][0]['children'] ? true : false;
		$series_meta = [];
		
		if($nested) {
			$parents = [];
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
					
						@$type_options['context_id_key'] = $by['type_options']['context_id_key'] ?: $by['key_query'];
						
					} else {
						@$type_options['context_key'] = $by['key_query'] . '__context';
						@$type_options['context_id_key'] = $by['key_query'];
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
		$recurse = null;
		
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
					
					if($column_index == $depth) {
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
							$name = $node['name'];
							$value = intval($node['value']);
							
							if(DevblocksPlatform::strEndsWith($key_prefix, '_id')) {
								$key_prefix = substr($key_prefix, 0, -3) . '_';
							} else if($key_prefix == 'id') {
								$key_prefix = '';
							}
							
							if(!array_key_exists('type_options', $column)) {
								if(false !== (strpos($value,':'))) {
									@list($context, $context_id) = explode(':', $value, 2);
									$row[$key_prefix . '_context'] = $context;
									$row[$key_prefix . 'id'] = $context_id;
									$row[$key_prefix . '_label'] = $name;
								}
							} else {
								$row[$key_prefix . 'id'] = $value;
								$row[$key_prefix . '_context'] = $column['type_options']['context'];
								$row[$key_prefix . '_label'] = $name;
							}
							
							break;
							
						case DevblocksSearchCriteria::TYPE_WORKER:
							$name = $node['name'];
							$value = intval($node['value']);
							
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
								$name = $node['name'];
								$value = $node['value'];
								
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
				@$func_label = $chart_model['function'];
				
				switch($chart_model['function']) {
					case 'avg':
					case 'average':
						$func_label = 'Avg.';
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
					
						@$type_options['context_id_key'] = $by['type_options']['context_id_key'] ?: $by['key_query'];
						
					} else {
						@$type_options['context_key'] = $by['key_query'] . '__context';
						@$type_options['context_id_key'] = $by['key_query'];
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
		$recurse = null;
		
		// Build a table recursively from the tree
		$recurse = function($node, $depth=0, $parents=[]) use (&$recurse, $chart_model, &$rows) {
			if(array_key_exists('name', $node))
				$parents[] = &$node;
			
			// If this is a leaf
			if(!array_key_exists('children', $node)) {
				$row = [];
				
				foreach($chart_model['by'] as $column_index => $column) {
					@$type = $column['type'] ?: DevblocksSearchCriteria::TYPE_TEXT;
					
					if($column_index == $depth) {
						$value = $node['hits'];
						
					} else {
						if(array_key_exists($column_index, $parents))
							$value = $parents[$column_index]['name'];
					}
					
					switch($type) {
						case DevblocksSearchCriteria::TYPE_CONTEXT:
							$value = $parents[$column_index]['value'];
							
							if(!array_key_exists('type_options', $column)) {
								if(false !== (strpos($value,':'))) {
									@list($context, $context_id) = explode(':', $value, 2);
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
		$x_series = DevblocksPlatform::dateLerpArray($x_series, $xaxis_step, $xaxis_format);
		$x_series = array_fill_keys($x_series, 0);
		
		$output = [ 'ts' => array_map(function($d) { return strval($d); }, array_keys($x_series)) ];
		
		$series_meta = [];
		
		foreach($response['children'] as $date) {
			if(!isset($date['children']))
				continue;
			
			foreach($date['children'] as $series) {
				if(!isset($output[$series['name']]))
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