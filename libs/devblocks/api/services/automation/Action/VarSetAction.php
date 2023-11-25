<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class VarSetAction extends AbstractAction {
	const ID = 'var.set';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
		
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
				->string()
				->setMaxLength(2048)
				->setRequired(true)
			;
			
			$validation->addField('delimiter', 'inputs:delimiter:')
				->string()
				->setMaxLength(128)
			;
			
			$validation->addField('value', 'inputs:value:')
				->stringOrArray()
				->setMaxLength(16_777_216)
				->setRequired(true)
				->setNotEmpty(false)
			;
			
			$validation_values = json_decode(json_encode($inputs), true);
			
			if(false === ($validation->validateAll($validation_values, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$result = $dict->setKeyPath($inputs['key'], $inputs['value'], $inputs['delimiter'] ?? ':');
			
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