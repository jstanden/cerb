<?php
class BotAction_DataQuery extends Extension_DevblocksEventAction {
	const ID = 'core.bot.action.data_query';
	
	static function getMeta() {
		return [
			'notes' => '',
			'params' => [
				'query' => [
					'type' => 'text',
					'required' => true,
					'notes' => 'The [data query](/docs/data-queries/) to execute',
				],
				'object_placeholder' => [
					'type' => 'placeholder',
					'notes' => 'The placeholder to set with the data query results',
				],
			],
		];
	}
	
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=[], $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
		
		$tpl->display('devblocks:cerberusweb.core::internal/decisions/actions/_action_data_query.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		
		$query = $tpl_builder->build($params['query'] ?? '', $dict);
		$object_placeholder = ($params['object_placeholder'] ?? null) ?: '_results';

		if(empty($query))
			return "[ERROR] Query is required.";
		
		$out = sprintf(">>> Executing a data query:\n%s\n", $query);
		
		$this->run($token, $trigger, $params, $dict);
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$out .= sprintf("\n>>> Saving results to {{%s}}\n%s".
				"\n",
				$object_placeholder,
				DevblocksPlatform::strFormatJson(json_encode($dict->get($object_placeholder)))
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		$data = DevblocksPlatform::services()->data();
		
		$query = $tpl_builder->build($params['query'] ?? '', $dict);
		$object_placeholder = ($params['object_placeholder'] ?? null) ?: '_results';
		
		if(!$query)
			return;
		
		$error = null;
		
		// [TODO] Return errors
		if(false === ($json = $data->executeQuery($query, [], $error)))
			return;
		
		if(is_string($json) && false == ($json = json_decode($json, true)))
			return;
		
		// Set placeholder with object meta
		
		if(!empty($object_placeholder)) {
			$dict->set($object_placeholder, $json);
		}
	}
};