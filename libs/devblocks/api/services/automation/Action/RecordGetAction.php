<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DAO_CustomField;
use DAO_CustomFieldValue;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;

class RecordGetAction extends AbstractAction {
	const ID = 'record.get';
	
	function activate(\DevblocksDictionaryDelegate $dict, array &$node_memory, \CerbAutomationPolicy $policy, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		
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
			
			if(!$policy->isAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not permit this action (%s).",
					self::ID
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$record_type = $inputs['record_type'];
			$id = $inputs['record_id'];
			
			if ($record_type && $id) {
				if (false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
					throw new Exception_DevblocksAutomationError(sprintf(
						"Unknown record type `%s`",
						$record_type
					));
				
				if (false == (@$model = $context_ext->getDaoClass()::get($id))) {
					throw new Exception_DevblocksAutomationError(sprintf(
						"Record %s:%d not found",
						$record_type,
						$id
					));
				}
				
				$record_dict = DevblocksDictionaryDelegate::getDictionariesFromModels([$id => $model], $context_ext->id);
				
				$dict->set($output, $record_dict[$id]);
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