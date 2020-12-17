<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use Model_Automation;

class RootNode extends AbstractNode {
	public function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error = null) {
		if (!array_key_exists('stack', $node_memory)) {
			$node_memory['stack'] = [];
			
			if (null != ($event_child = $this->node->getChild('start'))) {
				return $event_child->getId();
				
			} else {
				$error = "No `start:` node was found.";
				return false;
			}
			
		} else {
			$node_memory = null;
		}
		
		$dict->set('__exit', 'exit');
		return true;
	}
}