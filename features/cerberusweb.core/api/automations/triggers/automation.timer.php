<?php
class AutomationTrigger_AutomationTimer extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.automation.timer';
	
	function renderConfig(Model_Automation $model) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('inputs', $this->getInputsMeta());
		$tpl->assign('outputs', $this->getOutputsMeta());
		$tpl->display('devblocks:cerberusweb.core::automations/triggers/config_inputs_outputs.tpl');
	}
	
	function validateConfig(array &$params, &$error=null) {
		return true;
	}
	
	function getInputsMeta() : array {
		return [
			[
				'key' => 'timer_*',
				'notes' => 'The [automation timer](https://cerb.ai/docs/records/types/automation_timer/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}
	
	function getOutputsMeta() : array {
		return [
			'await' => [
				[
					'key' => 'until',
					'notes' => 'When to resume the timer, as a Unix timestamp',
				]
			],
			'return' => [
				[
					'key' => 'delete',
					'notes' => '`true` to delete the timer when complete',
				]
			]
		];
	}
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		if(($linked_timers = DAO_AutomationTimer::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_AutomationTimer::AUTOMATIONS_KATA),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_timers = array_filter($linked_timers, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->automations_kata, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_timers)
				$results['automation_timer'] = array_column($linked_timers, 'id');
		}
		
		return $results;
	}
	
	public function getEditorToolbarItems(array $toolbar): array {
		return $toolbar;
	}
	
	public function getAutocompleteSuggestions() : array {
		return [
			'*' => [
				'(.*):await:' => [
					'until@date: +5 mins',
				],
				'(.*):return:' => [
					'delete@bool: yes',
				],
			]
		];
	}
}