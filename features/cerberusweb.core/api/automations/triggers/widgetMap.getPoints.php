<?php
class AutomationTrigger_WidgetMapGetPoints extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.widgetMap.getPoints';
	
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
				'key' => 'widget_*',
				'notes' => 'The widget record. Supports key expansion.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active worker record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'points',
					'notes' => 'The points in GeoJSON format.',
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