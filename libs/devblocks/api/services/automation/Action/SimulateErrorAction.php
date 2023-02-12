<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class SimulateErrorAction extends AbstractAction {
	const ID = 'simulate.error';
	
	/**
	 * @param Model_Automation $automation
	 * @param DevblocksDictionaryDelegate $dict
	 * @param array $node_memory
	 * @param string|null $error
	 * @return string|false
	 */
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$return_values = $automation->getParams($this->node, $dict);
		
		$simulate_node = $this->node->getAncestorByType('on_simulate');
		
		// This command can only run inside of an `on_simulate:` branch
		if(!$simulate_node) {
			$error = "The `simulate.error:` command must occur within `on_simulate:`";
			return false;
		}
		
		$command_node = $simulate_node->getParent();
		
		if(!$command_node)
			return false;
		
		$output = $command_node->getParam('output', null, $dict);
		
		if($output && is_string($output)) {
			$dict->set($output, $return_values);
		}
		
		if($event_error = $command_node->getChild($command_node->getId() . ':on_error')) {
			return $event_error->getId();
		}
		
		return $command_node->getId();
	}
}