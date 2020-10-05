<?php
class AutomationTrigger_ProjectBoardCardAction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.projectBoard.cardAction';
	
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
				'notes' => 'The project board record. Supports key expansion.',
			],
			[
				'key' => 'card_*',
				'notes' => 'The card record. Supports key expansion.',
			],
			[
				'key' => 'column_*',
				'notes' => 'The project board column record. Supports key expansion.',
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