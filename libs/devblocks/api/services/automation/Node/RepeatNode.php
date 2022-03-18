<?php
namespace Cerb\AutomationBuilder\Node;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Model_Automation;

class RepeatNode extends AbstractNode {
	public function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error = null) {
		$params = $this->node->getParams($dict);
		
		$each = $params['each'] ?? null;
		$as = $params['as'] ?? null;
		
		if(is_null($as)) {
			$error = '`as:` is required.';
			return false;
		}
		
		if(!is_string($as)) {
			$error = '`as:` must be a string.';
			return false;
		}
		
		$as = DevblocksPlatform::parseCsvString($as, true);
		$as_key = $as_value = null;
		
		if(1 == count($as)) {
			$as_key = $as[0] . '__index';
			$as_value = $as[0];
			
		} else if(2 == count($as)) {
			$as_key = $as[0] ?: null;
			$as_value = $as[1] ?: null;
		}
		
		if(!$as_value) {
			return $this->node->getParent()->getId();
			
		} else {
			// Need to initialize?
			if(!array_key_exists('each', $node_memory)) {
				if(!is_array($each))
					$each = [];
				
				if(0 === count($each))
					return $this->node->getParent()->getId();
				
				$node_memory['each_keys'] = array_keys($each);
				$node_memory['each'] = $each;
				$node_memory['stack'] = [];
			}
			
			if(0 !== count($node_memory['stack'])) {
				return array_shift($node_memory['stack']);
				
			} else {
				if(0 !== count($node_memory['each'])) {
					if(!is_null($as_key))
						$dict->set($as_key, current($node_memory['each_keys']));
					
					$dict->set($as_value, current($node_memory['each']));
					
					array_shift($node_memory['each_keys']);
					array_shift($node_memory['each']);
					
					$node_memory['stack'] = array_map(function($child) { return $child->getId(); }, $this->node->getChildren());
					
					// Clear branch memory
					$dict->scrubKeyPathPrefix('__state|memory', $this->node->getId() . ':', '|');
					
					// Next state is the first node of the stack
					return array_shift($node_memory['stack']);
					
				} else {
					if($as_key)
						$dict->unset($as_key);
					
					$dict->unset($as_value);
					
					return $this->node->getParent()->getId();
				}
			}
		}
	}
}