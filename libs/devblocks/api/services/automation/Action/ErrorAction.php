<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class ErrorAction extends AbstractAction {
	const ID = 'error';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$return_values = $automation->getParams($this->node, $dict);
		
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