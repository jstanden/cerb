<?php
namespace Cerb\AutomationBuilder\Action;

use CerbAutomationPolicy;
use DAO_AutomationDatastore;
use DevblocksDictionaryDelegate;
use Exception_DevblocksAutomationError;

class StorageDeleteAction extends AbstractAction {
	const ID = 'storage.delete';
	
	function activate(DevblocksDictionaryDelegate $dict, array &$node_memory, CerbAutomationPolicy $policy, string &$error=null) {
		$validation = \DevblocksPlatform::services()->validation();
		
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
			
			if (!$policy->isAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not permit this action (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$result = DAO_AutomationDatastore::delete([$inputs['key']]);
			
			if($output)
				$dict->set($output, $result);
			
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
		}
		
		if(null != ($event_success = $this->node->getChildBySuffix(':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}