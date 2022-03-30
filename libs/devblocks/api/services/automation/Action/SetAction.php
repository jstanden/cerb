<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use Model_Automation;

class SetAction extends AbstractAction {
	const ID = 'set';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$params = $this->node->getParams();
		
		foreach($params as $k => $v) {
			$script_error = null;
			
			if(false !== ($this->node->formatKeyValue($k, $v, $dict, $script_error))) {
				if(!is_null($k))
					$dict->set($k, $v);
				
				if($script_error) {
					$automation->logError(
						'Scripting error: ' . $script_error,
						$this->node->getId(),
						3 // error
					);
				}
			}
		}
		
		return $this->node->getParent()->getId();
	}
}