<?php
abstract class _DevblocksDataProvider {
	abstract function getData($query, $chart_fields, array $options=[]);
}

class _DevblocksDataProviderWorklistMetric extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.metric',
			'metric' => '',
			'values' => [],
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'metric') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['metric'] = $value;
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'values.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_model = [
					'id' => explode('.', $field->key, 2)[1],
				];
				
				$series_context = null;
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if(false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
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
					
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
					// [TODO] The field has to be a date type
					if(array_key_exists('field', $series_model)) {
						if(isset($query_fields[$series_model['field']])) {
							$search_key = $query_fields[$series_model['field']]['options']['param_key'];
							$search_field = $search_fields[$search_key];
							$series_model['field'] = $search_field;
						} else {
							unset($series_model['field']);
						}
					}
				}
				
				$chart_model['values'][] = $series_model;
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
			
			switch($chart_model['metric']) {
				case 'number.average':
				case 'number.avg':
					$metric_field = sprintf("AVG(%s.%s)",
						Cerb_ORMHelper::escape($series['field']->db_table),
						Cerb_ORMHelper::escape($series['field']->db_column)
					);
					break;
					
				case 'number.count':
					$metric_field = sprintf("COUNT(*)",
						Cerb_ORMHelper::escape($series['field']->db_table),
						Cerb_ORMHelper::escape($series['field']->db_column)
					);
					break;
					
				case 'number.max':
					$metric_field = sprintf("MAX(%s.%s)",
						Cerb_ORMHelper::escape($series['field']->db_table),
						Cerb_ORMHelper::escape($series['field']->db_column)
					);
					break;
					
				case 'number.min':
					$metric_field = sprintf("MIN(%s.%s)",
						Cerb_ORMHelper::escape($series['field']->db_table),
						Cerb_ORMHelper::escape($series['field']->db_column)
					);
					break;
					
				case 'number.sum':
					$metric_field = sprintf("SUM(%s.%s)",
						Cerb_ORMHelper::escape($series['field']->db_table),
						Cerb_ORMHelper::escape($series['field']->db_column)
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
		
		$response = [];
		
		if(isset($chart_model['values']))
			foreach($chart_model['values'] as $value) {
				if(isset($value['id']) && isset($value['value']))
					$response[] = [$value['id'],$value['value']];
			}
		
		return $response;
	}
}

class _DevblocksDataProviderWorklistScatterplot extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.scatterplot',
			'x' => '',
			'y' => '',
			'series' => [],
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
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_model = [
					'id' => explode('.', $field->key, 2)[1],
				];
				
				$series_context = null;
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if(false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
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
					
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
					if(array_key_exists('x', $series_model)) {
						if(isset($query_fields[$series_model['x']])) {
							$search_key = $query_fields[$series_model['x']]['options']['param_key'];
							$search_field = $search_fields[$search_key];
							$series_model['x'] = $search_field;
						} else {
							unset($series_model['x']);
						}
					}
					
					if(array_key_exists('y', $series_model)) {
						if(isset($query_fields[$series_model['y']])) {
							$search_key = $query_fields[$series_model['y']]['options']['param_key'];
							$search_field = $search_fields[$search_key];
							$series_model['y'] = $search_field;
						} else {
							unset($series_model['y']);
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
			
			$x_field = $y_field = null;
			
			switch($chart_model['x']) {
				default:
					$x_field = sprintf("%s.%s",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
			}
			
			switch($chart_model['y']) {
				default:
					$y_field = sprintf("%s.%s",
						Cerb_ORMHelper::escape($series['y']->db_table),
						Cerb_ORMHelper::escape($series['y']->db_column)
					);
					break;
			}
			
			if(!$x_field || !$y_field)
				continue;
			
			// [TODO] Limit
			$sql = sprintf("SELECT %s AS x, %s AS y %s %s LIMIT 5000",
				$x_field,
				$y_field,
				$query_parts['join'],
				$query_parts['where']
			);
			
			$results = $db->GetArraySlave($sql);
			
			$results = array_column($results, 'y', 'x');
			
			$chart_model['series'][$series_idx]['data'] = $results;
		}
		
		// Respond
		
		$response = [];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			$id = $series['id'];
			
			$format_values = function($d, $axis) use ($chart_model) {
				switch(@$chart_model[$axis]) {
					case 'time.seconds':
						return DevblocksPlatform::strSecsToString($d);
						break;
						
					default:
						return floatval($d);
						break;
				}
			};
			
			$x_values = $y_values = [];
			
			foreach($series['data'] as $x => $y) {
				$x_values[] = $format_values($x, 'x');
				$y_values[] = $format_values($y, 'y');
			}
			
			array_unshift($x_values, $id . '_x');
			array_unshift($y_values, $id);
			
			$response[] = $x_values;
			$response[] = $y_values;
		}
		
		return $response;
	}
}

class _DevblocksDataProviderWorklistSubtotals extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.subtotals',
			'metric' => '',
		];
		
		$subtotals_context = null;
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'metric') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['metric'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else if($field->key == 'limit') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['limit'] = $value;
				
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
				if(false == ($subtotal_field = $search_class::getFieldForSubtotalKey($by, $query_fields, $search_fields, $search_class::getPrimaryKey())))
					continue;
				
				$chart_model['by'][] = $subtotal_field;
			}
		}
		
		if(!isset($chart_model['by']) || !$chart_model['by'])
			return [];
		
		$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
		
		$custom_fields = DAO_CustomField::getAll();
		
		$sql = sprintf("SELECT COUNT(*) AS hits, %s %s %s GROUP BY %s",
			implode(', ', array_map(function($e) use ($db) {
				return sprintf("%s AS `%s`",
					$e['sql_select'],
					$db->escape($e['key_select'])
				);
			}, $chart_model['by'])),
			$query_parts['join'],
			$query_parts['where'],
			implode(', ', array_map(function($e) use ($db) {
				return sprintf("`%s`",
					$db->escape($e['key_select'])
				);
			}, $chart_model['by']))
		);
		
		// [TODO] LIMIT
		//$chart_model['limit'] intClamp
		$sql .= ' LIMIT 500';
		
		$response = [];
		
		if(false == ($rows = $db->GetArraySlave($sql)))
			return [];
		
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
		$last_k = array_slice(array_keys($rows[0]), -1, 1)[0];
		
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
				return $this->_convertTreeToCategories($response);
				break;
				
			case 'timeseries':
				return $this->_convertTreeToTimeSeries($response);
				break;
				
			case 'tree':
			default:
				return $response;
				break;
		}
	}
	
	function _convertTreeToCategories($response) {
		if(!isset($response['children']))
			return [];
		
		// [TODO] Do we have nested data?
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
		
		return ['subtotals' => $output, 'stacked' => $nested];
	}
	
	function _convertTreeToTimeSeries($response) {
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
		
		return $output;
	}
}

class _DevblocksDataProviderWorklistTimeSeries extends _DevblocksDataProvider {
	function getData($query, $chart_fields, array $options=[]) {
				$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.timeseries',
			'x' => '',
			'y' => '',
			'series' => [],
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
				
			} else if(DevblocksPlatform::strStartsWith($field->key, 'series.')) {
				$series_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$series_query = substr($series_query, 1, -1);
				
				$series_fields = CerbQuickSearchLexer::getFieldsFromQuery($series_query);
				
				$series_model = [
					'id' => explode('.', $field->key, 2)[1],
				];
				
				$series_context = null;
				
				foreach($series_fields as $series_field) {
					if($series_field->key == 'of') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						if(false == ($series_context = Extension_DevblocksContext::getByAlias($value, true)))
							continue;
						
						$series_model['context'] = $series_context->id;
						
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
					
					$query_fields = $view->getQuickSearchFields();
					$search_fields = $view->getFields();
					
					// [TODO] The field has to be a date type
					if(array_key_exists('x', $series_model)) {
						if(isset($query_fields[$series_model['x']])) {
							$search_key = $query_fields[$series_model['x']]['options']['param_key'];
							$search_field = $search_fields[$search_key];
							$series_model['x'] = $search_field;
						} else {
							unset($series_model['x']);
						}
					}
					
					if(array_key_exists('y', $series_model)) {
						if(isset($query_fields[$series_model['y']])) {
							$search_key = $query_fields[$series_model['y']]['options']['param_key'];
							$search_field = $search_fields[$search_key];
							$series_model['y'] = $search_field;
						} else {
							unset($series_model['y']);
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
			
			switch($chart_model['x']) {
				case 'date.year':
					$date_field = sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%%Y')",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
					
				case 'date.month':
					$date_field = sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%%Y-%%m')",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
					
				case 'date.day':
					$date_field = sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%%Y-%%m-%%d')",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
					
				case 'date.hour':
					$date_field = sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%%Y-%%m-%%d-%%H')",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
					
				case 'date.minute':
					$date_field = sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%%Y-%%m-%%d-%%H-%%i')",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
					
				case 'date.second':
					$date_field = sprintf("DATE_FORMAT(FROM_UNIXTIME(%s.%s), '%%Y-%%m-%%d-%%H-%%i-%%s')",
						Cerb_ORMHelper::escape($series['x']->db_table),
						Cerb_ORMHelper::escape($series['x']->db_column)
					);
					break;
					
				default:
					$date_field = null;
					break;
			}
			
			switch($chart_model['y']) {
				case 'number.average':
					$metric_field = sprintf("AVG(%s.%s)",
						Cerb_ORMHelper::escape($series['y']->db_table),
						Cerb_ORMHelper::escape($series['y']->db_column)
					);
					break;
					
				default:
				case 'number.count':
					$metric_field = "COUNT(*)";
					break;
					
				case 'number.max':
					$metric_field = sprintf("MAX(%s.%s)",
						Cerb_ORMHelper::escape($series['y']->db_table),
						Cerb_ORMHelper::escape($series['y']->db_column)
					);
					break;
					
				case 'number.min':
					$metric_field = sprintf("MIN(%s.%s)",
						Cerb_ORMHelper::escape($series['y']->db_table),
						Cerb_ORMHelper::escape($series['y']->db_column)
					);
					break;
					
				case 'number.sum':
					$metric_field = sprintf("SUM(%s.%s)",
						Cerb_ORMHelper::escape($series['y']->db_table),
						Cerb_ORMHelper::escape($series['y']->db_column)
					);
					break;
			}
			
			if(!$date_field || !$metric_field)
				continue;
			
			// [TODO] Limit
			$sql = sprintf("SELECT %s AS metric, %s AS value %s %s GROUP BY %s LIMIT 300",
				$metric_field,
				$date_field,
				$query_parts['join'],
				$query_parts['where'],
				$date_field
			);
			
			$results = $db->GetArraySlave($sql);
			
			$results = array_column($results, 'metric', 'value');
			ksort($results);
			
			$chart_model['series'][$series_idx]['data'] = $results;
		}
		
		// Domain
		
		$x_domain = [];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			if(!isset($series['data']))
				continue;
				
			$x_domain += array_keys($series['data']);
		}
		
		sort($x_domain);
		
		// Respond
		
		$response = [
			'ts' => $x_domain,
		];
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series) {
			$values = [];
			
			foreach($x_domain as $k) {
				$values[] = floatval(@$series['data'][$k] ?: 0);
			}
			
			$response[$series['id']] = $values;
		}
		
		return $response;
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
					$data = @$action['data'];
					return $data;
					break;
			}
		}
		
		return [];
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
	
	function executeQuery($query) {
		$chart_fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		$type_field = array_filter($chart_fields, function($field) {
			if($field->key == 'type')
				return true;
			
			return false;
		});
		
		if(!is_array($type_field) || 1 != count($type_field))
			throw new Exception_DevblocksValidationError("A valid chart type is required.");
		
		CerbQuickSearchLexer::getOperStringFromTokens($type_field[0]->tokens, $oper, $chart_type);
		
		$results = [];
		
		switch($chart_type) {
			case 'worklist.metric':
				$provider = new _DevblocksDataProviderWorklistMetric();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.scatterplot':
				$provider = new _DevblocksDataProviderWorklistScatterplot();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.subtotals':
				$provider = new _DevblocksDataProviderWorklistSubtotals();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			case 'worklist.timeseries':
				$provider = new _DevblocksDataProviderWorklistTimeSeries();
				$results = $provider->getData($query, $chart_fields);
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($chart_type, 'behavior.')) {
					$behavior_alias = substr($chart_type, 9);
					$provider = new _DevblocksDataProviderBotBehavior();
					$results = $provider->getData($query, $chart_fields, ['behavior_alias' => $behavior_alias]);
					break;
				}
				
				throw new Exception_DevblocksValidationError("A valid chart type is required.");
				break;
		}
		
		return $results;
	}
}