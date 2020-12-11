<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;

class WhileNode extends AbstractNode {
	public function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, array $environment, string &$error = null) {
		$params = $this->node->getParams($dict);
		
		$if = @$params['if'];
		
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
			return $this->node->getParent()->getId();
			
		} else {
			// Need to initialize?
			if(
				!array_key_exists('stack', $node_memory)
				|| !$node_memory['stack']
			) {
				$node_memory['stack'] = array_map(function($child) { return $child->getId(); }, $this->node->getChildren());
			}
			
			// If there are no children, return to parent
			if(!$node_memory['stack'])
				return $this->node->getParent()->getId();
			
			// Next child on the stack
			return array_shift($node_memory['stack']);
		}
	}
}