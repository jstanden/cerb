<?php
class _DevblocksDataProviderDataQueryTypes extends _DevblocksDataProvider {
	public function getSuggestions($type, array $params = []) {
		return [
			'' => [
				'format:',
			],
			'format:' => [
				'dictionaries',
			],
		];
	}
	
	public function getData($query, $chart_fields, &$error = null, array $options = []) {
		$chart_model = [
			'type' => 'data.query.types',
			'format' => 'dictionaries',
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
		
		if(false == ($results = $this->_getDataQueryTypes($chart_model, $error))) {
			return [
				'error' => $error
			];
		}
		
		return ['data' => $results, '_' => [
			'type' => 'data.query.types',
			'format' => 'dictionaries',
		]];
	}
	
	private function _getDataQueryTypes(array $chart_model, &$error=null) {
		return DevblocksPlatform::services()->data()->getTypes();
	}
}