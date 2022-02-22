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
			$error = "The `period:` field must be one of: hour";
			return false;
		}
		
		if(false == ($hours = DevblocksPlatform::services()->date()->parseDateRange($chart_model['range'])))
			return false;
		
		$range_from = $hours['from_ts'];
		$range_to = $hours['to_ts'];
		
		$results = [];
		
		$calendars = DAO_Calendar::getIds($calendar_ids);
		
		$period_mins = 'hour' == $chart_model['period'] ? 60 : 1440;
		$date_format = 'Y-m-d H:i:s';
		
		$dt_from = new DateTime();
		$dt_from->setTimestamp($range_from);
		
		$dt_to = new DateTime();
		$dt_to->setTimestamp($range_to);
		
		$hours = DevblocksPlatform::dateLerpArray(
			[
				$dt_from->format($date_format),
				$dt_to->format($date_format),
			],
			$chart_model['period'] 
		);
		
		foreach ($hours as $hour) {
			$results[$hour] = 0;
		}
		
		unset($hours);
		
		$months = DevblocksPlatform::dateLerpArray(
			[
				$dt_from->format('Y-m-01'),
				$dt_to->format('Y-m-01'),
			],
			'month'
		);
		
		if(!empty($calendars)) {
			foreach($calendars as $calendar) { /* @var Model_Calendar $calendar */
				// Loop through each calendar month in our range as a page
				foreach($months as $ts_month) {
					$calendar_properties = DevblocksCalendarHelper::getCalendar(date('m', $ts_month), date('Y', $ts_month), true);
					
					// Get base events for this range
					$events = $calendar->getEvents($range_from, $range_to);
					
					$avail = $calendar->computeAvailability($calendar_properties['date_range_from'], $calendar_properties['date_range_to'], $events);
				
					// Split events that span day boundaries
					$events = $avail->getAsCalendarEvents($calendar_properties);
					
					foreach($events as $ts_day => $models) {
						$ts_day_end = strtotime('+1 day -1 second', $ts_day);
						
						$dt_day_from = new DateTime();
						$dt_day_from->setTimestamp($ts_day);
						
						$dt_day_to = new DateTime();
						$dt_day_to->setTimestamp($ts_day_end);
						
						$day_hours = DevblocksPlatform::dateLerpArray(
							[
								date($date_format, $ts_day),
								$dt_day_to->format($date_format),
							],
							'hour'
						);
						
						foreach($day_hours as $hour) {
							if(!array_key_exists($hour, $results))
								continue;
							
							$hour_end = strtotime('+1 hour -1 second', $hour);
							$avail = $calendar->computeAvailability($hour, $hour_end, [$ts_day => $models]);
							
							$mins = $avail->getMinutes();
							
							$value = substr_count($mins, '1');
							$results[$hour] += $value;
						}
					}					
				}
			}
		}
		
		$results = array_map(
			function($k) use ($results, $period_mins) {
				return [
					'date' => 60 == $period_mins ? (date('Y-m-d\TH', $k).':00:00') : (date('Y-m-d', $k).'T00:00:00'),
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