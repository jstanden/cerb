<?php
namespace Cerb\AutomationBuilder\Action;

use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class QueuePopAction extends AbstractAction {
	const ID = 'queue.pop';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$queue = DevblocksPlatform::services()->queue();
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
			
			$validation->addField('queue_name', 'inputs:queue_name:')
				->string()
				->setRequired(true)
				;
			
			$validation->addField('limit', 'inputs:limit:')
				->number()
				;
			
			if(false === ($validation->validateAll($inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
				
			$queue_name = $inputs['queue_name'];
			$limit = $inputs['limit'] ?? 1;
			
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
			
			$consumer_id = null;
			
			$results = DevblocksPlatform::objectsToArrays($queue->dequeue($queue_name, $limit, $consumer_id));
			
			$results = array_combine(
				array_column($results, 'uuid'),
				array_map(
					function($result) use ($queue_name) {
						return [
							'queue' => $queue_name,
							'data' => $result['message'] ?? [],
						];
					}, 
					$results
				)
			);
			
			$output_dict = [
				'consumer_id' => $consumer_id,
				'messages' => $results,
			];
			
			$dict->set($output, $output_dict);
			
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