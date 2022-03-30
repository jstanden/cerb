<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use Model_Automation;

class OutcomeNode extends AbstractNode {
	public function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error = null) {
		// If this outcome is outside of a decision, check the if logic
		if('decision' != $this->node->getParent()->getNameType()) {
			$outcome_params = $automation->getParams($this->node, $dict);
			
			if(array_key_exists('if', $outcome_params) && !$outcome_params['if']) {
				return $this->node->getParent()->getId();
			}
		}
		
		if(!array_key_exists('stack', $node_memory)) {
			$node_memory['stack'] = array_map(function($child) { return $child->getId(); }, $this->node->getChildren());
		}
		
		if(!empty($node_memory['stack'])) {
			return array_shift($node_memory['stack']);
		} else {
			return $this->node->getParent()->getId();
		}
	}
}