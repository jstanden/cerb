<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class VarUnsetAction extends AbstractAction {
	const ID = 'var.unset';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Params validation
			
			$validation->addField('inputs', 'inputs:')
				->array()
				->setRequired(true)
			;
			
			$validation->addField('output', 'output:')
				->string()
			;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate input
			
			$validation->reset();
			
			$validation->addField('key', 'inputs:key:')
				->stringOrArray()
				->setMaxLength(2048)
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			if(is_array($inputs['key'])) {
				$result = [];
				foreach($inputs['key'] as $key)
					$result[$key] = $dict->unsetKeyPath($key, ':');
				
			} else {
				$result = $dict->unsetKeyPath($inputs['key'], ':');
			}
			
			if($output) {
				$dict->set($output, $result);
			}
		
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if(null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}