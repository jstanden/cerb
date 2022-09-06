<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_AutomationDatastore;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class StorageGetAction extends AbstractAction {
	const ID = 'storage.get';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Validate params
			
			$validation->addField('inputs', 'inputs:')
				->array()
				->setRequired(true);
			
			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
				;
			
			if (false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate inputs
			
			$validation->reset();
			
			$validation->addField('key', 'inputs:key:')
				->string()
				->setMaxLength(2048)
				->setRequired(true)
			;
			
			$validation->addField('default', 'inputs:default:')
				->stringOrArray()
				->setMaxLength(16_777_216)
			;
			
			if (false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if (false === ($results = DAO_AutomationDatastore::get($inputs['key']))) {
				if(array_key_exists('default', $inputs)) {
					$results = $inputs['default'];
				} else {
					$error = sprintf("Failed to load the key (`%s`)", $inputs['key']);
					throw new Exception_DevblocksAutomationError($error);
				}
			}
			
			if ($output)
				$dict->set($output, $results);
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if (null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if (null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}