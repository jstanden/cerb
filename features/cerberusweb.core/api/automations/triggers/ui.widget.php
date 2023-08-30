<?php
class AutomationTrigger_UiWidget extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.widget';
	
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
				'key' => 'record_*',
				'notes' => 'The current record dictionary when a card or profile widget (empty on workspace widgets).',
			],
			[
				'key' => 'widget_*',
				'notes' => 'The card, profile, or workspace widget record.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The current worker record.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'html',
					'notes' => 'The HTML to render for the widget',
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