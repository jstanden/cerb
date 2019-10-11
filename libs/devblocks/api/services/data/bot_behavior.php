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
			
			// [TODO] Types
			$schema[$var_key][] = [
				'caption' => '(' . $var['label'] . ')',
				'snippet' => '"${1:' . $var['type'] . '}"',
			];
		}
		
		return $schema;
	}
	
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$tpl = DevblocksPlatform::services()->template();
		
		@$behavior_alias = $options['behavior_alias'];
		
		if(false == ($data_behavior = Event_DataQueryDatasource::getByAlias($behavior_alias)))
			throw new Exception_DevblocksValidationError("A bot behavior isn't configured.");
		
		$behavior_vars = [];
		
		foreach($chart_fields as $chart_field) {
			if($chart_field->key == 'type')
				continue;
			
			$var_key = 'var_' . $chart_field->key;
			
			if(array_key_exists($var_key, $data_behavior->variables)) {
				CerbQuickSearchLexer::getOperStringFromTokens($chart_field->tokens, $oper, $value);
				$behavior_vars[$var_key] = $value;
			}
		}
		
		// Event model
		
		$actions = [];
		
		$event_model = new Model_DevblocksEvent(
			Event_DataQueryDatasource::ID,
			[
				'_variables' => $behavior_vars,
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
				
				$value = $data_behavior->formatVariable($data_behavior->variables[$k], $v);
				$dict->set($k, $value);
			}
		}
		
		// Run tree
		
		$result = $data_behavior->runDecisionTree($dict, false, $event);
		
		foreach($actions as $action) {
			switch($action['_action']) {
				case 'return_data':
					$data = @json_decode($action['data'], true);
					
					return ['data' => $data, '_' => [
						// [TOOD] Variables?
						'type' => 'behavior.' . $behavior_alias,
					]];
					break;
			}
		}
		
		return [];
	}
};