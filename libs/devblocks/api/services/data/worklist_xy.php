<?php
class _DevblocksDataProviderWorklistXy extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				[
					'caption' => 'series:',
					'snippet' => "series.\${1:alias}:(\n  of:\${2:message}\n  x:\${3:worker}\n  y:\${4:responseTime}\n  query:(\n    \${5:responseTime:>0 sort:responseTime limit:10}\n  )\n)",
					'suppress_autocomplete' => true,
				],
				'format:',
				'timeout:',
			],
			'series.*:' => [
				'' => [
					'of:',
					'label:',
					'x:',
					'x.metric:',
					'y:',
					'y.metric:',
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
			'format:' => [
				'pie',
				'categories',
				'scatterplot',
				'table',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.xy',
			'x' => '',
			'y' => '',
			'series' => [],
			'format' => 'scatterplot',
			'timeout' => 20000,
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				// Do nothing
				true;
				
			} else if($field->key == 'x') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['x'] = $value;
				
			} else if($field->key == 'y') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['y'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else if($field->key == 'timeout') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['timeout'] = DevblocksPlatform::intClamp($value, 0, 60000);

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
						
					} else if($series_field->key == 'x.metric') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['x_metric'] = $value;
						
					} else if($series_field->key == 'y') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y'] = $value;
						
					} else if($series_field->key == 'y.metric') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['y_metric'] = $value;
						
					} else if($series_field->key == 'query') {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query'] = $data_query;
						
					} else if(in_array($series_field->key, ['query.require', 'query.required'])) {
						$data_query = CerbQuickSearchLexer::getTokensAsQuery($series_field->tokens);
						$data_query = substr($data_query, 1, -1);
						$series_model['query_required'] = $data_query;
						
					} else {
						$error = sprintf("The series parameter '%s' is unknown.", $series_field->key);
						return false;
					}
				}
				
				// Convert series x/y to SearchFields_* using context
				
				// [TODO] Handle currency/decimal
				
				if($series_context) {
					$view = $series_context->getTempView();
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
			
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Fetch data for each series
		
		if(isset($chart_model['series']))
		foreach($chart_model['series'] as $series_idx => $series) {
			if(!isset($series['context']))
				continue;
			
			@$query = $series['query'];
			@$query_required = $series['query_required'];
			
			$context_ext = Extension_DevblocksContext::get($series['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$search_class = $context_ext->getSearchClass();
			$view = $context_ext->getTempView();
			
			if(false === $view->addParamsRequiredWithQuickSearch($query_required, true, [], $error))
				return false;
			
			if(false === $view->addParamsWithQuickSearch($query, true, [], $error))
				return false;
			
			$query_parts = $dao_class::getSearchQueryComponents(
				[],
				$view->getParams()
			);
			
			$sort_data = Cerb_ORMHelper::buildSort($view->renderSortBy, $view->renderSortAsc, $view->getFields(), $search_class);
			
			if(!array_key_exists('x', $series) || !array_key_exists('y', $series))
				continue;
			
			$x_field = $series['x']['sql_select'] ?? null;
			$y_field = $series['y']['sql_select'] ?? null;
			
			$sql = sprintf("SELECT %s AS x, %s AS y%s %s %s %s LIMIT %d",
				$x_field,
				$y_field,
				$sort_data['sql_select'] ? sprintf(", %s", $sort_data['sql_select']) : '',
				$query_parts['join'],
				$query_parts['where'],
				$sort_data['sql_sort'],
				$view->renderLimit
			);
			
			try {
				if(false == ($results = $db->GetArrayReader($sql, $chart_model['timeout'])))
					$results = [];
				
			} catch (Exception_DevblocksDatabaseQueryTimeout $e) {
				$error = sprintf('Query timed out (%d ms)', $chart_model['timeout']);
				return false;
			}
			
			$x_labels = $search_class::getLabelsForKeyValues($series['x']['key_select'], array_column($results, 'x'));
			$y_labels = $search_class::getLabelsForKeyValues($series['y']['key_select'], array_column($results, 'y'));
			
			// Metric expression?
			if(array_key_exists('x_metric', $series) || array_key_exists('y_metric', $series)) {
				$results = array_map(function($data) use ($series) {
					$tpl_builder = DevblocksPlatform::services()->templateBuilder();
					
					if(array_key_exists('x_metric', $series)) {
						$metric_template = sprintf('{{%s}}',
							$series['x_metric']
						);
						
						$out = $tpl_builder->build($metric_template, [
							'x' => $data['x'],
							'y' => $data['y'],
						]);
						
						if(false === $out || !is_numeric($out)) {
							true;
						} else {
							$data['x'] = floatval($out);
						}
					}
					
					if(array_key_exists('y_metric', $series)) {
						$metric_template = sprintf('{{%s}}',
							$series['y_metric']
						);
						
						$out = $tpl_builder->build($metric_template, [
							'x' => $data['x'],
							'y' => $data['y'],
						]);
						
						if(false === $out || !is_numeric($out)) {
							true;
						} else {
							$data['y'] = floatval($out);
						}
					}
					
					return $data;
					
				}, $results);
			}
			
			$chart_model['series'][$series_idx]['data'] = $results;
			$chart_model['series'][$series_idx]['labels'] = ['x' => $x_labels, 'y' => $y_labels];
		}
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'scatterplot';
		
		switch($format) {
			case 'categories':
				return $this->_formatDataAsCategories($chart_model);
				
			case 'pie':
				return $this->_formatDataAsPie($chart_model);
				
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				
			case 'scatterplot':
				return $this->_formatDataAsScatterplot($chart_model);
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: categories, pie, scatterplot, table",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsCategories($chart_model) : array {
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
			//$y_labels = $series['labels']['y'];
			
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
				'format_params' => [
					'xaxis_key' => 'label',
				]
			]
		];
	}
	
	function _formatDataAsPie($chart_model) : array {
		$series_data = $chart_model['series'];
		
		$response = [];
		
		foreach($series_data as $series) {
			if(!array_key_exists('data', $series))
				continue;
			
			$x_values = array_column($series['data'], 'x');
			$x_labels = $series['labels']['x'];
			
			$y_values = array_column($series['data'], 'y');
			//$y_labels = $series['labels']['y'];
			
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
	
	function _formatDataAsScatterplot($chart_model) : array {
		$series_data = $chart_model['series'];
		
		$response = [];
		
		foreach($series_data as $series) {
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
	
	function _formatDataAsTable($chart_model) : array {
		$series_data = $chart_model['series'];
		
		$rows = $columns = [];
		
		$table = [
			'columns' => &$columns,
			'rows' => &$rows,
		];
		
		// [TODO] What do we do with multiple series?
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
};