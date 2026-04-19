<?php
class _DevblocksDataProviderSampleRecords extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		return [
			'' => [
				[
					'caption' => 'records:',
					'snippet' => "records:(\n  new_york:(name:\"\${1:New York}\" coordinates:[\${2:-73.935242, 40.73061}])\n)",
					'suppress_autocomplete' => true,
				],
				'format:',
			],
			'format:' => [
				'dictionaries',
			]
		];
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$data = [];
		
		foreach($chart_fields as $field) {
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'type') {
				// Do nothing
				DevblocksPlatform::noop();
				
			} else if('records' == $field->key) {
				$records_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$records_query = substr($records_query, 1, -1);
				
				$records_fields = CerbQuickSearchLexer::getFieldsFromQuery($records_query);
				
				foreach($records_fields as $record_field) {
					if(!($record_field instanceof DevblocksSearchCriteria))
						continue;

					$record = [];
					
					$fields_query = CerbQuickSearchLexer::getTokensAsQuery($record_field->tokens);
					$fields_query = substr($fields_query, 1, -1);
					
					$field_defs = CerbQuickSearchLexer::getFieldsFromQuery($fields_query);
					
					foreach($field_defs as $field_def) {
						$oper = $value = null;
						if('T_ARRAY' == $field_def->tokens[0]->type ?? null) {
							CerbQuickSearchLexer::getOperArrayFromTokens($field_def->tokens, $oper, $value);
						} else if('T_GROUP' == $field_def->tokens[0]->type ?? null) {
							$value = [];
						} else {
							CerbQuickSearchLexer::getOperStringFromTokens($field_def->tokens, $oper, $value);
						}
						$record[$field_def->key] = $value;
					}
					
					$data[$record_field->key] = $record;
				}
				
			} else if($field->key == 'format') {
				// Always `dictionaries`
				
			} else {
				$error = sprintf("The parameter '%s' is unknown.", $field->key);
				return false;
			}
		}
		
		return [
			'data' => $data,
			'_' => [
				'type' => 'sample.records',
				'format' => 'dictionaries',
			]
		];
	}
};