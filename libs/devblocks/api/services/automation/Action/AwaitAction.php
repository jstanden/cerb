<?php
namespace Cerb\AutomationBuilder\Action;

class AwaitAction extends AbstractAction {
	const ID = 'await';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$await_values = $this->node->getParams($dict);
		
		$dict->set('__exit', 'await');
		$dict->set('__return', $await_values);
		
		return $this->node->getParent()->getId();
	}
}