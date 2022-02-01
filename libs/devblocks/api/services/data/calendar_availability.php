<?php
class _DevblocksDataProviderCalendarAvailability extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions = [
			'' => [
				[
					'caption' => 'calendars:',
					'snippet' => 'calendars:(${1})',
				],
				[
					'caption' => 'range:',
					'snippet' => 'range:"${1:this month}"',
				],
//				[
//					'caption' => 'period:',
//					'snippet' => 'period:${1:hour}',
//				],
				'format:',
			],
			'calendars:' => [],
//			'period:' => [
//				'hour',
//			],
			'format:' => [
				'timeblocks',
				'dictionaries',
			],
		];
		
		if(false == ($context_ext = Extension_DevblocksContext::getByAlias('calendar', true)))
			return [];
		
		if(false == ($view = $context_ext->getTempView()))
			return [];
		
		$of_schema = $view->getQueryAutocompleteSuggestions();
		
		foreach($of_schema as $of_path => $of_suggestions) {
			if('_contexts' == $of_path) {
				if(!array_key_exists('_contexts', $suggestions))
					$suggestions['_contexts'] = [];
				
				foreach($of_suggestions as $ctx_path => $ctx_suggestion) {
					$suggestions['_contexts']['calendar:' . $ctx_path] = $ctx_suggestion;
				}
				
			} else {
				$suggestions['calendar:' . $of_path] = $of_suggestions;
			}
		}
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$chart_model = [
			'type' => 'calendar.availability',
			'range' => 'this month',
			'period' => 'hour',
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type')
				continue;

			if($field->key == 'calendars') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['calendars_query'] = $data_query;
			
			} else if($field->key == 'range') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['range'] = $value;
				
//			} else if($field->key == 'period') {
//				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
//				$chart_model['period'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		$calendar_ids = [];
		
		if(array_key_exists('calendars_query', $chart_model)) {
			if(!empty($chart_model['calendars_query'])) {
				$calendar_ctx = Extension_DevblocksContext::get(Context_Calendar::ID, true);
				
				$view = $calendar_ctx->getTempView();
				$view->renderLimit = 100;
				$view->addParamsWithQuickSearch($chart_model['calendars_query'], true);
				
				$results = $view->getData()[0];
				
				$calendar_ids = array_merge($calendar_ids, array_keys($results));
			} else {
				$calendar_ids = [];
			}
		}
		
		// Sanitize
		
		if(!$chart_model['range']) {
			$error = "The `range:` field is required.";
			return false;
		}
		
		if(!in_array($chart_model['period'], ['hour'])) {
			$error = "The `period:` field must be one of: hour, day";
			return false;
		}
		
		if(false == ($dates = DevblocksPlatform::services()->date()->parseDateRange($chart_model['range'])))
			return false;
		
		$range_from = $dates['from_ts'];
		$range_to = $dates['to_ts'];
		
		$results = [];
		
		$calendars = DAO_Calendar::getIds($calendar_ids);
		
		$period_mins = 'hour' == $chart_model['period'] ? 60 : 1440;
		$date_format = '%Y-%m-%d %H:%M:%S';
		
		$dates = DevblocksPlatform::dateLerpArray(
			[
				strftime($date_format, $range_from),
				strftime($date_format, $range_to)
			],
			$chart_model['period'], 
			1, 
			'%s'
		);
		
		if(!empty($calendars)) {
			foreach($calendars as $calendar) { /* @var Model_Calendar $calendar */
				$events = $calendar->getEvents($range_from, $range_to);
				
				foreach ($dates as $date) {
					if ('hour' == $chart_model['period']) {
						$date_end = strtotime('+1 hour -1 second', $date);
						//				} else if('day' == $chart_model['period']) {
						//					$date_end = strtotime('+1 day -1 second', $date);
					} else {
						$error = "The `period:` field must be one of: hour, day";
						return false;
					}
					
					// [TODO] In hourly, do this once per day (not 24x)
					$tick_events = array_filter($events, function ($ts_day) use ($date, $date_end) {
						return $date >= $ts_day && $date_end <= strtotime('+1 day -1 second', $ts_day);
					}, ARRAY_FILTER_USE_KEY);
					
					$avail = $calendar->computeAvailability($date, $date_end, $tick_events);
					$mins = $avail->getMinutes();
					
					$value = substr_count($mins, '1');
					$results[$date] = ($results[$date] ?? 0) + $value;
				}
			}
			
		} else {
			foreach ($dates as $date) {
				$results[$date] = ($results[$date] ?? 0) + 0;
			}
		}
		
		$results = array_map(
			function($k) use ($results, $period_mins) {
				return [
					'date' => 60 == $period_mins ? strftime('%Y-%m-%dT%H:00:00', $k) : strftime('%Y-%m-%dT00:00:00', $k),
					'value' => $results[$k]
				];
			},
			array_keys($results)
		);
		
		$chart_model['data'] = $results;
		
		unset($results);
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'dictionaries';
		
		switch($format) {
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($chart_model);
				
			case 'timeblocks':
				return $this->_formatDataAsTimeBlocks($chart_model);
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: dictionaries, timeblocks",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsDictionaries($chart_model) {
		return [
			'data' => $chart_model['data'],
			'_' => [
				'type' => 'calendar.availability',
				'calendars_query' => $chart_model['calendars_query'],
				'format' => 'dictionaries',
			]
		];
	}
	
	function _formatDataAsTimeBlocks($chart_model) {
		return [
			'data' => $chart_model['data'] ?? [],
			'_' => [
				'type' => 'calendar.availability',
				'calendars_query' => $chart_model['calendars_query'],
				'format' => 'timeblocks',
			]
		];
	}
};