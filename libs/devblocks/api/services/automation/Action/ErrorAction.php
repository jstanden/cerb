<?php
namespace Cerb\AutomationBuilder\Action;

class ErrorAction extends AbstractAction {
	const ID = 'error';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$return_values = $this->node->getParams($dict);
		
		if(array_key_exists('', $return_values)) {
			$return_values['_message'] = $return_values[''];
			unset($return_values['']);
		}
		
		$error = 'An unexpected error occurred.';
		
		if(array_key_exists('_message', $return_values)) {
			$error = $return_values['_message'];
			unset($return_values['_message']);
		}
		
		$dict->set('__exit', 'error');
		$dict->set('__return', $return_values);
		
		return false;
	}
}