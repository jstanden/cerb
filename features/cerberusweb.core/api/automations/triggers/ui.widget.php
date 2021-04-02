<?php
class AutomationTrigger_UiWidget extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.widget';
	
	function renderConfig(Model_Automation $model) {
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() {
		return [
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
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}