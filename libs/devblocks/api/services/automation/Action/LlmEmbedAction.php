<?php
namespace Cerb\AutomationBuilder\Action;

use DAO_Automation;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LlmEmbedAction extends AbstractAction {
	const ID = 'llm.embed';
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		
		$validation = DevblocksPlatform::services()->validation();
		$automator = DevblocksPlatform::services()->automation();
		
		$params = $automation->getParams($this->node, $dict);
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
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($params, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			$validation->reset();
			
			// Inputs validation
			
			$validation->addField('uri', 'uri:')
				->string()
				->setMaxLength(512)
				->setRequired(true)
			;
			
			$validation->addField('texts', 'texts:')
				->stringOrArray()
				->setRequired(true)
			;
			
			$validation->addField('params', 'params:')
				->array()
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
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = "The automation policy does not allow the `llm.embed:` command.";
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if (!($automation = DAO_Automation::getByUri($inputs['uri'], \AutomationTrigger_LlmEmbedding::ID))) {
				throw new Exception_DevblocksAutomationError(sprintf('inputs:uri: (%s) must be an llm.embed trigger', $inputs['uri']));
			}
			
			if(!is_array($inputs['texts']))
				$inputs['texts'] = [$inputs['texts']];
			
			$initial_state = [
				'texts' => $inputs['texts'] ?? [],
				'inputs' => $inputs['params'] ?? [],
			];
			
			if (!($automation_results = $automator->executeScript($automation, $initial_state, $error))) {
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
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());
			
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