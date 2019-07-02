<?php
abstract class _DevblocksDataProvider {
	abstract function getData($query, $chart_fields, &$error=null, array $options=[]);
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
	
	function getTypeMeta($type, $params) {
		switch($type) {
			case 'worklist.records':
				@$of = $params['of'];
				
				if($of) {
					if(
						false == ($context_ext = Extension_DevblocksContext::getByAlias($of, true))
						|| false == ($view = $context_ext->getTempView())) 
					{
						$of = null;
					} else {
						$of_schema = $view->getQueryAutocompleteSuggestions();
					}
				}
				
				if(!$of) {
					$suggestions = [
						'' => [
							'of:',
						],
						'type:' => $this->getTypes(),
						'of:' => array_values(Extension_DevblocksContext::getUris()),
					];
					return $suggestions;
				}
				
				$suggestions  = [
					'' => [
						'of:',
						[
							'caption' => 'query:',
							'snippet' => 'query:(${1})',
						],
						[
							'caption' => 'query.required:',
							'snippet' => 'query.required:(${1})',
						],
						'format:',
						[
							'caption' => 'expand:',
							'snippet' => 'expand:[${1}]',
						],
						'page:',
					],
					'of:' => array_values(Extension_DevblocksContext::getUris()),
					'query:' => [],
					'query.required:' => [],
					'page:' => [
						[
							'caption' => '(number)',
							'snippet' => '${1:0}',
						]
					],
					'format:' => [
						'dictionaries',
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
					}
				}
				
				return $suggestions;
				break;
				
			case 'worklist.subtotals':
				@$of = $params['of'];
				
				// Sanitize `of:`
				if($of) {
					if(
						false == ($context_ext = Extension_DevblocksContext::getByAlias($of, true))
						|| false == ($view = $context_ext->getTempView())) 
					{
						$of = null;
					} else {
						$of_schema = $view->getQueryAutocompleteSuggestions();
					}
				}
				
				if(!$of) {
					$suggestions = [
						'' => [
							'of:',
						],
						'type:' => $this->getTypes(),
						'of:' => array_values(Extension_DevblocksContext::getUris()),
					];
					return $suggestions;
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
							$suggestions['by:'] = $of_suggestions;
							$suggestions['by.count:'] = $of_suggestions;
							$suggestions['by.avg:'] = $of_suggestions;
							$suggestions['by.sum:'] = $of_suggestions;
							$suggestions['by.min:'] = $of_suggestions;
							$suggestions['by.max:'] = $of_suggestions;
						}
					}
				}
				
				return $suggestions;
				break;
				
			case 'worklist.series':
				return [
					'' => [
						[
							'caption' => 'series:',
							'snippet' => "series.\${1:alias}:(\n  of:\${2:ticket}\n  x:\${3:id}\n  function:\${4:count}\n  query:(\n    \${5}\n  )\n)",
							'suppress_autocomplete' => true,
						],
						'x.label:',
						'format:',
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
					'format:' => [
						'table',
						'timeseries',
					]
				];
				break;
				
			case 'worklist.metrics':
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
				break;
				
			case 'worklist.xy':
				return [
					'' => [
						[
							'caption' => 'series:',
							'snippet' => "series.\${1:alias}:(\n  of:\${2:message}\n  x:\${3:worker}\n  y:\${4:responseTime}\n  query:(\n    \${5:responseTime:>0 sort:responseTime limit:10}\n  )\n)",
							'suppress_autocomplete' => true,
						],
						'format:',
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
				break;
				
			case 'worklist.geo.points':
				return [
					'' => [
						[
							'caption' => 'series:',
							'snippet' => "series.\${1:alias}:(\n  of:\${2:org}\n  point:\${3:coordinates}\n  fields:[\${4:name,coordinates}]\n  query:(\n    \${5:coordinates:!null}\n  )\n)",
							'suppress_autocomplete' => true,
						],
						'format:',
					],
					'series.*:' => [
						'' => [
							'of:',
							[
								'caption' => 'point:',
								'snippet' => 'point:${1:coordinates}',
							],
							[
								'caption' => 'fields:',
								'snippet' => 'fields:[${1:name,coordinates}]',
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
						'point:' => [
							'_type' => 'series_of_field',
							'of_types' => 'geo_point',
						],
						'fields:' => [
							'_type' => 'series_of_field',
						],
						'query:' => [
							'_type' => 'series_of_query',
						],
						'query.required:' => [
							'_type' => 'series_of_query',
						],
					],
					'format:' => [
						'geojson',
						'table',
						//'dictionaries', // [TODO]
					]
				];
				break;
				
			case 'sample.geo.points':
				return [
					'' => [
						[
							'caption' => 'series:',
							'snippet' => "series.\${1:cities}:(\n  new_york:(name:\"\${2:New York}\" coordinates:\"POINT(\${3:-73.935242 40.73061})\")\n)",
							'suppress_autocomplete' => true,
						],
						'format:',
					],
					'series.*:' => [
						'' => [
							'label:',
							'y.min:',
							'y.max:',
							'trend:',
						],
						'label:' => [],
						'y.min:' => [],
						'y.max:' => [],
						'trend:' => [
							'down',
							'up',
							'random',
						],
					],
					'format:' => [
						'geojson',
					]
				];
				break;
				
			case 'sample.timeseries':
				return [
					'' => [
						'x.count:',
						'x.unit:',
						[
							'caption' => 'series:',
							'snippet' => "series.\${1:alias}:(\n  label:\"\${2:# Tickets}\"\n  y.min:\${3:1000}\n  y.max:\${4:100000}\n  trend:\${5:random}\n)",
							'suppress_autocomplete' => true,
						],
						'format:',
					],
					'series.*:' => [
						'' => [
							'label:',
							'y.min:',
							'y.max:',
							'trend:',
						],
						'label:' => [],
						'y.min:' => [],
						'y.max:' => [],
						'trend:' => [
							'down',
							'up',
							'random',
						],
					],
					'x.count:' => [],
					'x.unit:' => [
						'days',
						'weeks',
						'months',
						'years',
					],
					'format:' => [
						'timeseries',
					]
				];
				break;
				
			case 'sample.xy':
				return [
					'' => [
						[
							'caption' => 'series:',
							'snippet' => "series.\${1:alias}:(\n  label:\"\${2:Enterprise}\"\n  samples:\${3:100}\n  trend:\${4:up}\n  x.min:\${5:120}\n  x.max:\${6:172800}\n  y.min:\${7:0}\n  y.max:\${8:100}\n)",
							'suppress_autocomplete' => true,
						],
						'format:',
					],
					'series.*:' => [
						'' => [
							'label:',
							'samples:',
							'trend:',
							'x.min:',
							'x.max:',
							'y.min:',
							'y.max:',
						],
						'label:' => [],
						'samples:' => [],
						'trend:' => [
							'down',
							'up',
							'random',
						],
						'x.min:' => [],
						'x.max:' => [],
						'y.min:' => [],
						'y.max:' => [],
					],
					'format:' => [
						'scatterplot',
					]
				];
				break;
				
			case 'usage.behaviors':
				return [
					'' => [
						'format:',
					],
					'format:' => [
						'table',
						'timeseries',
					]
				];
				break;
				
			case 'usage.snippets':
				return [
					'' => [
						'format:',
					],
					'format:' => [
						'table',
						'timeseries',
					]
				];
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($type, 'behavior.')) {
					$behavior_uri = substr($type, 9);
					
					if(false == ($behavior = Event_DataQueryDatasource::getByAlias($behavior_uri)))
						break;
					
					$schema = [
						'' => [],
					];
					
					foreach($behavior->variables as $var) {
						if($var['is_private'])
							continue;
						
						$var_key = substr($var['key'], 4) . ':';
						
						$schema[''][] = [
							'value' => $var_key,
						];
						
						// [TODO] Types
						$schema[$var_key][] = [
							'caption' => '(' . $var['label'] . ')',
							'snippet' => '"${1:' . $var['type'] . '}"',
						];
					}
					
					return $schema;
				}
				break;
		}
		
		return [];
	}
	
	function getTypes() {
		$types = [
			'worklist.records',
			'worklist.subtotals',
			'worklist.series',
			'worklist.metrics',
			'worklist.xy',
			'worklist.geo.points',
			'sample.geo.points',
			'sample.timeseries',
			'sample.xy',
			'usage.behaviors',
			'usage.snippets',
		];
		
		$behaviors = DAO_TriggerEvent::getByEvent(Event_DataQueryDatasource::ID);
		foreach($behaviors as $behavior) {
			if(false == ($alias = $behavior->event_params['alias']))
				continue;
			
			$types[] = 'behavior.' . $alias;
		}
		
		return $types;
	}
	
	function executeQuery($query, &$error=null, $cache_secs=0) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = 'data_query_' . sha1($query);
		
		if($cache_secs) {
			if(
				false != ($results = $cache->load($cache_key))
				&& is_array($results)
				&& array_key_exists('results', $results)
			) {
				return $results['results'];
			}
		}
		
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
		
		$oper = $chart_type = null;
		
		CerbQuickSearchLexer::getOperStringFromTokens($type_field->tokens, $oper, $chart_type);
		
		$results = [];
		
		switch($chart_type) {
			case 'sample.geo.points':
				$provider = new _DevblocksDataProviderSampleGeoPoints();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'sample.timeseries':
				$provider = new _DevblocksDataProviderSampleTimeSeries();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'sample.xy':
				$provider = new _DevblocksDataProviderSampleXy();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'usage.behaviors':
				$provider = new _DevblocksDataProviderUsageBotBehaviors();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'usage.snippets':
				$provider = new _DevblocksDataProviderUsageSnippets();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'worklist.geo.points':
				$provider = new _DevblocksDataProviderWorklistGeoPoints();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'worklist.metrics':
				$provider = new _DevblocksDataProviderWorklistMetrics();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'worklist.records':
				$provider = new _DevblocksDataProviderWorklistRecords();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'worklist.series':
				$provider = new _DevblocksDataProviderWorklistSeries();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'worklist.subtotals':
				$provider = new _DevblocksDataProviderWorklistSubtotals();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'worklist.xy':
				$provider = new _DevblocksDataProviderWorklistXy();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			default:
				if(DevblocksPlatform::strStartsWith($chart_type, 'behavior.')) {
					$behavior_alias = substr($chart_type, 9);
					$provider = new _DevblocksDataProviderBotBehavior();
					
					if(false === ($results = $provider->getData($query, $chart_fields, $error, ['behavior_alias' => $behavior_alias])))
						return false;
					
					break;
				}
				
				$error = sprintf("'%s' is not a known data query type.", $chart_type);
				return false;
				break;
		}
		
		if($cache_secs) {
			$cache->save(['results' => $results], $cache_key, [], $cache_secs);
		}
		
		return $results;
	}
	
	function stripQuotes($string) {
		return '"' . str_replace('"','', $string) . '"';
	}
}