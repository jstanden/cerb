<?php
class _DevblocksDataProviderAutomationInvoke extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) : array {
		$suggestions = [
			'' => [
				[
					'caption' => 'name:',
					'snippet' => 'name:',
					'description' => 'Invoke a data.query automation',
					'score' => 2000,
				],
				[
					'caption' => 'inputs:',
					'snippet' => 'inputs:',
					'score' => 1999,
				],
				[
					'caption' => 'format:',
					'snippet' => 'format:',
					'score' => 1998,
				],
			],
			'format:' => [
				'dictionaries',
			],
			'inputs:' => [],
		];
		
		$automations = DAO_Automation::getWhere(
			sprintf('%s = %s',
				Cerb_ORMHelper::escape(DAO_Automation::EXTENSION_ID),
				Cerb_ORMHelper::qstr(AutomationTrigger_DataQuery::ID)
			)
		);
		
		if(is_array($automations))
			$suggestions['name:'] = array_column($automations, 'name');
		
		return $suggestions;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$automator = DevblocksPlatform::services()->automation();
		
		$automation_name = null;
		$automation_inputs = [];
		$query_format = null;
		
		foreach($chart_fields as $query_field) {
			if($query_field->key == 'type') {
				continue;
				
			} else if ('format' == $query_field->key) {
				CerbQuickSearchLexer::getOperStringFromTokens($query_field->tokens, $oper, $value);
				$query_format = $value;
				
			} else if ('inputs' == $query_field->key) {
				$inputs_query = CerbQuickSearchLexer::getTokensAsQuery($query_field->tokens);
				$inputs_query = substr($inputs_query, 1, -1);
				
				$input_fields = CerbQuickSearchLexer::getFieldsFromQuery($inputs_query);
				
				foreach($input_fields as $input_field) {
					if('T_ARRAY' == $input_field->tokens[0]->type) {
						CerbQuickSearchLexer::getOperArrayFromTokens($input_field->tokens, $oper, $value);
					} else {
						CerbQuickSearchLexer::getOperStringFromTokens($input_field->tokens, $oper, $value);
					}
					
					$automation_inputs[$input_field->key] = $value;
				}
				
			} else if ('name' == $query_field->key) {
				CerbQuickSearchLexer::getOperStringFromTokens($query_field->tokens, $oper, $value);
				$automation_name = $value;
				
			} else {
				$error = sprintf("Unknown parameter `%s`", $query_field->key);
				return false;
			}
		}
		
		if(!$automation_name) {
			$error = "An automation `name:` is required";
			return false;
		}
		
		if(!($automation = DAO_Automation::getByUri($automation_name, AutomationTrigger_DataQuery::ID))) {
			$error = sprintf("Unknown automation `%s`", $automation_name);
			return false;
		}
		
		$error = null;
		
		$initial_state = [
			'inputs' => $automation_inputs,
			'query_format' => $query_format,
		];
		
		if(!($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
			$error = '[Automation error] ' . $error;
			return false;
		}
		
		return [
			'data' => $automation_results->getKeyPath('__return.data', []),
			'_' => [
				'type' => 'automation.invoke',
				'format' => $query_format,
			]
 		];
	}
};