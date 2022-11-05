<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Model_Automation;

class WhileNode extends AbstractNode {
	public function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error = null) {
		$params = $automation->getParams($this->node, $dict);
		
		$if = $params['if'] ?? null;
		
		if(is_null($if)) {
			$error = '`if:` is required.';
			return false;
		}
		
		// If not a bool, parse the string as a bool
		if(is_string($if)) {
			$if = DevblocksPlatform::services()->string()->toBool($if);
		}
		
		if(!is_bool($if)) {
			$error = '`if:` must be a boolean `true` or `false`.';
			return false;
		}
		
		if(!$if) {
			unset($node_memory['stack']);
			$dict->scrubKeyPathPrefix('__state|memory', $this->node->getId() . ':', '|');
			return $this->node->getParent()->getId();
			
		} else {
			// Need to initialize?
			if(
				!array_key_exists('stack', $node_memory)
				|| !$node_memory['stack']
			) {
				$node_memory['stack'] = array_map(function($child) { return $child->getId(); }, $this->node->getChildren());
			}
			
			$dict->scrubKeyPathPrefix('__state|memory', $this->node->getId() . ':', '|');
			
			// Next child on the stack
			if(!empty($node_memory['stack'])) {
				return array_shift($node_memory['stack']);
			// If there are no children left, return to parent
			} else {
				return $this->node->getParent()->getId();
			}
		}
	}
}