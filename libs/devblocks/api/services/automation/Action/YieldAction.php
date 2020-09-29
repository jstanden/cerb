<?php
namespace Cerb\AutomationBuilder\Action;

class YieldAction extends AbstractAction {
	const ID = 'yield';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$yield_values = $this->node->getParams($dict);
		
		$dict->set('__exit', 'yield');
		$dict->set('__return', $yield_values);
		
		return $this->node->getParent()->getId();
	}
}