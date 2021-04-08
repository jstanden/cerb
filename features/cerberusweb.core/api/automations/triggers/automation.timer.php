<?php
class AutomationTrigger_AutomationTimer extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.automation.timer';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'timer_*',
				'notes' => 'The [automation timer](https://cerb.ai/docs/records/types/automation_timer/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'await' => [
				[
					'key' => 'datetime',
					'notes' => 'When to resume the timer, as a Unix timestamp',
				]
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