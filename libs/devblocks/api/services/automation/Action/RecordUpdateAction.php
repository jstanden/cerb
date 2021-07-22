<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_CustomField;
use DAO_CustomFieldValue;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;
use Model_Automation;

class RecordUpdateAction extends AbstractAction {
	const ID = 'record.update';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
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
			
			
			$validation->addField('expand', 'inputs:expand:')
				->string()
				->setMaxLength(2048)
			;
			
			$validation->addField('fields', 'inputs:fields:')
				->array()
				->setRequired(true)
			;
			
			$validation->addField('record_id', 'inputs:record_id:')
				->id()
				->setRequired(true)
			;
			
			$validation->addField('record_type', 'inputs:record_type:')
				->context()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Policy
			
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
			$expand = $inputs['expand'] ?? null;
			@$fields = $inputs['fields'] ?: [];
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true))) {
				throw new Exception_DevblocksAutomationError(sprintf(
					"Unknown record type `%s`",
					$record_type
				));
			}
			
			// Make sure we can create records of this type
			if(!$context_ext->manifest->hasOption('records'))
				throw new Exception_DevblocksAutomationError("Not implemented.");
			
			$models = $context_ext->getModelObjects([$record_id]);
			
			if(!isset($models[$record_id]))
				throw new Exception_DevblocksAutomationError(sprintf("Record #%d not found", $record_id));
			
			$dao_class = $context_ext->getDaoClass();
			$dao_fields = $custom_fields = [];
			
			if(!method_exists($dao_class, 'update'))
				throw new Exception_DevblocksAutomationError("Not implemented.");
			
			if(!method_exists($context_ext, 'getDaoFieldsFromKeysAndValues'))
				throw new Exception_DevblocksAutomationError("Not implemented.");
			
			if(!$context_ext->getDaoFieldsFromKeysAndValues($fields, $dao_fields, $custom_fields, $policy))
				throw new Exception_DevblocksAutomationError($policy);
			
			if(is_array($dao_fields) && !$dao_class::validate($dao_fields, $policy, $record_id))
				throw new Exception_DevblocksAutomationError($policy);
			
			if($custom_fields && !DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $policy, $record_id))
				throw new Exception_DevblocksAutomationError($policy);
			
			$dao_class::update($record_id, $dao_fields);
			
			if($custom_fields)
				DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $record_id, $custom_fields);
			
			$record_dict = DevblocksDictionaryDelegate::instance([
				'_context' => $context_ext->id,
				'id' => $record_id,
			]);
			
			// [TODO] Expand (also in record_create)
			// [TODO] Handle multiples
			if($expand)
				$record_dict->get($expand);
			
			if ($output)
				$dict->set($output, $record_dict);
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}