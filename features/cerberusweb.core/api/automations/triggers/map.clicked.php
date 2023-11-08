<?php
class AutomationTrigger_MapClicked extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.map.clicked';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function getInputsMeta() {
		return [
			[
				'key' => 'feature_type',
				'notes' => '`region` or `point`',
			],
			[
				'key' => 'feature_properties',
				'notes' => 'The key/value properties of the selected feature.',
			],
			[
				'key' => 'widget_*',
				'notes' => 'The widget [record](https://cerb.ai/docs/records/types/). Supports key expansion.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'sheet',
					'notes' => "The sheet to display for the clicked feature based on the properties dictionary.",
				],
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		return [];
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