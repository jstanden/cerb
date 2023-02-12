<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class ReturnAction extends AbstractAction {
	const ID = 'return';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$return_values = $automation->getParams($this->node, $dict);
		
		$dict->set('__exit', 'return');
		$dict->set('__return', $return_values);
		
		return $this->node->getParent()->getId();
	}
}