<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class AwaitAction extends AbstractAction {
	const ID = 'await';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$await_values = $automation->getParams($this->node, $dict);
		
		$dict->set('__exit', 'await');
		$dict->set('__return', $await_values);
		
		return $this->node->getParent()->getId();
	}
}