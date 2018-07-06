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
				
			default:
				throw new Exception_DevblocksValidationError("A valid chart type is required.");
				break;
		}
		
		return $results;
	}
}