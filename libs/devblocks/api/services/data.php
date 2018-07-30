<?php
abstract class _DevblocksDataProvider {
	abstract function getData($query, $chart_fields, array $options=[]);
}

class _DevblocksDataProviderWorklistMetrics extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.metrics',
			'values' => [],
			'format' => 'table',
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = DevblocksPlatform::strLower($value);
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'values.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$series_model = [
					'id' => $series_id,
					'label' => DevblocksPlatform::strTitleCase(str_replace('_',' ',$series_id)),
					'functions' => ['count'],
				];
				
				$series_context = null;
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if(false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
					} else if($series_field->key == 'functions') {
						CerbQuickSearchLexer::getOperArrayFromTokens($series_field->tokens, $oper, $values);
						$series_model['functions'] = $values;
					
					} else if($series_field->key == 'function') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['functions'] = [$value];
						
					} else if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
						
					} else if($series_field->key == 'field') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['field'] = $value;
						
					} else if($series_field->key == 'query') {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query'] = $data_query;
					}
				}
				
				// Convert series x/y to SearchFields_* using context
				
				if($series_context) {
					$view_class = $series_context->getViewClass();
					$view = new $view_class();
					$search_class = $series_context->getSearchClass();
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
					if(array_key_exists('field', $series_model)) {
						if(false == ($subtotal_field = $search_class::getFieldForSubtotalKey($series_model['field'], $series_model['context'], $query_fields, $search_fields, $search_class::getPrimaryKey()))) {
							unset($series_model['field']);
							continue;
						}
						
						$series_model['field'] = $subtotal_field;
					}
				}
				
				$function_count = count($series_model['functions']);
				
				// Synthesize series from functions
				foreach($series_model['functions'] as $function) {
					$function = DevblocksPlatform::strLower($function);
					
					$series = $series_model;
					unset($series['functions']);
					$series['function'] = $function;
					
					// Make a unique series name if we expanded it
					if($function_count > 1) {
						$series['id'] = $function . '_' . $series['id'];
						
						$label = $series['label'];
						
						switch($function) {
							default:
							case 'avg':
							case 'average':
								$label = 'Avg. ' . $label;
								break;
								
							case 'count':
								$label = '# ' . $label;
								break;
								
							case 'max':
								$label = 'Max. ' . $label;
								break;
								
							case 'min':
								$label = 'Min. ' . $label;
								break;
								
							case 'sum':
								$label = 'Total ' . $label;
								break;
						}
						
						$series['label'] = $label;
					}
					
					$chart_model['values'][] = $series;
				}
			}
		}
		
		// Fetch data for each series
		
		if(isset($chart_model['values']))
		foreach($chart_model['values'] as $series_idx => $series) {
			if(!isset($series['context']))
				continue;
			
			$context_ext = Extension_DevblocksContext::get($series['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$view = $context_ext->getSearchView(uniqid());
			$view->setAutoPersist(false);
			$view->addParamsWithQuickSearch($series['query']);
			
			$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
			
			$metric_field = null;
			
			switch($series['function']) {
				case 'average':
				case 'avg':
					$metric_field = sprintf("AVG(%s)",
						Cerb_ORMHelper::escape($series['field']['sql_select'])
					);
					break;
					
				case 'count':
					$metric_field = "COUNT(*)";
					break;
					
				case 'max':
					$metric_field = sprintf("MAX(%s)",
						Cerb_ORMHelper::escape($series['field']['sql_select'])
					);
					break;
					
				case 'min':
					$metric_field = sprintf("MIN(%s)",
						Cerb_ORMHelper::escape($series['field']['sql_select'])
					);
					break;
					
				case 'sum':
					$metric_field = sprintf("SUM(%s)",
						Cerb_ORMHelper::escape($series['field']['sql_select'])
					);
					break;
			}
			
			if(!$metric_field)
				continue;
			
			$sql = sprintf("SELECT %s AS value %s %s",
				$metric_field,
				$query_parts['join'],
				$query_parts['where']
			);
			
			$value = $db->GetOneSlave($sql);
			
			$chart_model['values'][$series_idx]['value'] = $value;
		}
		
		switch($chart_model['format']) {
			default:
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				break;
		}
	}
	
	private function _formatDataAsTable(array $chart_model=[]) {
		$response = [];
		$rows = [];
		
		$table = [
			'columns' => [
				'name' => [
					'label' => 'Metric',
				],
				'value' => [
					'label' => 'Value',
				]
			],
			'rows' => &$rows,
		];
		
		foreach($chart_model['values'] as $value) {
			if(!isset($value['id']) || !isset($value['value']))
				continue;
			
			$type = @$value['field']['type'] ?: DevblocksSearchCriteria::TYPE_TEXT;
			$type_options = @$value['field']['type_options'] ?:[];
			
			$row = [
				'_types' => [
					'value' => [
						'type' => $type,
						'options' => $type_options,
					]
				],
				'id' => $value['id'],
				'name' => $value['label'],
				'value' => $value['value'],
			];
			
			// Override types based on function
			switch($value['function']) {
				case 'count':
					$row['_types']['value'] = [
						'type' => DevblocksSearchCriteria::TYPE_SEARCH,
						'options' => [
							'context' => $value['context'],
							'query' => $value['query'],
						],
					];
					break;
			}
			
			$rows[] = $row;
		}
		
		$response = [
			'data' => $table,
			'_' => [
				'type' => 'worklist.metrics',
				'format' => 'table',
			]
		];
		
		return $response;
	}
}

class _DevblocksDataProviderWorklistXy extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.xy',
			'x' => '',
			'y' => '',
			'series' => [],
			'format' => 'scatterplot',
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'x') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['x'] = $value;
				
			} else if($field->key == 'y') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['y'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_id = explode('.', $field->key, 2)[1];
				
				$series_model = [
					'id' => $series_id,
					'label' => $series_id,
				];
				
				$series_context = null;
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if(false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
					} else if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
						
					} else if($series_field->key == 'x') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['x'] = $value;
						
					} else if($series_field->key == 'y') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y'] = $value;
						
					} else if($series_field->key == 'query') {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query'] = $data_query;
					}
				}
				
				// Convert series x/y to SearchFields_* using context
				
				if($series_context) {
					$view_class = $series_context->getViewClass();
					$view = new $view_class();
					
					$search_class = $series_context->getSearchClass();
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
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
			}
		}
		
		// Fetch data for each series
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series_idx => $series) {
			if(!isset($series['context']))
				continue;
			
			@$query = $series['query'];
			
			$context_ext = Extension_DevblocksContext::get($series['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$search_class = $context_ext->getSearchClass();
			$view = $context_ext->getSearchView(uniqid());
			$view->setAutoPersist(false);
			$view->addParamsWithQuickSearch($query);
			
			$query_parts = $dao_class::getSearchQueryComponents(
				[],
				$view->getParams()
			);
			
			$sort_data = Cerb_ORMHelper::buildSort($view->renderSortBy, $view->renderSortAsc, $view->getFields(), $search_class);
			
			$x_field = $y_field = null;
			
			if(!array_key_exists('x', $series) || !array_key_exists('y', $series))
				continue;
			
			$x_field = $series['x']['sql_select'];
			$y_field = $series['y']['sql_select'];
			
			$sql = sprintf("SELECT %s AS x, %s AS y%s %s %s %s LIMIT %d",
				$x_field,
				$y_field,
				$sort_data['sql_select'] ? sprintf(", %s", $sort_data['sql_select']) : '',
				$query_parts['join'],
				$query_parts['where'],
				$sort_data['sql_sort'],
				$view->renderLimit
			);
			
			if(false == ($results = $db->GetArraySlave($sql)))
				$results = [];
			
			$x_labels = $search_class::getLabelsForKeyValues($series['x']['key_select'], array_column($results, 'x'));
			$y_labels = $search_class::getLabelsForKeyValues($series['y']['key_select'], array_column($results, 'y'));

			$chart_model['series'][$series_idx]['data'] = $results;
			$chart_model['series'][$series_idx]['labels'] = ['x' => $x_labels, 'y' => $y_labels];
		}
		
		// Respond
		
		switch($chart_model['format']) {
			case 'categories':
				return $this->_formatDataAsCategories($chart_model);
				break;
				
			case 'pie':
				return $this->_formatDataAsPie($chart_model);
				break;
				
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				break;
				
			default:
			case 'scatterplot':
				return $this->_formatDataAsScatterplot($chart_model);
				break;
		}
	}
	
	function _formatDataAsCategories($chart_model) {
		$series_data = $chart_model['series'];
		
		$response = [
			['label'],['hits']
		];
		
		foreach($series_data as $series) {
			if(!array_key_exists('data', $series))
				continue;
			
			$x_values = array_column($series['data'], 'x');
			$x_labels = $series['labels']['x'];
			
			$y_values = array_column($series['data'], 'y');
			$y_labels = $series['labels']['y'];
			
			foreach($x_values as $idx => $x) {
				$response[0][] = $x_labels[$x];
				$response[1][] = $y_values[$idx];
			}
		}
		
		return [
			'data' => $response,
			'_' => [
				'type' => 'worklist.scatterplot',
				'format' => 'categories',
			]
		];
	}
	
	function _formatDataAsPie($chart_model) {
		$series_data = $chart_model['series'];
		
		$response = [];
		
		foreach($series_data as $series) {
			if(!array_key_exists('data', $series))
				continue;
			
			$x_values = array_column($series['data'], 'x');
			$x_labels = $series['labels']['x'];
			
			$y_values = array_column($series['data'], 'y');
			$y_labels = $series['labels']['y'];
			
			foreach($x_values as $idx => $x) {
				$response[] = [$x_labels[$x], $y_values[$idx]];
			}
		}
		
		return [
			'data' => $response,
			'_' => [
				'type' => 'worklist.xy',
				'format' => 'pie',
			]
		];
	}
	
	function _formatDataAsScatterplot($chart_model) {
		$series_data = $chart_model['series'];
		
		$response = [];
		
		foreach($series_data as $series) {
			$id = $series['id'];
			
			$x_values = $y_values = [];
			
			if(array_key_exists('data', $series))
			foreach($series['data'] as $data) {
				$x_values[] = $data['x'];
				$y_values[] = $data['y'];
			}
			
			array_unshift($x_values, $series['label'] . '_x');
			array_unshift($y_values, $series['label']);
			
			$response[] = $x_values;
			$response[] = $y_values;
		}
		
		return ['data' => $response, '_' => [
			'type' => 'worklist.subtotals',
			'format' => 'scatterplot',
		]];
	}
	
	function _formatDataAsTable($chart_model) {
		$series_data = $chart_model['series'];
		
		$rows = $columns = [];
		
		$table = [
			'columns' => &$columns,
			'rows' => &$rows,
		];
		
		$series = $series_data[0];
		
		if($series) {
			$x_field_id = $series['x']['key_query'];
			$y_field_id = $series['y']['key_query'];
			
			$columns['x_label'] = [
				'label' => DevblocksPlatform::strTitleCase(@$series['x']['label'] ?: $x_field_id),
				'type' => @$series['x']['type'] ?: DevblocksSearchCriteria::TYPE_TEXT,
				'type_options' => @$series['x']['type_options'] ?: [],
			];
			
			$columns['y_label'] = [
				'label' => DevblocksPlatform::strTitleCase(@$series['y']['label'] ?: $y_field_id),
				'type' => @$series['y']['type'] ?: DevblocksSearchCriteria::TYPE_TEXT,
				'type_options' => @$series['y']['type_options'] ?: [],
			];
			
			foreach($series['data'] as $data) {
				$x = $data['x'];
				$y = $data['y'];
				@$x_label = $series['labels']['x'][$x] ?: $x;
				@$y_label = $series['labels']['y'][$y] ?: $y;
				
				$row = [
					'x_label' => $x_label,
					'x' => $x,
					'y_label' => $y_label,
					'y' => $y,
				];
				
				$rows[] = $row;
			}
		}
		
		return [
			'data' => $table,
			'_' => [
				'type' => 'worklist.xy',
				'format' => 'table',
			]
		];
	}
}

class _DevblocksDataProviderWorklistSubtotals extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.subtotals',
			'function' => 'count',
		];
		
		$subtotals_context = null;
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'function') {
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
				
			} else if($field->key == 'by') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['by'] = $value;
				
			} else if($field->key == 'query') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['query'] = $data_query;
			}
		}
		
		if(!$subtotals_context)
			return [];
		
		// Convert 'by:' keys to fields
		
		$dao_class = $subtotals_context->getDaoClass();
		$search_class = $subtotals_context->getSearchClass();
		$view = $subtotals_context->getSearchView(uniqid());
		$view->setAutoPersist(false);
		
		$view->addParamsWithQuickSearch(@$chart_model['query']);
		
		$query_fields = $view->getQuickSearchFields();
		$search_fields = $view->getFields();
		
		if($chart_model['by']) {
			$subtotal_by = $chart_model['by'];
			unset($chart_model['by']);
			
			if(!is_array($subtotal_by))
				$subtotal_by = [$subtotal_by];
				
			foreach($subtotal_by as $idx => $by) {
				// Handle limits and orders
				@list($by, $limit) = explode('~', $by, 2);
				@$limit_desc = DevblocksPlatform::strStartsWith($limit, '-') ? false : true;
				@$limit = DevblocksPlatform::intClamp(abs($limit), 0, 250) ?: 25;
				
				if(false == ($subtotal_field = $search_class::getFieldForSubtotalKey($by, $subtotals_context->id, $query_fields, $search_fields, $search_class::getPrimaryKey())))
					continue;
				
				$subtotal_field['limit'] = $limit;
				$subtotal_field['limit_desc'] = $limit_desc;
				
				$chart_model['by'][] = $subtotal_field;
			}
		}
		
		if(!isset($chart_model['by']) || !$chart_model['by'])
			return [];
		
		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
		
		$custom_fields = DAO_CustomField::getAll();
		
		// Aggregate function
		
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
			$limit = DevblocksPlatform::intClamp($by['limit'], 0, 250) ?: 25;
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
			
			if(false == ($rows = $db->GetArraySlave($sql)))
				return [];
			
		} else {
			$rows = [];
		}
		
		$labels = [];
		$by_token_to_field = [];
		
		foreach($chart_model['by'] as $by) {
			$values = array_column($rows, $by['key_select']);
			$by_token_to_field[$by['key_select']] = $by['key_query'];
			
			if(false !== ($by_labels = $search_class::getLabelsForKeyValues($by['key_select'], $values))) {
				$labels[$by['key_select']] = $by_labels;
				continue;
			}
		}
		
		$response = ['children' => []];
		@$last_k = array_slice(array_keys($rows[0]), -1, 1)[0] ?: [];
		
		foreach($rows as $row) {
			$ptr =& $response['children'];
			
			foreach(array_slice(array_keys($row),1) as $k) {
				$label = (array_key_exists($k, $labels) && array_key_exists($row[$k], $labels[$k])) ? $labels[$k][$row[$k]] : $row[$k];
				
				if(false === ($idx = array_search($label, array_column($ptr, 'name')))) {
					$data = [
						'name' => $label,
						'value' => $row[$k],
						'hits' => 0,
					];
					
					$ptr[] = $data;
					end($ptr);
					$ptr =& $ptr[key($ptr)];
					
				} else {
					$ptr =& $ptr[$idx];
				}
				
				$ptr['hits'] += $row['hits'];
				
				if($k != $last_k) {
					if(!array_key_exists('children', $ptr))
						$ptr['children'] = [];
					
					$ptr =& $ptr['children'];
				}
			}
		}
		
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
		
		switch(@$chart_model['format']) {
			case 'categories':
				return $this->_formatDataAsCategories($response, $chart_model);
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
			default:
				return $this->_formatDataAsTree($response, $chart_model);
				break;
		}
	}
	
	function _formatDataAsTree($response, array $chart_model=[]) {
		return [
			'data' => $response, 
			'_' => [
				'type' => 'worklist.subtotals',
				'format' => 'tree',
			]
		];
	}
	
	function _formatDataAsCategories($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		// Do we have nested data?
		$nested = @$response['children'][0]['children'] ? true : false;
		
		if($nested) {
			$parents = [];
			$xvalues = [];
			
			$output = [
				['label'],
			];
			
			$parents = array_column($response['children'], 'name');
			$output[0] = array_merge($output[0], $parents);
			
			foreach($response['children'] as $parent) {
				foreach($parent['children'] as $child) {
					$xvalues[$child['name']] = array_fill_keys($parents, 0);
				}
			}
			
			foreach($response['children'] as $parent) {
				foreach($parent['children'] as $child) {
					$xvalues[$child['name']][$parent['name']] = $child['hits'];
				}
			}
			
			foreach($xvalues as $child_name => $parents) {
				$values = array_values($parents);
				array_unshift($values, $child_name);
				$output[] = $values;
			}
			
		} else {
			$output = [
				['label'], ['hits']
			];
			
			foreach($response['children'] as $subtotal) {
				$output[0][] = $subtotal['name'];
				$output[1][] = $subtotal['hits'];
			}
		}
		
		return ['data' => $output, '_' => [
			'type' => 'worklist.subtotals',
			'stacked' => $nested,
			'format' => 'categories',
		]];
	}
	
	function _formatDataAsPie($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		$output = [];
		
		foreach($response['children'] as $subtotal) {
			$output[] = [$subtotal['name'], $subtotal['hits']];
		}
		
		return [
			'data' => $output,
			'_' => [
				'type' => 'worklist.subtotals',
				'format' => 'pie',
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
		
		// Build a table recursively from the tree
		$recurse = function($node, $depth=0, $parents=[]) use (&$recurse, $chart_model, &$rows) {
			@$by = $chart_model['by'][$depth];
			
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
			'format' => 'table',
		]];
	}
	
	function _formatDataAsTimeSeries($response, array $chart_model=[]) {
		if(!isset($response['children']))
			return [];
		
		$x_series = array_fill_keys(array_column($response['children'], 'name'), 0);
		$output = [ 'ts' => array_map(function($d) { return strval($d); }, array_keys($x_series)) ];
		
		foreach($response['children'] as $date) {
			if(!isset($date['children']))
				continue;
			
			foreach($date['children'] as $series) {
				if(!isset($output[$series['name']]))
					$output[$series['name']] = $x_series;
				
				$output[$series['name']][$date['name']] = $series['hits'];
			}
		}
		
		foreach(array_keys($output) as $series_key) {
			$output[$series_key] = array_values($output[$series_key]);
		}
		
		return ['data' => $output, '_' => [
			'type' => 'worklist.subtotals',
			'format' => 'timeseries',
		]];
	}
}

class _DevblocksDataProviderWorklistSeries extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.series',
			'series' => [],
			'x.label' => 'Metric',
			'format' => 'timeseries',
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = DevblocksPlatform::strLower($value);
				
			} else if($field->key == 'x.label') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['x.label'] = $value;
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
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
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if(false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
					} else if($series_field->key == 'function') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['function'] = $value;
						
					} else if($series_field->key == 'label') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['label'] = $value;
						
					} else if($series_field->key == 'x') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['x'] = $value;
						
					} else if($series_field->key == 'y') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y'] = $value;
						
					} else if($series_field->key == 'query') {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query'] = $data_query;
					}
				}
				
				// If we aren't given a bin, default to months
				if(!strpos($series_model['x'],'@'))
					$series_model['x'] .= '@month';
				
				// Convert series x/y to SearchFields_* using context
				
				if($series_context) {
					$view_class = $series_context->getViewClass();
					$view = new $view_class();
					
					$search_class = $series_context->getSearchClass();
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
					// [TODO] The field has to be a date type
					
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
			}
		}
		
		// Fetch data for each series
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series_idx => $series) {
			if(!isset($series['context']))
				continue;
			
			@$query = $series['query'];
			
			$context_ext = Extension_DevblocksContext::get($series['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$view = $context_ext->getSearchView(uniqid());
			$view->setAutoPersist(false);
			$view->addParamsWithQuickSearch($query);
			
			$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
			
			@$date_field = $series['x']['sql_select'];
			@$function = $series['function'];
			
			switch($function) {
				case 'average':
					$metric_field = sprintf("AVG(%s)",
						Cerb_ORMHelper::escape($series['y']['sql_select'])
					);
					break;
					
				default:
				case 'count':
					$metric_field = "COUNT(*)";
					break;
					
				case 'max':
					$metric_field = sprintf("MAX(%s)",
						Cerb_ORMHelper::escape($series['y']['sql_select'])
					);
					break;
					
				case 'min':
					$metric_field = sprintf("MIN(%s)",
						Cerb_ORMHelper::escape($series['y']['sql_select'])
					);
					break;
					
				case 'sum':
					$metric_field = sprintf("SUM(%s)",
						Cerb_ORMHelper::escape($series['y']['sql_select'])
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
			
			$results = $db->GetArraySlave($sql);
			
			$results = array_column($results, 'metric', 'value');
			ksort($results);
			
			$chart_model['series'][$series_idx]['data'] = $results;
		}
		
		switch(@$chart_model['format']) {
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				break;
				
			default:
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($chart_model);
				break;
		}
	}
	
	private function _formatDataAsTable(array $chart_model) {
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
			$x_domain += array_keys($series['data']);
		}
		
		sort($x_domain);
		
		// Table
		
		$rows = [];
		
		foreach($x_domain as $k) {
			$row = [
				'x' => $k,
			];
			
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
	
	private function _formatDataAsTimeSeries(array $chart_model) {
		// [TODO] Verify that 'x' is a date
		
		// Domain
		
		$x_domain = [];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			if(!isset($series['data']))
				continue;
				
			$x_domain += array_keys($series['data']);
		}
		
		sort($x_domain);
		
		$response = [
			'ts' => $x_domain,
		];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			$values = [];
			
			foreach($x_domain as $k) {
				$values[] = floatval(@$series['data'][$k] ?: 0);
			}
			
			$response[$series['label']] = $values;
		}
		
		return [
			'data' => $response,
			'_' => [
				'type' => 'worklist.series',
				'format' => 'timeseries',
			]
		];
	}
}

class _DevblocksDataProviderBotBehavior extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$behavior_alias = $options['behavior_alias'];
		
		if(false == ($data_behavior = Event_DataQueryDatasource::getByAlias($behavior_alias)))
			throw new Exception_DevblocksValidationError("A bot behavior isn't configured.");
		
		$behavior_vars = [];
		
		foreach($chart_fields as $chart_field) {
			if($chart_field->key == 'type')
				continue;
			
			$var_key = 'var_' . $chart_field->key;
			
			if(array_key_exists($var_key, $data_behavior->variables)) {
				CerbQuickSearchLexer::getOperStringFromTokens($chart_field->tokens, $oper, $value);
				$behavior_vars[$var_key] = $value;
			}
		}
		
		// Event model
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DataQueryDatasource::ID,
			[
				'_variables' => $behavior_vars,
				'actions' => &$actions,
			]
		);
		
		if(false == ($event = $data_behavior->getEvent()))
			return;
			
		$event->setEvent($event_model, $data_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		// Format behavior vars
		
		if(is_array($behavior_vars))
		foreach($behavior_vars as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!isset($data_behavior->variables[$k]))
					continue;
				
				$value = $data_behavior->formatVariable($data_behavior->variables[$k], $v);
				$dict->set($k, $value);
			}
		}
		
		// Run tree
		
		$result = $data_behavior->runDecisionTree($dict, false, $event);
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'return_data':
					$data = @json_decode($action['data'], true);
					
					return ['data' => $data, '_' => [
						'type' => 'behavior.' . $behavior_alias,
					]];
					break;
			}
		}
		
		return [];
	}
}

class _DevblocksDataProviderUsageBotBehaviors extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$format = 'table';
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $format);
			}
		}
		
		switch($format) {
			default:
			case 'table':
				return $this->_formatDataAsTable($chart_fields);
				break;
				
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($chart_fields);
				break;
		}
	}
	
	private function _formatDataAsTable(array $chart_fields=[]) {
		$chart_model = [
			'type' => 'usage.behaviors',
			'format' => 'timeseries',
			'ids' => [],
		];
		
		$db = DevblocksPlatform::services()->database();
		
		$start = 'first day of this month -1 year';
		@$start_time = strtotime($start);
		$start_time -= $start_time % 86400;
		
		$end = 'now';
		@$end_time = strtotime($end);
		$end_time -= $end_time % 86400;
		
		$sort_by = 'uses';
		if(!in_array($sort_by, ['uses', 'avg_elapsed_ms', 'elapsed_ms', 'va_name', 'va_owner', 'event']))
			$sort_by = 'uses';

		// Scope
		
		$event_mfts = Extension_DevblocksEvent::getAll(false);

		// Data
		
		$sql = sprintf("SELECT trigger_event.id AS behavior_id, trigger_event.title AS behavior_name, trigger_event.bot_id, trigger_event.event_point, ".
			"SUM(trigger_event_history.uses) AS uses, SUM(trigger_event_history.elapsed_ms) AS elapsed_ms ".
			"FROM trigger_event_history ".
			"INNER JOIN trigger_event ON (trigger_event_history.trigger_id=trigger_event.id) ".
			"WHERE trigger_event_history.ts_day BETWEEN %d AND %d ".
			"GROUP BY trigger_event.id, trigger_event.title, trigger_event.bot_id, trigger_event.event_point ",
			$start_time,
			$end_time
		);
		
		$stats = $db->GetArraySlave($sql);
		
		$bots = DAO_Bot::getAll();
		
		foreach($stats as &$stat) {
			// Avg. Runtime
			
			$stat['avg_elapsed_ms'] = !empty($stat['uses']) ? intval($stat['elapsed_ms'] / $stat['uses']) : $stat['elapsed_ms'];
			
			// Event
			
			@$event_mft = $event_mfts[$stat['event_point']];
			$stat['event'] = !empty($event_mft) ? $event_mft->name : '';
			
			// Bot
			
			if(false == (@$bot = $bots[$stat['bot_id']]))
				continue;
			
			// Owner

			$meta = $bot->getOwnerMeta();
			
			$stat['bot_id'] = $bot->id;
			$stat['bot_name'] = $bot->name;
			$stat['bot_owner'] = sprintf("%s%s", $meta['context_ext']->manifest->name, (!empty($meta['name']) ? (': '.$meta['name']) : ''));
		}
		
		// Sort
		
		$sort_asc = false;
		switch($sort_by) {
			case 'event':
			case 'bot_name':
			case 'bot_owner':
				$sort_asc = true;
				break;
		}
		
		DevblocksPlatform::sortObjects($stats, sprintf('[%s]', $sort_by), $sort_asc);
		
		return [
			'data' => [
				'columns' => [
					'bot_name' => [
						'label' => DevblocksPlatform::translateCapitalized('common.bot'),
						'type' => 'context',
						'type_options' => [
							'context' => CerberusContexts::CONTEXT_BOT,
							'context_id_key' => 'bot_id',
						]
					],
					'behavior_name' => [
						'label' => DevblocksPlatform::translateCapitalized('common.behavior'),
						'type' => 'context',
						'type_options' => [
							'context' => CerberusContexts::CONTEXT_BEHAVIOR,
							'context_id_key' => 'behavior_id',
						]
					],
					'event' => [
						'label' => DevblocksPlatform::translateCapitalized('common.event'),
						'type' => Model_CustomField::TYPE_SINGLE_LINE,
					],
					'uses' => [
						'label' => '# Uses',
						'type' => Model_CustomField::TYPE_NUMBER,
					],
					'elapsed_ms' => [
						'label' => 'Total Runtime (ms)',
						'type' => Model_CustomField::TYPE_NUMBER,
					],
					'avg_elapsed_ms' => [
						'label' => 'Avg. Runtime (ms)',
						'type' => Model_CustomField::TYPE_NUMBER,
					]
				],
				'rows' => $stats,
			],
			'_' => [
				'type' => 'usage.behaviors',
				'format' => 'table'
			]
		];
	}
	
	private function _formatDataAsTimeSeries(array $chart_fields=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'usage.behaviors',
			'format' => 'timeseries',
			'ids' => [],
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'ids') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $ids);
				$chart_model['ids'] = $ids;
			}
		}
		
		if(!$chart_model['ids']) {
			$sql = sprintf("SELECT trigger_id AS behavior_id ".
				"FROM trigger_event_history ".
				"WHERE ts_day BETWEEN %d AND %d ".
				"GROUP BY behavior_id ".
				"ORDER BY COUNT(*) DESC ".
				"LIMIT 10",
				strtotime('first day of this month -1 year 00:00:00'),
				strtotime('today 00:00:00')
			);
			$results = $db->GetArraySlave($sql);
			$chart_model['ids'] = array_column($results, 'behavior_id');
		}
		
		$sql = sprintf("SELECT SUM(uses) AS metric, trigger_id AS behavior_id, DATE_FORMAT(FROM_UNIXTIME(ts_day),'%%Y-%%m') AS label ".
			"FROM trigger_event_history ".
			"WHERE ts_day BETWEEN %d AND %d ".
			"%s".
			"GROUP BY label, trigger_id ".
			"ORDER BY label ",
			strtotime('first day of this month -1 year 00:00:00'),
			strtotime('today 00:00:00'),
			$chart_model['ids'] 
				? sprintf("AND trigger_id IN (%s) ", implode(',', $chart_model['ids']))
				: ''
		);
		
		$results = $db->GetArraySlave($sql);
		
		$output = [
			'data' => [
				'ts' => [],
			],
			'_' => [
				'type' => 'usage.behaviors',
				'format' => 'timeseries'
			]
		];
		
		$x_values = array_values(array_unique(array_column($results, 'label')));
		
		$output['data']['ts'] = $x_values;
		
		$series = [];
		
		foreach($results as $result) {
			$series_key = $result['behavior_id'];
			
			if(!array_key_exists($series_key, $series)) {
				$series[$series_key] = array_fill_keys($x_values, 0);
			}
			
			$series[$series_key][$result['label']] = floatval($result['metric']);
		}
		
		$behaviors = DAO_TriggerEvent::getIds(array_keys($series));
		
		foreach($series as $behavior_id => $y_values) {
			$behavior_name = @$behaviors[$behavior_id]->title ?: '(no name)';
			$output['data'][$behavior_name] = array_values($y_values);
		}
		
		return $output;
	}
}

class _DevblocksDataProviderUsageSnippets extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$format = 'table';
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $format);
			}
		}
		
		switch($format) {
			case 'timeseries':
				return $this->_formatDataAsTimeSeries($chart_fields);
				break;
				
			case 'table':
			default:
				return $this->_formatDataAsTable($chart_fields);
				break;
		}
	}
	
	private function _formatDataAsTable(array $chart_fields=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'usage.snippets',
			'format' => 'table',
		];
		
		// Filter: Start + End
		
		$start = 'first day of this month -1 year';
		@$start_time = strtotime($start);
		$start_time -= $start_time % 86400;
		
		$end = 'now';
		@$end_time = strtotime($end);
		$end_time -= $end_time % 86400;

		// Filter: Limit
		$limit = 0;
		
		// Filter: Workers
		
		$filter_worker_ids = [];
		$filter_worker_ids = DevblocksPlatform::sanitizeArray($filter_worker_ids, 'integer', ['unique','nonzero']);
		
		$workers = DAO_Worker::getAll();
		
		$sql = sprintf("SELECT snippet.id AS snippet_id, snippet.title AS snippet_title, SUM(snippet_use_history.uses) AS snippet_uses ".
			"FROM snippet_use_history ".
			"INNER JOIN snippet ON (snippet_use_history.snippet_id=snippet.id) ".
			"WHERE snippet_use_history.ts_day BETWEEN %d AND %d ".
			"%s ".
			"GROUP BY snippet.id, snippet.title ".
			"ORDER BY snippet_uses %%s ".
			"%s",
			$start_time,
			$end_time,
			(!empty($filter_worker_ids) && is_array($filter_worker_ids) ? sprintf("AND snippet_use_history.worker_id IN (%s)", implode(',', $filter_worker_ids)) : ''),
			(!empty($limit) ? sprintf("LIMIT %d", $limit) : '')
		);
		
		if(true) {
			$results = $db->GetArraySlave(sprintf($sql, 'DESC'));
		} else {
			$results = $db->GetArraySlave(sprintf($sql, 'ASC'));
		}
		
		return [
			'data' => [
				'columns' => [
					'snippet_title' => [
						'label' => 'Name',
						'type' => 'context',
						'type_options' => [
							'context' => CerberusContexts::CONTEXT_SNIPPET,
							'context_id_key' => 'snippet_id',
						]
					],
					'snippet_uses' => [
						'label' => '# Uses',
						'type' => Model_CustomField::TYPE_NUMBER,
					]
				],
				'rows' => $results,
			],
			'_' => [
				'type' => 'usage.snippets',
				'format' => 'table'
			]
		];
	}
	
	private function _formatDataAsTimeSeries(array $chart_fields=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'usage.snippets',
			'format' => 'timeseries',
			'ids' => [],
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'ids') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $ids);
				$chart_model['ids'] = $ids;
			}
		}
		
		// Default IDs
		
		if(!$chart_model['ids']) {
			$sql = sprintf("SELECT snippet_id ".
				"FROM snippet_use_history ".
				"WHERE ts_day BETWEEN %d AND %d ".
				"GROUP BY snippet_id ".
				"ORDER BY COUNT(*) DESC ".
				"LIMIT 10",
				strtotime('first day of this month -1 year 00:00:00'),
				strtotime('today 00:00:00')
			);
			$results = $db->GetArraySlave($sql);
			$chart_model['ids'] = array_column($results, 'snippet_id');
		}
		
		$sql = sprintf("SELECT SUM(uses) AS metric, snippet_id, DATE_FORMAT(FROM_UNIXTIME(ts_day),'%%Y-%%m') AS label ".
			"FROM snippet_use_history ".
			"WHERE ts_day BETWEEN %d AND %d ".
			"%s".
			"GROUP BY label, snippet_id ".
			"ORDER BY label ",
			strtotime('first day of this month -1 year 00:00:00'),
			strtotime('today 00:00:00'),
			$chart_model['ids'] 
				? sprintf("AND snippet_id IN (%s) ", implode(',', $chart_model['ids']))
				: ''
		);
		
		$results = $db->GetArraySlave($sql);
		
		$output = [
			'data' => [
				'ts' => [],
			],
			'_' => [
				'type' => 'usage.snippets',
				'format' => 'timeseries'
			]
		];
		
		$x_values = array_values(array_unique(array_column($results, 'label')));
		
		$output['data']['ts'] = $x_values;
		
		$series = [];
		
		foreach($results as $result) {
			$series_key = $result['snippet_id'];
			
			if(!array_key_exists($series_key, $series)) {
				$series[$series_key] = array_fill_keys($x_values, 0);
			}
			
			$series[$series_key][$result['label']] = floatval($result['metric']);
		}
		
		$snippets = DAO_Snippet::getIds(array_keys($series));
		
		foreach($series as $snippet_id => $y_values) {
			$snippet_name = @$snippets[$snippet_id]->title ?: '(no name)';
			$output['data'][$snippet_name] = array_values($y_values);
		}
		
		return $output;
	}
}

class _DevblocksDataService {
	static $instance = null;
	
	private function __construct() {
		// We lazy load the connections
	}
	
	static function getInstance() {
		if(null == self::$instance) {
			self::$instance = new _DevblocksDataService();
		}
		
		return self::$instance;
	}
	
	function executeQuery($query, &$error=null) {
		$chart_fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		@$type_field = array_shift(array_filter($chart_fields, function($field) {
			if($field->key == 'type')
				return true;
			
			return false;
		}));
		
		if(!$type_field) {
			$error = "A data query 'type:' is required.";
			return false;
		}
		
		CerbQuickSearchLexer::getOperStringFromTokens($type_field->tokens, $oper, $chart_type);
		
		$results = [];
		
		switch($chart_type) {
			case 'usage.behaviors':
				$provider = new _DevblocksDataProviderUsageBotBehaviors();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'usage.snippets':
				$provider = new _DevblocksDataProviderUsageSnippets();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.metrics':
				$provider = new _DevblocksDataProviderWorklistMetrics();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.series':
				$provider = new _DevblocksDataProviderWorklistSeries();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.subtotals':
				$provider = new _DevblocksDataProviderWorklistSubtotals();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.xy':
				$provider = new _DevblocksDataProviderWorklistXy();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($chart_type, 'behavior.')) {
					$behavior_alias = substr($chart_type, 9);
					$provider = new _DevblocksDataProviderBotBehavior();
					$results = $provider->getData($query, $chart_fields, ['behavior_alias' => $behavior_alias]);
					break;
				}
				
				$error = sprintf("'%s' is not a known data query type.", $chart_type);
				return false;
				break;
		}
		
		return $results;
	}
}