<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use Model_Automation;

class OutcomeNode extends AbstractNode {
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$has_activated = array_key_exists('stack', $node_memory);
		
		// If this outcome is not in a decision, check if logic
		if('decision' != $this->node->getParent()->getNameType()) {
			$outcome_params = $automation->getParams($this->node, $dict);
			
			// If the outcome hasn't activated yet, evaluate the if condition once
			if(!$has_activated && array_key_exists('if', $outcome_params) && !$outcome_params['if']) {
				return $this->node->getParent()->getId();
			}
		}
		
		if(!$has_activated) {
			$node_memory['stack'] = array_map(function($child) { return $child->getId(); }, $this->node->getChildren());
		}
		
		if(!empty($node_memory['stack'])) {
			return array_shift($node_memory['stack']);
		} else {
			return $this->node->getParent()->getId();
		}
	}
}