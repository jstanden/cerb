<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DAO_CustomField;
use DAO_CustomFieldValue;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;
use Model_Automation;

class RecordCreateAction extends AbstractAction {
	const ID = 'record.create';
	
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
				;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Validate input
			
			$validation->reset();
			
			$validation->addField('record_type', 'inputs:record_type:')
				->context()
				->setRequired(true)
				;
			
			$validation->addField('fields', 'inputs:fields:')
				->array()
				->setRequired(true)
				;
			
			$validation->addField('expand', 'inputs:expand:')
				->stringOrArray()
				->setMaxLength(2048)
				;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
				
			$record_type = $inputs['record_type'];
			$fields = $inputs['fields'] ?? [];
			$expand = $inputs['expand'] ?? [];
			
			if(is_string($expand))
				$expand = [$expand];
			
			if(!is_array($expand)) {
				$error = '`expand:` must be a list of keys.';
				return false;
			}
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
				throw new Exception_DevblocksAutomationError(sprintf(
					"Unknown record type `%s`",
					$record_type
				));
			
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
			
			// Automation?
			$actor = [CerberusContexts::CONTEXT_APPLICATION, 0];
			
			if(empty($context_ext->id))
				throw new Exception_DevblocksAutomationError("Invalid record type.");
			
			// Make sure we can create records of this type
			if(!$context_ext->manifest->hasOption('records'))
				throw new Exception_DevblocksAutomationError("The given record type is not supported.");
			
			$dao_class = $context_ext->getDaoClass();
			$dao_fields = $custom_fields = [];
			
			// Fail if there's no DAO::create() method
			if(!method_exists($dao_class, 'create'))
				throw new Exception_DevblocksAutomationError("The given record type is not supported.");
			
			$error = null;
			
			if(!$context_ext->getDaoFieldsFromKeysAndValues($fields, $dao_fields, $custom_fields, $error))
				throw new Exception_DevblocksAutomationError($error);
			
			if(is_array($dao_fields))
				if(!$dao_class::validate($dao_fields, $error))
					throw new Exception_DevblocksAutomationError($error);
			
			if($custom_fields)
				if(!DAO_CustomField::validateCustomFields($custom_fields, $context_ext->id, $error))
					throw new Exception_DevblocksAutomationError($error);
			
			// Check implementation permissions
			if(!$dao_class::onBeforeUpdateByActor($actor, $dao_fields, null, $error))
				throw new Exception_DevblocksAutomationError($error);
			
			if(false == ($id = $dao_class::create($dao_fields)))
				throw new Exception_DevblocksAutomationError("Failed to create the record.");
			
			$dao_class::onUpdateByActor($actor, $dao_fields, $id);
			
			if($custom_fields)
				DAO_CustomFieldValue::formatAndSetFieldValues($context_ext->id, $id, $custom_fields);
			
			$record_dict = DevblocksDictionaryDelegate::instance([
				'_context' => $context_ext->id,
				'id' => $id,
			]);
			
			if($expand) {
				foreach($expand as $expand_key)
				$record_dict->get($expand_key);
			}
			
			$dict->set($output, $record_dict);
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				$dict->set($output, [
					'error' => $error,
				]);
				
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