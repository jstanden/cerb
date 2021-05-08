<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class VarExpandAction extends AbstractAction {
	const ID = 'var.expand';
	
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
				->string()
			;
			
			$validation->addField('paths', 'inputs:paths:')
				->stringOrArray()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			if(!array_key_exists('key', $inputs)) {
				$targets = $dict;
			} else if(false !== strpos($inputs['key'], ':')) {
				$targets = $dict->getKeyPath($inputs['key'], null, ':');
			} else {
				$targets = $dict->get($inputs['key'], null);
			}
			
			if($targets) {
				if($targets instanceof DevblocksDictionaryDelegate)
					$targets = [$targets];
				
				$paths = $inputs['paths'];
				
				if(is_string($paths))
					$paths = DevblocksPlatform::parseCsvString($paths);
				
				if(is_array($targets) && is_array($paths)) {
					foreach ($paths as $path)
						DevblocksDictionaryDelegate::bulkLazyLoad($targets, $path, true);
				}
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