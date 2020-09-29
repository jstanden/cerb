<?php
namespace Cerb\AutomationBuilder\Action;

class ErrorAction extends AbstractAction {
	const ID = 'error';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$params = $this->node->getParams($dict);
		
		if(array_key_exists('', $params)) {
			$params['message'] = $params[''];
			unset($params['']);
		}
		
		$error = @$params['message'];
		
		//$dict->set('__error', $params);
		
		return false;
	}
}