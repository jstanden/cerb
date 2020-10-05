<?php
class AutomationTrigger_WidgetMapRenderPoint extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.widgetMap.renderPoint';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'point',
				'notes' => "The GeoJSON point.\n~~~\n{\n  'type': 'Point',\n  'coordinates': [-123.456, 1.234],\n  'properties': {\n    'key': 'value'\n  }\n}\n~~~\n",
			],
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
					'key' => 'point',
					'notes' => "The GeoJSON point.\n~~~\n{\n  'type': 'Point',\n  'coordinates': [-123.456, 1.234],\n  'properties': {\n    'key': 'value'\n  }\n}\n~~~\n",
				],
			]
		];
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}