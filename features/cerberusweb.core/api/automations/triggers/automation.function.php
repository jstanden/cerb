<?php
class AutomationTrigger_AutomationFunction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.automation.function';
	
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
		return [];
	}
	
	function getOutputsMeta() {
		return [];
	}
	
	function getUsageMeta(string $automation_name): array {
		$results = [];
		
		// Automations
		if(($linked_automations = DAO_Automation::getWhere(sprintf("%s LIKE %s",
			Cerb_ORMHelper::escape(DAO_Automation::SCRIPT),
			Cerb_ORMHelper::qstr('%' . $automation_name . '%')
		)))) {
			$linked_automations = array_filter($linked_automations, function($w) use ($automation_name) {
				$tokens = DevblocksPlatform::services()->string()->tokenize($w->script, false);
				return in_array($automation_name, $tokens);
			});
			
			if($linked_automations)
				$results['automation'] = array_column($linked_automations, 'id');
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