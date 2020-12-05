<?php
namespace Cerb\AutomationBuilder\Action;

use CerbAutomationPolicy;
use DAO_AutomationDatastore;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;

class StorageSetAction extends AbstractAction {
	const ID = 'storage.set';
	
	function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, CerbAutomationPolicy $policy, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Validate params
			
			$validation->addField('inputs', 'inputs:')
				->array()
				->setRequired(true);
			
			$validation->addField('output', 'output:')
				->string();
			
			if (false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate inputs
			
			$validation->reset();
			
			$validation->addField('key', 'inputs:key:')
				->string()
				->setRequired(true);
			
			$validation->addField('value', 'inputs:value:')
				->stringOrArray()
				->setRequired(true);
			
			$validation->addField('expires', 'inputs:expires:')
				->string()
				->addValidator($validation->validators()->date());
			
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
			
			$expires_at = 0;
			
			if(array_key_exists('expires', $inputs))
				$expires_at = strtotime($inputs['expires']);
				
			DAO_AutomationDatastore::set($inputs['key'], $inputs['value'], $expires_at);
			
			if($output)
				$dict->set($output, [
					'key' => $inputs['key'],
					'expires' => $expires_at,
				]);
			
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
		
		if(null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}