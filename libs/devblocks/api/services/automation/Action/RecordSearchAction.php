<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;
use Model_Automation;

class RecordSearchAction extends AbstractAction {
	const ID = 'record.search';
	
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
			
			$validation->addField('record_type', 'inputs:record_type:')
				->context()
				->setRequired(true)
			;
			
			$validation->addField('record_query', 'inputs:record_query:')
				->string()
				->setMaxLength(65_536)
				->setRequired(true)
			;
			
			$validation->addField('record_query_params', 'inputs:record_query_params:')
				->array()
			;
			
			$validation->addField('record_expand', 'inputs:record_expand:')
				->string()
				->setMaxLength(2048)
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
			$record_query = $inputs['record_query'] ?? null;
			$record_query_params = $inputs['record_query_params'] ?? [];
			$record_expand_keys = DevblocksPlatform::parseCsvString($inputs['record_expand'] ?? null);
			
			if ($record_type && $record_query) {
				if (false == ($context_ext = Extension_DevblocksContext::getByAlias($record_type, true)))
					throw new Exception_DevblocksAutomationError(sprintf(
						"Unknown record type `%s`",
						$record_type
					));
				
				// [TODO] Boundary query
				
				$view = $context_ext->getTempView();
				$view->addParamsWithQuickSearch($record_query, true, $record_query_params);
				$models = $view->getDataAsObjects();

				$record_dicts = DevblocksDictionaryDelegate::getDictionariesFromModels($models, $context_ext->id, $record_expand_keys);
				
				if(1 === $view->renderLimit) {
					$dict->set($output, current($record_dicts));
				} else {
					$dict->set($output, $record_dicts);
				}
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