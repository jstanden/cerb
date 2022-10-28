<?php
class _DevblocksDataProviderBotBehavior extends _DevblocksDataProvider {
	function getSuggestions($type, array $params=[]) {
		$behavior_uri = substr($type, 9);
		
		if(false == ($behavior = Event_DataQueryDatasource::getByAlias($behavior_uri)))
			return [];
		
		$schema = [
			'' => [],
		];
		
		foreach($behavior->variables as $var) {
			if($var['is_private'])
				continue;
			
			$var_key = substr($var['key'], 4) . ':';
			
			$schema[''][] = [
				'value' => $var_key,
			];
			
			$schema[$var_key][] = [
				'caption' => '(' . $var['label'] . ')',
				'snippet' => '"${1:' . $var['type'] . '}"',
			];
		}
		
		$schema[''][] = 'format:';
		$schema['format:'] = [];
		
		return $schema;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$behavior_alias = $options['behavior_alias'] ?? null;
		
		if(false == ($data_behavior = Event_DataQueryDatasource::getByAlias($behavior_alias))) {
			$error = "A bot behavior isn't configured.";
			return false;
		}
		
		$behavior_vars = [];
		$query_format = null;
		
		foreach($chart_fields as $chart_field) {
			if($chart_field->key == 'type') {
				continue;
				
			} else if ('format' == $chart_field->key) {
				CerbQuickSearchLexer::getOperStringFromTokens($chart_field->tokens, $oper, $value);
				$query_format = $value;
				
			} else {
				$var_key = 'var_' . $chart_field->key;
				
				if(array_key_exists($var_key, $data_behavior->variables)) {
					CerbQuickSearchLexer::getOperStringFromTokens($chart_field->tokens, $oper, $value);
					$behavior_vars[$var_key] = $value;
				}
			}
		}
		
		// Event model
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DataQueryDatasource::ID,
			[
				'_variables' => $behavior_vars,
				'query_format' => $query_format,
				'actions' => &$actions,
			]
		);
		
		if(false == ($event = $data_behavior->getEvent()))
			return;
			
		$event->setEvent($event_model, $data_behavior);
		
		$values = $event->getValues();
		
		$dict = DevblocksDictionaryDelegate::instance($values);
		
		// Format behavior vars
		
		if(is_array($behavior_vars))
		foreach($behavior_vars as $k => &$v) {
			if(DevblocksPlatform::strStartsWith($k, 'var_')) {
				if(!isset($data_behavior->variables[$k]))
					continue;
				
				$value = $data_behavior->formatVariable($data_behavior->variables[$k], $v, $dict);
				$dict->set($k, $value);
			}
		}
		
		// Run tree
		
		$data_behavior->runDecisionTree($dict, false, $event);
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'return_data':
					$data = @json_decode($action['data'], true);
					
					return ['data' => $data, '_' => [
						'type' => 'behavior.' . $behavior_alias,
						'format' => $query_format,
					]];
					break;
			}
		}
		
		return [];
	}
};