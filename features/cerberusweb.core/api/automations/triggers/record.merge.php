<?php
class AutomationTrigger_RecordMerge extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.record.merge';
	
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
				'notes' => 'The [record type](https://cerb.ai/docs/records/types/) being merged.',
			],
			[
				'key' => 'records',
				'notes' => 'An array of [record](https://cerb.ai/docs/records/types/) dictionaries to merge. Supports key expansion.',
			],
			[
				'key' => 'source_ids',
				'notes' => 'The IDs of the records being merged from.',
			],
			[
				'key' => 'target_id',
				'notes' => 'The ID of the record being merged to.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The current [worker](https://cerb.ai/docs/records/types/worker/) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'deny',
					'notes' => 'If set, the merge request will be denied with the given message. If omitted the request is approved.',
				],
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		return [];
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}