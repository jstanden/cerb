<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class SetAction extends AbstractAction {
	const ID = 'set';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$params = $this->node->getParams($dict);
		
		foreach($params as $k => $v) {
			$dict->set($k, $v);
		}
		
		return $this->node->getParent()->getId();
	}
}