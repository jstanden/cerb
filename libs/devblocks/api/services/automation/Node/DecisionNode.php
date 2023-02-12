<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use Model_Automation;

class DecisionNode extends AbstractNode {
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		if (!array_key_exists('stack', $node_memory)) {
			$node_memory['stack'] = [];
			
			foreach ($this->node->getChildren() as $outcome) {
				$outcome_params = $automation->getParams($outcome, $dict);
				
				if(!array_key_exists('if', $outcome_params)) {
					return $outcome->getId();
					
				} else {
					if($outcome_params['if'])
						return $outcome->getId();
				}
			}
		}
		
		return $this->node->getParent()->getId();
	}
}