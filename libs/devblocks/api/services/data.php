<?php
abstract class _DevblocksDataProvider {
	abstract function getSuggestions($type, array $params=[]);
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
			case 'calendar.events':
				$provider = new _DevblocksDataProviderCalendarEvents();
				return $provider->getSuggestions($type, $params);
				break;

			case 'classifier.prediction':
				$provider = new _DevblocksDataProviderClassifierPrediction();
				return $provider->getSuggestions($type, $params);
				break;

			case 'worklist.records':
				$provider = new _DevblocksDataProviderWorklistRecords();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'worklist.subtotals':
				$provider = new _DevblocksDataProviderWorklistSubtotals();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'worklist.series':
				$provider = new _DevblocksDataProviderWorklistSeries();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'worklist.metrics':
				$provider = new _DevblocksDataProviderWorklistMetrics();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'worklist.xy':
				$provider = new _DevblocksDataProviderWorklistXy();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'worklist.geo.points':
				$provider = new _DevblocksDataProviderWorklistGeoPoints();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'gpg.keyinfo':
				$provider = new _DevblocksDataProviderGpgKeyInfo();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'sample.geo.points':
				$provider = new _DevblocksDataProviderSampleGeoPoints();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'sample.timeseries':
				$provider = new _DevblocksDataProviderSampleTimeSeries();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'sample.xy':
				$provider = new _DevblocksDataProviderSampleXy();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'usage.behaviors':
				$provider = new _DevblocksDataProviderUsageBotBehaviors();
				return $provider->getSuggestions($type, $params);
				break;
				
			case 'usage.snippets':
				$provider = new _DevblocksDataProviderUsageSnippets();
				return $provider->getSuggestions($type, $params);
				break;
			
			default:
				if(DevblocksPlatform::strStartsWith($type, 'behavior.')) {
					$provider = new _DevblocksDataProviderBotBehavior();
					return $provider->getSuggestions($type, $params);
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
			'calendar.events',
			'classifier.prediction',
			'gpg.keyinfo',
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
		
		switch($chart_type) {
			case 'calendar.events':
				$provider = new _DevblocksDataProviderCalendarEvents();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'classifier.prediction':
				$provider = new _DevblocksDataProviderClassifierPrediction();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'gpg.keyinfo':
				$provider = new _DevblocksDataProviderGpgKeyInfo();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
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