<?php
namespace Cerb\AutomationBuilder\Action;

class ReturnAction extends AbstractAction {
	const ID = 'return';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$return_values = $this->node->getParams($dict);
		
		$dict->set('__exit', 'return');
		$dict->set('__return', $return_values);
		
		return $this->node->getParent()->getId();
	}
}