<?php
namespace Cerb\AutomationBuilder\Action;

use Cerb\LLM\MemoryStore\NoHistory;
use Cerb\LLM\Providers\Interfaces\Chat;
use DevblocksDictionaryDelegate;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LlmChatAction extends AbstractAction {
	const ID = 'llm.chat';
	
	private array $_inputs = [];
	private string $_output = '';
	private DevblocksDictionaryDelegate $_dict;
	private array $_node_memory = [];
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$validation = DevblocksPlatform::services()->validation();
		
		$policy = $automation->getPolicy();
		
		$this->_node_memory =& $node_memory;
		$this->_dict = $dict;
		
		$params = $automation->getParams($this->node, $this->_dict);
		$this->_inputs = $params['inputs'] ?? [];
		$this->_output = $params['output'] ?? '';
		
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
			
			$validation->addField('messages', 'messages:')
				->array()
				->setRequired(true);
			
			$validation->addField('system_prompt', 'system_prompt:')
				->string()
				->setMaxLength(200_000)
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
				'output' => $this->_output,
			]);
			
			if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
				$error = "The automation policy does not allow the `llm.chat:` command.";
				throw new Exception_DevblocksAutomationError($error);
			}
			
			$llm_provider = $this->_getLlmProvider();
			
			if(!($llm_provider instanceof Chat)) {
				$llm_id = array_key_first($this->_inputs['llm'] ?? []);
				$error = sprintf('LLM provider does not support chat completions: %s', $llm_id);
				throw new Exception_DevblocksAutomationError($error);
			}
			
			if(!$this->_activateLLM($error))
				return false;
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());
			
			if (null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if ($this->_output) {
					$dict->set($this->_output, [
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
	
	/**
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateLLM(?string &$error=null) : bool {
		$llm_provider = $this->_getLlmProvider(); /* @var $llm_provider Chat */
		
		// LLM
		$llm_response = $llm_provider->chatCompletion(
			$this->_inputs['messages'] ?? [],
			$this->_inputs['system_prompt'] ?? '',
			[],
			new NoHistory()
		);
		
		// Return an abstract list of messages
		$this->_dict->set($this->_output, [
			'messages' => $llm_response->getMessages(),
		]);
		
		return true;
	}
}