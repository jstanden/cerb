<?php
class AutomationTrigger_ResourceGet extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.resource.get';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'resource_*',
				'notes' => 'The resource record. Supports key expansion.',
			],
			[
				'key' => 'actor_*',
				'notes' => 'The current actor record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'file:content',
					'type' => 'string',
					'notes' => 'The file contents in text (base64-encode if binary)',
				],
				[
					'key' => 'file:expires_at',
					'type' => 'timestamp',
					'notes' => 'Cache this response until this UNIX timestamp',
				],
			]
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}