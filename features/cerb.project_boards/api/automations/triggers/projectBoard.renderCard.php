<?php
class AutomationTrigger_ProjectBoardRenderCard extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.projectBoard.renderCard';
	
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
				'key' => 'board_*',
				'notes' => 'The [project board](https://cerb.ai/docs/records/types/project_board/#dictionary-placeholders) record. Supports key expansion.',
			],
			[
				'key' => 'card_*',
				'notes' => 'The card [record](https://cerb.ai/docs/records/types/). Supports key expansion. `card__type` is the record type alias (e.g. `ticket`).',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The current [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() {
		return [
			'return' => [
				[
					'key' => 'sheet',
					'notes' => 'The sheet for displaying the card.',
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