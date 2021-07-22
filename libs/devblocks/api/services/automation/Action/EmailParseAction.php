<?php
namespace Cerb\AutomationBuilder\Action;

use CerberusParser;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception;
use Exception_DevblocksAutomationError;
use Model_Automation;

class EmailParseAction extends AbstractAction {
	const ID = 'email.parse';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $this->node->getParams($dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Params validation
			
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
			
			$validation->addField('message', 'inputs:message:')
				->string()
				->setRequired(true)
				->setMaxLength(16_777_216)
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
			
			$ticket = CerberusParser::parseMessageSource($inputs['message']);
			
			if($output)
				$dict->set($output, $ticket);
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if(null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
				if($output) {
					$dict->set($output, [
						'error' => $e->getMessage(),
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			
			if($output) {
				$dict->set($output, [
					'error' => 'An unexpected error occurred.',
				]);
			}
			
			if (null != ($event_error = $this->node->getChildBySuffix(':on_error'))) {
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