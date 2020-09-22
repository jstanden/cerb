<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;

class OutcomeNode extends AbstractNode {
	public function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, array $environment, string &$error = null) {
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