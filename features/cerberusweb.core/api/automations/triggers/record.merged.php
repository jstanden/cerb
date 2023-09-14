<?php
class AutomationTrigger_RecordMerged extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.record.merged';
	
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
				'key' => 'record_type',
				'notes' => 'The merged [record type](https://cerb.ai/docs/records/types/).',
			],
			[
				'key' => 'records',
				'notes' => 'An array of [record](https://cerb.ai/docs/records/types/) dictionaries that were merged. Supports key expansion.',
			],
			[
				'key' => 'source_ids',
				'notes' => 'The IDs of the records merged from.',
			],
			[
				'key' => 'target_id',
				'notes' => 'The ID of the record merged to.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The current [worker](https://cerb.ai/docs/records/types/worker/) record. Supports key expansion.',
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