<?php
class _DevblocksDataProviderCalendarEvents extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$suggestions = [
			'' => [
				[
					'caption' => 'calendar:',
					'snippet' => 'calendar:(${1})',
				],
				[
					'caption' => 'from:',
					'snippet' => 'from:"${1:Jan 1}"',
				],
				[
					'caption' => 'to:',
					'snippet' => 'to:"${1:Dec 31}"',
				],
				[
					'caption' => 'expand:',
					'snippet' => 'expand:[${1}]',
				],
				'format:',
			],
			'calendar:' => [],
			'format:' => [
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
			'type' => 'calendar.events',
			'from' => '',
			'to' => '',
			'expand' => [],
			'format' => 'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;

			if($field->key == 'type') {
				// Do nothing
				
			} else if($field->key == 'calendar') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['calendar_query'] = $data_query;
			
			} else if($field->key == 'from') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['from'] = $value;
				
			} else if($field->key == 'to') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['to'] = $value;
				
			} else if($field->key == 'expand') {
				CerbQuickSearchLexer::getOperArrayFromTokens($field->tokens, $oper, $value);
				$chart_model['expand'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		$calendar_ids = [];
		
		if(array_key_exists('calendar_query', $chart_model)) {
			$calendar_ctx = Extension_DevblocksContext::get(Context_Calendar::ID, true);
			
			$view = $calendar_ctx->getTempView();
			$view->addParamsWithQuickSearch($chart_model['calendar_query'], true);
			
			$results = $view->getData()[0];
			
			$calendar_ids = array_merge($calendar_ids, array_keys($results));
		}
		
		// Sanitize
		
		if(!$calendar_ids) {
			$error = "The `calendar:` field is required.";
			return false;
		}
		
		if(!$chart_model['from']) {
			$error = "The `from:` field is required.";
			return false;
		}
		
		if(!$chart_model['to'])
			$chart_model['to'] = $chart_model['from'];
		
		if(false === ($from =  strtotime($chart_model['from'])))
			return false;
		
		if(false === ($to = strtotime($chart_model['to'])))
			return false;
		
		$events = [];
		$event_ptrs = [];
		
		foreach($calendar_ids as $calendar_id) {
			if(false == ($calendar = DAO_Calendar::get($calendar_id)))
				continue;
			
			$calendar_events = $calendar->getEvents($from, $to);
			
			foreach($calendar_events as $ts => $ts_events) {
				foreach ($ts_events as $ev_idx => $ev) {
					$ev['record__context'] = $ev['context'];
					$ev['record_id'] = $ev['context_id'];
					
					$ev['calendar__context'] = CerberusContexts::CONTEXT_CALENDAR;
					$ev['calendar_id'] = $calendar_id;
					
					unset($ev['context']);
					unset($ev['context_id']);
					unset($ev['link']);
					
					$ts_events[$ev_idx] = DevblocksDictionaryDelegate::instance($ev);
					$event_ptrs[] =& $ts_events[$ev_idx];
				}
				
				if(!array_key_exists($ts, $events)) {
					$events[$ts] = $ts_events;
				} else {
					$events[$ts] = array_merge($events[$ts], $ts_events);
				}
			}
		}
		
		if($event_ptrs && is_array($chart_model['expand'])) {
			foreach($chart_model['expand'] as $expand_key) {
				DevblocksDictionaryDelegate::bulkLazyLoad($event_ptrs, $expand_key);
			}
		}
		
		$chart_model['data'] = ['events' => $events];
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'dictionaries';
		
		switch($format) {
			case 'dictionaries':
				return $this->_formatDataAsDictionaries($chart_model);
				break;
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: dictionaries",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsDictionaries($chart_model) {
		$meta = [
			'data' => $chart_model['data'],
			'_' => [
				'type' => 'calendar.events',
				'format' => 'dictionaries',
			]
		];
		
		return $meta;
	}
};