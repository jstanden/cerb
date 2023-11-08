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
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		// Project board
		if(($linked_project_boards = DAO_ProjectBoard::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_ProjectBoard::CARDS_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_project_boards = array_filter($linked_project_boards, function($w) use ($automation_name) {
				$content = implode(' ', [$w->cards_kata]);
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_project_boards)
				$results['project_board'] = array_column($linked_project_boards, 'id');
		}
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [];
	}
}