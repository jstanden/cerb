<?php
class AutomationTrigger_DataQuery extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.data.query';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		
		try {
			$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
		} catch(SmartyException | Exception $e) {
			error_log($e->getMessage());
		}
	}
	
	function validateConfig(array &$params, &$error=null) : bool {
		return true;
	}
	
	function getEventPlaceholders() : array {
		$inputs = $this->getInputsMeta();
		
		$inputs[] = [
			'key' => 'query_format',
			'notes' => 'The requested format for the data query results.',
		];
		
		return $inputs;
	}

	function getInputsMeta() : array {
		return [
			[
				'key' => 'inputs',
				'notes' => 'Custom inputs from the caller.',
			],
			[
				'key' => 'query_format',
				'notes' => 'The requested format for the data query results.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'return' => [
				[
					'key' => 'data',
					'notes' => 'Array of data query results in the requested format',
					'required' => true,
				],
//				[
//					'key' => '_',
//					'notes' => 'Optional query results metadata',
//					'required' => false,
//				],
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return [];
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):return:' => [
					'data:',
				],
			],
		];
	}
}