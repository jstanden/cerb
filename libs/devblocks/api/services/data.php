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

			case 'classifier.prediction':
				$provider = new _DevblocksDataProviderClassifierPrediction();
				return $provider->getSuggestions($type, $params);

			case 'worklist.records':
				$provider = new _DevblocksDataProviderWorklistRecords();
				return $provider->getSuggestions($type, $params);
				
			case 'worklist.subtotals':
				$provider = new _DevblocksDataProviderWorklistSubtotals();
				return $provider->getSuggestions($type, $params);
				
			case 'worklist.series':
				$provider = new _DevblocksDataProviderWorklistSeries();
				return $provider->getSuggestions($type, $params);
				
			case 'worklist.metrics':
				$provider = new _DevblocksDataProviderWorklistMetrics();
				return $provider->getSuggestions($type, $params);
				
			case 'worklist.xy':
				$provider = new _DevblocksDataProviderWorklistXy();
				return $provider->getSuggestions($type, $params);
				
			case 'worklist.geo.points':
				$provider = new _DevblocksDataProviderWorklistGeoPoints();
				return $provider->getSuggestions($type, $params);
				
			case 'gpg.keyinfo':
				$provider = new _DevblocksDataProviderGpgKeyInfo();
				return $provider->getSuggestions($type, $params);
				
			case 'platform.extensions':
				$provider = new _DevblocksDataProviderPlatformExtensions();
				return $provider->getSuggestions($type, $params);
				
			case 'record.fields':
				$provider = new _DevblocksDataProviderRecordFields();
				return $provider->getSuggestions($type, $params);
				
			case 'record.types':
				$provider = new _DevblocksDataProviderRecordTypes();
				return $provider->getSuggestions($type, $params);
				
			case 'sample.geo.points':
				$provider = new _DevblocksDataProviderSampleGeoPoints();
				return $provider->getSuggestions($type, $params);
				
			case 'sample.timeseries':
				$provider = new _DevblocksDataProviderSampleTimeSeries();
				return $provider->getSuggestions($type, $params);
				
			case 'sample.xy':
				$provider = new _DevblocksDataProviderSampleXy();
				return $provider->getSuggestions($type, $params);
				
			case 'usage.behaviors':
				$provider = new _DevblocksDataProviderUsageBotBehaviors();
				return $provider->getSuggestions($type, $params);
			
			case 'ui.icons':
				$provider = new _DevblocksDataProviderUiIcons();
				return $provider->getSuggestions($type, $params);
				
			case 'usage.snippets':
				$provider = new _DevblocksDataProviderUsageSnippets();
				return $provider->getSuggestions($type, $params);
			
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
			'platform.extensions',
			'record.fields',
			'record.types',
			'sample.geo.points',
			'sample.timeseries',
			'sample.xy',
			'ui.icons',
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
	
	function executeQuery($query, array $bindings=[], &$error=null, $cache_secs=0) {
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
		
		$chart_fields = CerbQuickSearchLexer::getFieldsFromQuery($query, $bindings);
		
		if(false == ($type_field = CerbQuickSearchLexer::getFieldByKey('type', $chart_fields))) {
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
				
			case 'platform.extensions':
				$provider = new _DevblocksDataProviderPlatformExtensions();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'record.fields':
				$provider = new _DevblocksDataProviderRecordFields();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'record.types':
				$provider = new _DevblocksDataProviderRecordTypes();
				
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
			
			case 'ui.icons':
				$provider = new _DevblocksDataProviderUiIcons();
				
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
	
	public function parseQuery($query) {
		$oper = $query_type = null;
		$query_fields = CerbQuickSearchLexer::getFieldsFromQuery($query);
		
		if(false != ($type_field = CerbQuickSearchLexer::getFieldByKey('type', $query_fields)))
			CerbQuickSearchLexer::getOperStringFromTokens($type_field->tokens, $oper, $query_type);
		
		return [
			'type' => $query_type,
		];
	}
	
	public function generatePaging($data, $total, $limit, $page) {
		$paging = [
			'page' => [
				'of' => intval(ceil($total / $limit)),
				'rows' => [
					'of' => intval($total),
					'count' => count($data),
					'limit' => intval($limit),
				],
			]
		];
		
		$paging['page']['index'] = DevblocksPlatform::intClamp($page, 0, PHP_INT_MAX);
		
		$paging['page']['rows']['from'] = $paging['page']['index'] * $paging['page']['rows']['limit'] + 1;
		$paging['page']['rows']['to'] = min($paging['page']['rows']['from']+$paging['page']['rows']['limit'] - 1, $paging['page']['rows']['of']);
		
		if($paging['page']['rows']['from'] > $paging['page']['rows']['of']) {
			$paging['page']['rows']['from'] = 0;
			$paging['page']['rows']['to'] = 0;
		}
		
		if($paging['page']['index'] - 1 >= 0) {
			$paging['page']['prev'] = $paging['page']['index'] - 1;
			$paging['page']['first'] = 0;
		}
		
		if($paging['page']['index'] + 1 < $paging['page']['of']) {
			$paging['page']['next'] = $paging['page']['index'] + 1;
			$paging['page']['last'] = $paging['page']['of']-1;
		}
		
		return $paging;
	}
}