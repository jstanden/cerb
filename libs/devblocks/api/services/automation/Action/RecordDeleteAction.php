<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;

class RecordDeleteAction extends AbstractAction {
	const ID = 'record.delete';
	
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
			
			$record_type = $inputs['record_type'];
			$record_id = $inputs['record_id'];
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true))) {
				throw new Exception_DevblocksAutomationError(sprintf(
					"Unknown record type `%s`",
					$record_type
				));
			}
			
			// Make sure we can create records of this type
			if(!$context_ext->manifest->hasOption('records'))
				throw new Exception_DevblocksAutomationError("Not implemented.");
			
			$dao_class = $context_ext->getDaoClass();
			
			if(!method_exists($dao_class, 'get'))
				throw new Exception_DevblocksAutomationError("Get not implemented for this record type.");
			
			if(!method_exists($dao_class, 'delete'))
				throw new Exception_DevblocksAutomationError("Delete not implemented for this record type.");
			
			if(false == ($model = $dao_class::get($record_id)))
				throw new Exception_DevblocksAutomationError(sprintf("Record #%d not found", $record_id));
			
			$record_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels([$model->id => $model], $context_ext->id);
			
			$dao_class::delete($record_id);
			
			if(false != (@$record_dict = $record_dicts[$record_id])) {
				CerberusContexts::logActivityRecordDelete($context_ext, $record_id, $record_dict->_label);
			}
			
			if ($output)
				$dict->set($output, $record_dict);
			
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