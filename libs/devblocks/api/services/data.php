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
	
	function executeQuery($query, &$error=null, $cache_secs=0) {
		$cache = DevblocksPlatform::services()->cache();
		$cache_key = 'data_query_' . sha1($query);
		
		if($cache_secs) {
			if(false != ($results = $cache->load($cache_key)))
				return $results;
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
		
		if($cache_secs)
			$cache->save($results, $cache_key, [], $cache_secs);
		
		return $results;
	}
}