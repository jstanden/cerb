<?php
class _DevblocksDataProviderWorklistMetrics extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				[
					'caption' => 'values:',
					'snippet' => "values.\${1:open_tickets}:(\n  of:\${2:ticket}\n  function:\${3:count}\n  field:\${4:id}\n  query:(\n    \${5:status:open}\n  )\n)",
					'suppress_autocomplete' => true,
				],
				'format:',
			],
			'values.*:' => [
				'' => [
					'of:',
					'label:',
					'field:',
					'function:',
					[
						'caption' => 'metric:',
						'snippet' => 'metric:"${1:x}"',
					],
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
				'field:' => [
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
				'table',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.metrics',
			'values' => [],
			'format' => 'table',
		];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			$oper = $value = null;
			
			if($field->key == 'type') {
				// Do nothing
				
			} else if($field->key == 'format') {
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
					$oper = $value = null;
					$values = [];
					
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
						
					} else if($series_field->key == 'metric') {
						CerbQuickSearchLexer::getOperStringFromTokens($series_field->tokens, $oper, $value);
						$series_model['metric'] = $value;
						
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
				
				if($series_context) {
					$view = $series_context->getTempView();
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
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		// Fetch data for each series
		
		if(isset($chart_model['values']))
		foreach($chart_model['values'] as $series_idx => $series) {
			if(!isset($series['context']))
				continue;
			
			$context_ext = Extension_DevblocksContext::get($series['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$view = $context_ext->getTempView();
			$view->addParamsRequiredWithQuickSearch(@$series['query_required']);
			$view->addParamsWithQuickSearch(@$series['query']);
			
			$query_parts = $dao_class::getSearchQueryComponents([], $view->getParams());
			
			$metric_field = null;
			
			switch($series['function']) {
				case 'average':
				case 'avg':
					$metric_field = sprintf("AVG(%s)",
						$series['field']['sql_select']
					);
					break;
					
				case 'count':
					$metric_field = "COUNT(*)";
					break;
					
				case 'max':
					$metric_field = sprintf("MAX(%s)",
						$series['field']['sql_select']
					);
					break;
					
				case 'min':
					$metric_field = sprintf("MIN(%s)",
						$series['field']['sql_select']
					);
					break;
					
				case 'sum':
					$metric_field = sprintf("SUM(%s)",
						$series['field']['sql_select']
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
			
			if(array_key_exists('field', $series))
			switch($series['field']['type']) {
				case Model_CustomField::TYPE_CURRENCY:
					@$currency_id = $series['field']['type_options']['currency_id'];
					
					if(!$currency_id || false == ($currency = DAO_Currency::get($currency_id)))
						break;
					
					switch($series['function']) {
						case 'average':
						case 'avg':
						case 'max':
						case 'min':
						case 'sum':
							$value = $currency->format(intval($value), false, '.', '');
							break;
							
						case 'count':
							break;
					}
					break;
					
				case Model_CustomField::TYPE_DECIMAL:
					@$decimal_at = $series['field']['type_options']['decimal_at'];
					
					switch($series['function']) {
						case 'average':
						case 'avg':
						case 'max':
						case 'min':
						case 'sum':
							$value = DevblocksPlatform::strFormatDecimal(intval($value), $decimal_at, '.', '');
							break;
							
						case 'count':
							break;
					}
					break;
			}
			
			// Metric expression?
			if(array_key_exists('metric', $chart_model['values'][$series_idx])) {
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				
				$metric_template = sprintf('{{%s}}',
					$chart_model['values'][$series_idx]['metric']
				);
				
				$out = $tpl_builder->build($metric_template, [
					'x' => $value,
				]);
				
				if(false === $out || !is_numeric($out)) {
					$value = 0;
				} else {
					$value = floatval($out);
				}
			}
			
			$chart_model['values'][$series_idx]['value'] = $value;
		}
		
		@$format = $chart_model['format'] ?: 'table';
		
		switch($format) {
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				break;
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be: table",
					$format,
					$chart_model['type']
				);
				return false;
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
};