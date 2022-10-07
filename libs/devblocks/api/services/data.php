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
			case 'attachment.manifest':
				$provider = new _DevblocksDataProviderAttachmentManifest();
				return $provider->getSuggestions($type, $params);
			
			case 'autocomplete.completions':
				$provider = new _DevblocksDataProviderAutocompleteCompletions();
				return $provider->getSuggestions($type, $params);
			
			case 'calendar.availability':
				$provider = new _DevblocksDataProviderCalendarAvailability();
				return $provider->getSuggestions($type, $params);

			case 'calendar.events':
				$provider = new _DevblocksDataProviderCalendarEvents();
				return $provider->getSuggestions($type, $params);

			case 'classifier.prediction':
				$provider = new _DevblocksDataProviderClassifierPrediction();
				return $provider->getSuggestions($type, $params);
			
			case 'data.query.types':
				$provider = new _DevblocksDataProviderDataQueryTypes();
				return $provider->getSuggestions($type, $params);
			
			case 'metrics.timeseries':
				$provider = new _DevblocksDataProviderMetricsTimeseries();
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
				
			case 'platform.extension.points':
				$provider = new _DevblocksDataProviderPlatformExtensionPoints();
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
			[
				'name' => 'attachment.manifest',
				'description' => 'Read and filter file contents from archives',
			],
			[
				'name' => 'autocomplete.completions',
				'description' => 'Autocomplete completions for a schema',
			],
			[
				'name' => 'calendar.availability',
				'description' => 'Combined availability from calendars',
			],
			[
				'name' => 'calendar.events',
				'description' => 'Get events and recurring events from calendars',
			],
			[
				'name' => 'classifier.prediction',
				'description' => 'Predict classification for text with a classifier',
			],
			[
				'name' => 'data.query.types',
				'description' => 'Get metadata for data query types',
			],
			[
				'name' => 'gpg.keyinfo',
				'description' => 'Get info for a PGP public key',
			],
			[
				'name' => 'metrics.timeseries',
				'description' => 'Chart and aggregate time-based metrics',
			],
			[
				'name' => 'platform.extensions',
				'description' => 'Filterable and pageable list of plugin extensions for a given hook',
			],
			[
				'name' => 'platform.extension.points',
				'description' => 'Filterable and pageable list of plugin extensions hooks',
			],
			[
				'name' => 'record.fields',
				'description' => 'Filterable and pageable list of fields from a record type',
			],
			[
				'name' => 'record.types',
				'description' => 'Filterable and pageable list of record types',
			],
			[
				'name' => 'sample.geo.points',
				'description' => 'Simulated GeoJSON data',
			],
			[
				'name' => 'sample.timeseries',
				'description' => 'Simulated time-series data',
			],
			[
				'name' => 'sample.xy',
				'description' => 'Simulated X/Y data for scatterplots',
			],
			[
				'name' => 'ui.icons',
				'description' => 'Filterable and pageable list of icons',
			],
			[
				'name' => 'usage.behaviors',
				'description' => 'Historical usage data for bot behaviors',
			],
			[
				'name' => 'usage.snippets',
				'description' => 'Historical usage data for snippets',
			],
			[
				'name' => 'worklist.geo.points',
				'description' => 'Geolocation data from worklist records',
			],
			[
				'name' => 'worklist.metrics',
				'description' => 'Computed metrics based on worklist data',
			],
			[
				'name' => 'worklist.records',
				'description' => 'Record dictionaries with a search query',
			],
			[
				'name' => 'worklist.series',
				'description' => 'Series-based data from any worklist',
			],
			[
				'name' => 'worklist.subtotals',
				'description' => 'Aggregations on worklist record fields',
			],
			[
				'name' => 'worklist.xy',
				'description' => 'Compute clusters of two dimensional data from records',
			],
		];
		
		$behaviors = DAO_TriggerEvent::getByEvent(Event_DataQueryDatasource::ID);
		foreach($behaviors as $behavior) {
			if(false == ($alias = $behavior->event_params['alias']))
				continue;
			
			$types[] = [
				'name' => 'behavior.' . $alias,
				'description' => $behavior->title ?? '',
			];
		}
		
		$types = array_combine(
			array_column($types, 'name'),
			$types
		);
		
		DevblocksPlatform::sortObjects($types, '[name]', true);
		
		return $types;
	}
	
	function executeQuery($query, array $bindings=[], &$error=null, $cache_secs=0) {
		if(!is_string($query)) {
			$error = "The data query must be a string.";
			return false;
		}
		
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
			case 'attachment.manifest':
				$provider = new _DevblocksDataProviderAttachmentManifest();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;

			case 'autocomplete.completions':
				$provider = new _DevblocksDataProviderAutocompleteCompletions();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;

			case 'calendar.availability':
				$provider = new _DevblocksDataProviderCalendarAvailability();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
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
				
			case 'data.query.types':
				$provider = new _DevblocksDataProviderDataQueryTypes();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'metrics.timeseries':
				$provider = new _DevblocksDataProviderMetricsTimeseries();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'gpg.keyinfo':
				$provider = new _DevblocksDataProviderGpgKeyInfo();
				
				if(false === ($results = $provider->getData($query, $chart_fields, $error)))
					return false;
				
				break;
				
			case 'platform.extension.points':
				$provider = new _DevblocksDataProviderPlatformExtensionPoints();
				
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
		}
		
		if($cache_secs) {
			$cache->save(['results' => $results], $cache_key, [], $cache_secs);
		}
		
		return $results;
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