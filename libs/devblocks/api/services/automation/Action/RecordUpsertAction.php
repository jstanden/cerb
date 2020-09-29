<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusContexts;
use DAO_CustomField;
use DAO_CustomFieldValue;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;

class RecordUpsertAction extends AbstractAction {
	const ID = 'record.upsert';
	
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
			
			$validation->addField('query', 'inputs:query:')
				->string()
				->setRequired(true)
			;
			
			$validation->addField('fields', 'inputs:fields:')
				->stringOrArray()
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
			
			@$record_type = $inputs['record_type'];
			@$query = $inputs['query'];
			@$fields = $inputs['fields'] ?? [];
			
			if(false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true))) {
				throw new Exception_DevblocksAutomationError(sprintf(
					"Unknown record type `%s`",
					$record_type
				));
			}
			
			// Make sure we can create records of this type
			if(!$context_ext->manifest->hasOption('records'))
				throw new Exception_DevblocksAutomationError("Upsert not implemented.");
			
			if(false == ($view = $context_ext->getChooserView()))
				throw new Exception_DevblocksAutomationError("Upsert not implemented.");
			
			$view->setAutoPersist(false);
			$view->addParamsWithQuickSearch($query, true);
			list($results, $total) = $view->getData();
			
			if(0 == $total) {
				$params['action'] = 'create';
				unset($params['inputs']['query']);
				return $this->_activateCreate($params, $dict, $node_memory, $policy, $error);
				
			} elseif (1 == $total) {
				$params['action'] = 'update';
				$params['inputs']['id'] = key($results);
				unset($params['inputs']['query']);
				return $this->_activateUpdate($params, $dict, $node_memory, $policy, $error);
				
			} else {
				throw new Exception_DevblocksAutomationError("An upsert query must match exactly one or zero records.");
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
	}
}