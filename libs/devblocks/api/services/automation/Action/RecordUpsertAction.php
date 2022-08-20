<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksContext;
use Model_Automation;

class RecordUpsertAction extends AbstractAction {
	const ID = 'record.upsert';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		$was_events_enabled = DevblocksPlatform::services()->event()->isEnabled();
	
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
			
			$validation->addField('disable_events', 'inputs:disable_events:')
				->boolean()
			;

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
			
			$validation->addField('fields', 'inputs:fields:')
				->array()
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
			$query = $inputs['record_query'] ?? null;
			$query_params = $inputs['record_query_params'] ?? [];
			$disable_events = boolval($inputs['disable_events'] ?? null);
			
			if($disable_events)
				DevblocksPlatform::services()->event()->disable();
			
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
			$view->addParamsWithQuickSearch($query, true, $query_params);
			list($results, $total) = $view->getData();
			
			// If the results were limited to a single result, treat that as the entire set
			if($view->renderLimit == 1)
				$total = $total ? 1 : 0;
			
			// We want to proxy our unparsed params to `record.create` or `record.update` 
			$unparsed_params = $this->node->getParams();
			
			$unparsed_params['inputs'] = array_filter(
				$unparsed_params['inputs'],
				function($k) {
					// Exclude `record_query` and `record_query_params` keys
					if(
						'record_query' == $k
						|| DevblocksPlatform::strStartsWith($k, 'record_query@')
						|| 'record_query_params' == $k
					) {
						return false;
					}
					
					return true;
				},
				ARRAY_FILTER_USE_KEY
			);
			
			if(0 == $total) {
				$action_node = clone $this->node;
				$action_node->setType('record.create');
				$action_node->setParams($unparsed_params);
				$action = new RecordCreateAction($action_node);
				return $action->activate($automation, $dict, $node_memory, $error);
				
			} elseif (1 == $total) {
				$unparsed_params['inputs']['record_id'] = key($results);
				
				$action_node = clone $this->node;
				$action_node->setType('record.update');
				$action_node->setParams($unparsed_params);
				$action = new RecordUpdateAction($action_node);
				return $action->activate($automation, $dict, $node_memory, $error);
				
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
			
		} finally {
			// Reset the event listener status
			DevblocksPlatform::services()->event()->setEnabled($was_events_enabled);
		}
	}
}