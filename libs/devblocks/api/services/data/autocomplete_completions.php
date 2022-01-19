<?php
class _DevblocksDataProviderAutocompleteCompletions extends _DevblocksDataProvider {
	public function getSuggestions($type, array $params = []) {
		return [
			'' => [
				'format:',
				'params:',
				'schema:',
			],
			'format:' => [
				'dictionaries',
			],
			'schema:' => [
				'automation',
				'automation_event',
			],
		];
	}
	
	public function getData($query, $chart_fields, &$error = null, array $options = []) {
		$chart_model = [
			'type' => 'autocomplete.completions',
			'format' => 'dictionaries',
			'schema' => null,
			'params' => [],
		];
		
		$allowed_formats = [
			'dictionaries',
		];
		
		$allowed_schemas = [
			'automation',
			'automation_event',
		];
		
		$params_fields = [];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			// Do nothing
			if($field->key == 'type') {
				continue;
				
			} else if($field->key == 'schema') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$schema = DevblocksPlatform::strLower($value);
				
				if(false === array_search($schema, $allowed_schemas)) {
					$error = sprintf("Unknown `format:` (%s). Must be one of: %s",
						$schema,
						implode(', ', $allowed_schemas)
					);
					return false;
				}
				
				$chart_model['schema'] = $value;
				
			} else if($field->key == 'params') {
				$params_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$params_query = substr($params_query, 1, -1);
				
				$params_fields = CerbQuickSearchLexer::getFieldsFromQuery($params_query);
				
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
		$results = [];
		
		if(empty($chart_model['schema'])) {
			$error =  'The key `schema:` is required.';
			return false;
		}
		
		switch($chart_model['schema']) {
			case 'automation':
				if(false === ($results = $this->_getAutomationAutocompletions($chart_model, $params_fields, $error))) {
					return [
						'error' => $error
					];
				}
				break;
				
			case 'automation_event':
				if(false === ($results = $this->_getAutomationEventAutocompletions($chart_model, $params_fields, $error))) {
					return [
						'error' => $error
					];
				}
				break;
		}
		
		return [
			'data' => $results,
			'_' => [
				'type' => 'autocomplete.completions',
				'schema' => $chart_model['schema'],
				'format' => 'dictionaries',
			]
		];
	}
	
	private function _getAutomationAutocompletions(array $chart_model, array $params_fields=[], &$error=null) {
		$params = [
			'path' => '',
			'trigger' => '',
		];
		
		foreach($params_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			// Do nothing
			if($field->key == 'type') {
				continue;
				
			} else if($field->key == 'path') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$params['path'] = $value;
				
			} else if($field->key == 'trigger') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$params['trigger'] = $value;
			}
		}
		
		$path = implode(':', array_map(
			function($p) {
				if(false !== ($pos = strpos($p, '@')))
					$p = substr($p, 0, $pos);
				
				if(false !== ($pos = strpos($p, '/')))
					$p = substr($p, 0, $pos);
				
				return $p;
			},
			explode(':', $params['path'])
		));
		
		if(false == ($trigger_ext = Extension_AutomationTrigger::get($params['trigger'], true))) {
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
		if(is_iterable($suggestions)) {
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
						
					} else {
						return $suggestion;
					}
				},
				$suggestions
			);
			
			DevblocksPlatform::sortObjects($suggestions, '[caption]', true);
		}
		
		return $suggestions;
	}
	
	private function _getAutomationEventAutocompletions(array $chart_model, array $params_fields=[], &$error=null) {
		$params = [
			'path' => '',
			'trigger' => '',
		];
		
		foreach($params_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			// Do nothing
			if($field->key == 'type') {
				continue;
				
			} else if($field->key == 'path') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$params['path'] = $value;
				
			} else if($field->key == 'trigger') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$params['trigger'] = $value;
			}
		}

		$path = implode(':', array_map(
			function($p) {
				if(false !== ($pos = strpos($p, '@')))
					$p = substr($p, 0, $pos);
				
				if(false !== ($pos = strpos($p, '/')))
					$p = substr($p, 0, $pos);
				
				return $p;
			},
			explode(':', $params['path'])
		));
		
		$possible_suggestions = CerberusApplication::kataAutocompletions()->automationEvent();
		$suggestions = [];
		
		if(array_key_exists($path, $possible_suggestions)) {
			if(!array_key_exists('type', $possible_suggestions[$path]))
				$suggestions = $possible_suggestions[$path];
		
		} elseif(array_key_exists('*', $possible_suggestions)) {
			foreach($possible_suggestions['*'] as $pattern => $results) {
				if(preg_match('#^' . $pattern . '$#', $path)) {
					$suggestions = $results;
					break;
				}
			}
		}
		
		return $suggestions;
	}
}