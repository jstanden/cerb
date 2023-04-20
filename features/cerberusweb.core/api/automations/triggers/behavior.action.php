<?php
class AutomationTrigger_BehaviorAction extends Extension_AutomationTrigger {
	const ID = 'cerb.trigger.behavior.action';
	
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
		
		// Workflows
		if(($linked_nodes = DAO_DecisionNode::getWhere(sprintf("%s = %s AND %s LIKE %s",
			Cerb_ORMHelper::escape(DAO_DecisionNode::NODE_TYPE),
			Cerb_ORMHelper::qstr('action'),
			Cerb_ORMHelper::escape(DAO_DecisionNode::PARAMS_JSON),
			Cerb_ORMHelper::qstr('%core.bot.action.automation%' . $automation_name . '%')
		)))) {
			$linked_nodes = array_filter($linked_nodes, function($w) use ($automation_name) {
				$content = '';
				array_walk_recursive($w->params, function($v,$k) use (&$content) {
					if($k == 'automations_kata')
						$content .= ' ' . $v;
				});
				$tokens = DevblocksPlatform::services()->string()->tokenize($content, false);
				
				return in_array($automation_name, $tokens);
			});
		
			if($linked_nodes)
				$results['behavior'] = array_unique(array_column($linked_nodes, 'trigger_id'));
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