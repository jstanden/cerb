<?php
class AutomationTrigger_RecordChanged extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.record.changed';
	
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
				'key' => 'change_type',
				'notes' => 'The type of change: `created`, `updated`, `deleted`',
			],
			[
				'key' => 'record_',
				'notes' => 'The changed record dictionary. Supports key expansion.',
			],
			[
				'key' => 'was_record_',
				'notes' => 'The record dictionary before the changes. Supports key expansion.',
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