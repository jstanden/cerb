<?php
class AutomationTrigger_UiSheetData extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.ui.sheet.data';
	
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
				'key' => 'inputs',
				'notes' => 'The input parameters.',
			],
			[
				'key' => 'sheet_filter',
				'notes' => 'The optional text to filter results by (if `schema:layout:filtering:` is enabled).',
			],
			[
				'key' => 'sheet_limit',
				'notes' => 'The number of results per page.',
			],
			[
				'key' => 'sheet_page',
				'notes' => 'The zero-based current page of the sheet (if `schema:layout:paging:` is enabled).',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'data',
					'notes' => 'An array of dictionaries.',
				],
				[
					'key' => 'total',
					'notes' => 'The total number of results in the `data:` before paging.',
				],
			],
		];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}