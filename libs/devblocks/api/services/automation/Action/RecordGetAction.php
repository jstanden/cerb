<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;
use Model_Automation;

class RecordGetAction extends AbstractAction {
	const ID = 'record.get';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;

		try {
			// Validate params
			
			$validation->addField('inputs', 'inputs:')
				->array()
			;
			
			$validation->addField('output', 'output:')
				->string()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate input
			
			$validation->reset();
			
			$validation->addField('record_type', 'inputs:record_type:')
				->context()
				->setRequired(true)
			;
			
			$validation->addField('record_id', 'inputs:record_id:')
				->id()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if(!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow this command (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$record_type = $inputs['record_type'] ?? null;
			$record_id = $inputs['record_id'] ?? null;
			
			if ($record_type && $record_id) {
				if (false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
					throw new Exception_DevblocksAutomationError(sprintf(
						"Unknown record type `%s`",
						$record_type
					));
				
				if (false == (@$model = $context_ext->getDaoClass()::get($record_id))) {
					throw new Exception_DevblocksAutomationError(sprintf(
						"Record %s:%d not found",
						$record_type,
						$record_id
					));
				}
				
				$record_dict = DevblocksDictionaryDelegate::getDictionariesFromModels([$record_id => $model], $context_ext->id);
				
				$dict->set($output, $record_dict[$record_id]);
			}
			
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