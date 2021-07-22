<?php
namespace Cerb\AutomationBuilder\Action;

use AutomationTrigger_AutomationFunction;
use DAO_Automation;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class FunctionAction extends AbstractAction {
	const ID = 'function';
	
	/**
	 * @param Model_Automation $automation
	 * @param DevblocksDictionaryDelegate $dict
	 * @param array $node_memory
	 * @param string|null $error
	 * @return string|false
	 */
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) {
		$validation = DevblocksPlatform::services()->validation();
		$automator = DevblocksPlatform::services()->automation();
		
		$params = $this->node->getParams($dict);
		$policy = $automation->getPolicy();
		
		$inputs = $params['inputs'] ?? [];
		$output = $params['output'] ?? null;
		
		try {
			// Params validation
			
			$validation->addField('uri', 'uri:')
				->string()
				->setMaxLength(512)
				->setRequired(true)
			;
			
			$validation->addField('inputs', 'inputs:')
				->array()
			;
			
			$validation->addField('output', 'output:')
				->string()
			;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Policy
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'uri' => $params['uri'],
				'inputs' => $inputs,
				'output' => $output,
			]);
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = sprintf(
					"The automation policy does not allow the `function:` command."
				);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if (false == ($automation = DAO_Automation::getByUri($params['uri'], AutomationTrigger_AutomationFunction::ID))) {
				throw new Exception_DevblocksAutomationError(sprintf('Function (%s) must be an automation.function', $params['uri']));
			}
			
			$initial_state = [
				'inputs' => $inputs,
			];
			
			if (false == ($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
				throw new Exception_DevblocksAutomationError($error);
			}
			
			// Check exit code
			$exit_code = $automation_results->get('__exit');
			
			if ('error' == $exit_code) {
				$error = $automation_results->getKeyPath('__error.message', '');
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$end_state = $automation_results->get('__return');
			
			if ($output) {
				$dict->set($output, $end_state);
			}
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = $e->getMessage();
			
			if (null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if ($output) {
					$dict->set($output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
		
		if (null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
			return $event_success->getId();
		}
		
		return $this->node->getParent()->getId();
	}
}