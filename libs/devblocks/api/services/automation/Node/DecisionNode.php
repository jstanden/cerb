<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;

class DecisionNode extends AbstractNode {
	public function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, array $environment, string &$error = null) {
		if (!array_key_exists('stack', $node_memory)) {
			$node_memory['stack'] = [];
			
			foreach ($this->node->getChildren() as $outcome) {
				$outcome_params = $outcome->getParams($dict);
				
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