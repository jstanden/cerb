<?php
namespace Cerb\AutomationBuilder\Action;

use CerbAutomationPolicy;
use DevblocksDictionaryDelegate;

class SimulateSuccessAction extends AbstractAction {
	const ID = 'simulate.success';
	
	/**
	 * @param DevblocksDictionaryDelegate $dict
	 * @param array $node_memory
	 * @param CerbAutomationPolicy $policy
	 * @param string|null $error
	 * @return string|false
	 */
	function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, CerbAutomationPolicy $policy, string &$error=null) {
		$return_values = $this->node->getParams($dict);
		
		$simulate_node = $this->node->getAncestorByType('on_simulate');
		
		// This command can only run inside of an `on_simulate:` branch
		if(!$simulate_node) {
			$error = "The `simulate.success:` command must occur within `on_simulate:`";
			return false;
		}
		
		$command_node = $simulate_node->getParent();
		
		if(!$command_node)
			return false;
		
		$output = $command_node->getParam('output', null, $dict);
		
		if($output && is_string($output)) {
			$dict->set($output, $return_values);
		}
		
		if($event_success = $command_node->getChild($command_node->getId() . ':on_success')) {
			return $event_success->getId();
		}
		
		return $command_node->getId();
	}
}