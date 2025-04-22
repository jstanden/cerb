<?php
namespace Cerb\AutomationBuilder\Action;

use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LlmEmbedAction extends AbstractAction {
	const ID = 'llm.embed';
	
	private array $_inputs = [];
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$params = $automation->getParams($this->node, $dict);
		$policy = $automation->getPolicy();
		
		$this->_inputs = $params['inputs'] ?? [];
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
			
			$validation->addField('llm', 'llm:')
				->array()
				->setRequired(true);
			
			$validation->addField('texts', 'texts:')
				->stringOrArray()
				->setRequired(true)
			;
			
			if(false === ($validation->validateAll($this->_inputs, $error)))
				throw new Exception_DevblocksAutomationError($error);
			
			// Policy
			
			$action_dict = DevblocksDictionaryDelegate::instance([
				'node' => [
					'id' => $this->node->getId(),
					'type' => self::ID,
				],
				'inputs' => $this->_inputs,
				'output' => $output,
			]);
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = "The automation policy does not allow the `llm.embed:` command.";
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if(!($llm_provider = $this->_getLlmProvider())) {
				$llm_id = array_key_first($this->_inputs['llm'] ?? []);
				$error = sprintf('Unknown LLM provider: %s', $llm_id);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if(!($llm_provider instanceof Embedding)) {
				$llm_id = array_key_first($this->_inputs['llm'] ?? []);
				$error = sprintf('LLM provider does not support vector embeddings: %s', $llm_id);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if(!is_array($this->_inputs['texts']))
				$this->_inputs['texts'] = [$this->_inputs['texts']];
			
			if ($output) {
				$results = $llm_provider->embed($this->_inputs['texts']);
				$dict->set($output, $results);
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
	
	private function _getLlmProvider() : ?\Extension_DevblocksLlmProvider {
		$llm_id = array_key_first($this->_inputs['llm']);
		$llm_params = $this->_inputs['llm'][$llm_id] ?? [];
		return DevblocksPlatform::services()->llm()->getProvider($llm_id, $llm_params);
	}
}