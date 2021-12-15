<?php
class _DevblocksDataProviderAutomationAutocomplete extends _DevblocksDataProvider {
	public function getSuggestions($type, array $params = []) {
		$automation_triggers = Extension_AutomationTrigger::getAll(false);
		
		return [
			'' => [
				'format:',
				'path:',
				'trigger:',
			],
			'format:' => [
				'dictionaries',
			],
			'path' => [
				'start:some:key:path:',
			],
			'trigger:' => array_keys($automation_triggers),
		];
	}
	
	public function getData($query, $chart_fields, &$error = null, array $options = []) {
		$chart_model = [
			'type' => 'automation.autocomplete',
			'format' => 'dictionaries',
			'trigger' => null,
			'path' => null,
		];
		
		$allowed_formats = [
			'dictionaries',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			// Do nothing
			if($field->key == 'type') {
				continue;
				
			} else if($field->key == 'path') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['path'] = $value;
				
			} else if($field->key == 'trigger') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['trigger'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$format = DevblocksPlatform::strLower($value);
				
				if(false === array_search($format, $allowed_formats)) {
					$error = sprintf("Unknown `format:` (%s). Must be one of: %s",
						$format,
						implode(', ', $allowed_formats)
					);
					return false;
				}
				
				$chart_model['format'] = $format;
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		$error = null;
		
		if(false === ($results = $this->_getAutomationAutocompletions($chart_model, $error))) {
			return [
				'error' => $error
			];
		}
		
		return ['data' => $results, '_' => [
			'type' => 'automation.autocomplete',
			'format' => 'dictionaries',
		]];
	}
	
	private function _getAutomationAutocompletions(array $chart_model, &$error=null) {
		$path = implode(':', array_map(
			function($p) {
				if(false !== ($pos = strpos($p, '@')))
					$p = substr($p, 0, $pos);
				
				if(false !== ($pos = strpos($p, '/')))
					$p = substr($p, 0, $pos);
				
				return $p;
			},
			explode(':', $chart_model['path'] ?? '')
		));
		
		if(false == ($trigger_ext = Extension_AutomationTrigger::get($chart_model['trigger'] ?? null, true))) {
			return [];
		}
		
		/** @var $trigger_ext Extension_AutomationTrigger */
		
		$possible_suggestions = json_decode($trigger_ext->getAutocompleteSuggestionsJson(), true);
		$suggestions = [];
		
		if(array_key_exists($path, $possible_suggestions)) {
			$suggestions = $possible_suggestions[$path];
		
		} elseif(array_key_exists('*', $possible_suggestions)) {
			foreach($possible_suggestions['*'] as $pattern => $results) {
				if(preg_match('#^' . $pattern . '$#', $path)) {
					$suggestions = $results;
					break;
				}
			}
		}
		
		// Normalize suggestions
		$suggestions = array_map(
			function($suggestion) {
				if(is_array($suggestion)) {
					if(!array_key_exists('score', $suggestion))
						$suggestion['score'] = 1000;
					
					return $suggestion;
					
				} else if(is_string($suggestion)) {
					return [
						'caption' => $suggestion,
						'snippet' => $suggestion,
						'score' => 1000,
					];
				}
			},
			$suggestions
		);
		
		DevblocksPlatform::sortObjects($suggestions, '[caption]', true);
		
		return $suggestions;
	}
}