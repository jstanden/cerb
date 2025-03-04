<?php
namespace Cerb\AutomationBuilder\Node;

use CerberusContexts as CerberusContextsAlias;
use DAO_Automation;
use DevblocksDictionaryDelegate;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LlmAgentNode extends AbstractNode {
	const ID = 'llm.agent';
	
	private array $_inputs = [];
	private string $_output = '';
	private DevblocksDictionaryDelegate $_dict;
	private array $_node_memory = [];
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, string &$error=null) : string|false {
		$this->_node_memory =& $node_memory;
		$this->_dict = $dict;
		
		@set_time_limit(30);
		
		try {
			$params = $automation->getParams($this->node, $this->_dict);
			$this->_inputs = $params['inputs'] ?? [];
			$this->_output = $params['output'] ?? '';
			
			// If this is a new activation, invoke the LLM first
			if(!array_key_exists('stack', $this->_node_memory)) {
				$validation = DevblocksPlatform::services()->validation();
				
				// Params validation
				
				$validation->addField('inputs', 'inputs:')
					->array();
				
				$validation->addField('output', 'output:')
					->string()
					->setRequired(true);
				
				if (false === ($validation->validateAll($params, $error)))
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
					->string();
				
				$validation->addField('tools', 'tools:')
					->array();
				
				if (false === ($validation->validateAll($this->_inputs, $error)))
					throw new Exception_DevblocksAutomationError($error);
				
				// Policy
				
				$policy = $automation->getPolicy();
				
				$action_dict = DevblocksDictionaryDelegate::instance([
					'node' => [
						'id' => $this->node->getId(),
						'type' => self::ID,
					],
					'inputs' => $this->_inputs,
					'output' => $this->_output,
				]);
				
				if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
					$error = "The automation policy does not allow the `llm.agent:` command.";
					throw new Exception_DevblocksAutomationError($error);
				}
				
				$this->_node_memory['stack'] = [
					['llm', []],
				];
			}
		
			$llm_provider = $this->_getLlmProvider();
			$session_key = '__session::' . $this->node->getId();
			
			if(!($this->_dict->getKeyPath($session_key, null, '::'))) {
				if(!($llm_session = \DAO_LlmAgentSession::create($llm_provider::ID)))
					throw new Exception_DevblocksAutomationError("Failed to create an LLM session");
				
				$this->_dict->setKeyPath($session_key, $llm_session->uuid, '::');
			}
			
			$state = array_pop($this->_node_memory['stack']);
			
			// Run our next state
			if($state) {
				list($state, $state_params) = array_pad($state, 2, null);
				
				if(in_array($state, ['llm', 'tools_done'])) {
					if(!$this->_activateLLM($state, $error))
						return false;
					
					return $this->node->getId();
				
				} else if('tool_branch' == $state) {
					$this->_node_memory['stack'][] = ['tool_return', []];
					
					if (null != ($event_tool = $this->node->getChild($this->node->getId() . ':on_tool'))) {
						return $event_tool->getId();
					} else {
						return $this->node->getId();
					}
					
				} else if('tool_return' == $state) {
					$llm = DevblocksPlatform::services()->llm();
					
					$session_id = $this->_dict->getKeyPath('__session::' . $this->node->getId(), null, '::');
					$memory_store = $llm->getMemoryStore($session_id);
					
					$tool = $this->_dict->get('__tool', []);
					$tool_spec = new DevblocksLlmChatResponse_Tool($tool['name'] ?? '', $tool['parameters'] ?? [], $tool['id'] ?? '');
					
					$llm_provider->returnTool($tool_spec, $tool['content'] ?? '', $memory_store);
					
					$this->_dict->unset('__tool');
					
					return $this->node->getId();
					
				} else if('tool' == $state) {
					// [TODO] We can be given hallucinated tools
					
					if(!$this->_activateTool($state_params, $error))
						return false;
					
					return $this->node->getId();
					
				} else {
					// [TODO] Unknown state
					return false;
				}
				
			// If we're done, recurse back
			} else {
				unset($this->_node_memory['stack']);
				
				// [TODO] on_success, on_error ? (set activated when recursing down)
				
				/*
				if (null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
					return $event_success->getId();
				}
				*/
				
				return $this->node->getParent()->getId();
			}
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());
			
			if (null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if ($this->_output) {
					$this->_dict->set($this->_output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
	}
	
	private function _getLlmProvider() : \Extension_DevblocksLlmProvider {
		$llm_id = array_key_first($this->_inputs['llm']);
		$llm_params = $this->_inputs['llm'][$llm_id] ?? [];
		return DevblocksPlatform::services()->llm()->getProvider($llm_id, $llm_params);
	}
	
	private function _getTools() : array {
		$tools = [];
		
		foreach(($this->_inputs['tools'] ?? []) as $tool_key => $tool) {
			list($tool_type, $tool_name) = explode('/', $tool_key);
			if (empty($tool_name)) $tool_name = $tool_type;
			
			$tool['type'] = $tool_type;
			$tools[$tool_name] = $tool;
		}
		
		return $tools;
	}
	
	private function _getToolSchemaAutomation(string $tool_name, array $tool) : ?array {
		if(!array_key_exists('uri', $tool))
			return null;
		
		if(!($tool_automation = DAO_Automation::getByUri($tool['uri'], \AutomationTrigger_LlmTool::ID)))
			return null;
		
		// [TODO] Cache the tool inputs per automation
		$tool_dict = DevblocksDictionaryDelegate::getDictionaryFromModel($tool_automation, CerberusContextsAlias::CONTEXT_AUTOMATION, ['inputs']);
		
		// [TODO] strict mode
		
		$automation_inputs = $tool_dict->get('inputs', []);
		
		$tool_schema = [
			'type' => 'function',
			'function' => [
				'name' => $tool_name,
				'description' => $tool_automation->description ?? '',
				'parameters' => [
					'type' => 'object',
				],
			]
		];
		
		if($automation_inputs) {
			$tool_schema['function']['parameters']['properties'] = [];
			$tool_schema['function']['parameters']['required'] = [];
			
			foreach($automation_inputs as $automation_input) {
				$tool_property = [
					// [TODO] `type`
					'type' => 'string',
					'description' => $automation_input['description'] ?? '',
				];
				
				// [TODO] Validate
				if($automation_input['allowed_values'] ?? null && is_array($automation_input['allowed_values']))
					$tool_property['enum'] = $automation_input['allowed_values'];
				
				$tool_schema['function']['parameters']['properties'][$automation_input['key']] = $tool_property;
				
				if($automation_input['required'] ?? false)
					$tool_schema['function']['parameters']['required'][] = $automation_input['key'];
			}
		}
		
		return $tool_schema;
	}
	
	private function _getToolSchemaCustom(string $tool_name, array $tool) : ?array {
		$tool_schema = [
			'type' => 'function',
			'function' => [
				'name' => $tool_name,
				'description' => $tool['description'] ?? '',
				'parameters' => [
					'type' => 'object',
				],
			]
		];
		
		if(array_key_exists('parameters', $tool) && is_array($tool['parameters'])) {
			$tool_schema['function']['parameters']['properties'] = [];
			$tool_schema['function']['parameters']['required'] = [];
			
			foreach($tool['parameters'] as $param_key => $parameter) {
				list($param_type, $param_name) = array_pad(
					explode('/', $param_key),
					2,
					null
				);
				
				if(!$param_name)
					$param_name = $param_type;
				
				if('string' == $param_type) {
					$tool_schema['function']['parameters']['properties'][$param_name] = [
						'type' => 'string',
						'description' => $parameter['description'] ?? '',
					];
					
					if($parameter['required'] ?? false)
						$tool_schema['function']['parameters']['required'][] = $param_name;
				}
			}
		}
		
		return $tool_schema;
	}
	
	// [TODO] Cache these by signatures
	private function _getToolSchemas() : array {
		$tools = [];
		
		foreach($this->_getTools() as $tool_name => $tool) {
			$tool_type = $tool['type'] ?? null;
			
			$tool_schema = match($tool_type) {
				'automation' => $this->_getToolSchemaAutomation($tool_name, $tool),
				'tool' => $this->_getToolSchemaCustom($tool_name, $tool),
				default => null,
			};
			
			if($tool_schema)
				$tools[$tool_name] = $tool_schema;
		}
		
		return $tools;
	}
	
	/**
	 * @param string $state
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateLLM(string $state, string &$error=null) : bool {
		$llm = DevblocksPlatform::services()->llm();
		
		// Memory
		
		$session_id = $this->_dict->getKeyPath('__session::' . $this->node->getId(), null, '::');
		$memory_store = $llm->getMemoryStore($session_id);
		
		// Messages
		
		// [TODO] Configurable history length/strategy
		$memory_messages = $memory_store->getMessages(limit: 10);
		
		// If we're not running after tools, add the next message
		if('llm' == $state) {
			//$memory_messages = array_merge($memory_messages, array_values($this->_inputs['messages']));
			foreach($this->_inputs['messages'] ?? [] as $new_message) {
				$memory_messages[] = $new_message;
				$memory_store->appendMessage($new_message);
			}
		}
		
		// LLM
		
		$llm_provider = $this->_getLlmProvider();
		
		$llm_response = $llm_provider->chatCompletion(
			$memory_messages,
			$this->_inputs['system_prompt'] ?? '',
			array_values($this->_getToolSchemas()),
			$memory_store
		);
		
		// Tools
		
		if(($tool_calls = $llm_response->getToolCalls())) {
			// After the tools finish we need to invoke the LLM again
			$this->_node_memory['stack'][] = ['tools_done', []];
			
			// Push into the stack in reverse
			foreach(array_reverse($tool_calls) as $tool_call) {
				$this->_node_memory['stack'][] = ['tool', $tool_call];
			}
		}
		
		// Return an abstract list of messages
		$this->_dict->set($this->_output, [
			'messages' => $llm_response->getMessages(),
		]);
		
		return true;
	}
	
	/**
	 * @param DevblocksLlmChatResponse_Tool $tool_spec
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateTool(DevblocksLlmChatResponse_Tool $tool_spec, string &$error=null) : bool {
		$automator = DevblocksPlatform::services()->automation();
		$llm = DevblocksPlatform::services()->llm();
		
		$tools = $this->_getTools();
		
		$session_id = $this->_dict->getKeyPath('__session::' . $this->node->getId(), null, '::');
		$memory_store = $llm->getMemoryStore($session_id);
		
		$llm_provider = $this->_getLlmProvider();
		
		$tool = $tools[$tool_spec->getName()] ?? null;
		
		if($tool) {
			$tool_type = $tool['type'] ?? null;
			
			if('automation' == $tool_type) {
				if (!($tool_automation = DAO_Automation::getByUri($tool['uri'] ?? '', \AutomationTrigger_LlmTool::ID)))
					return false;
				
				$initial_state = [
					'inputs' => $tool_spec->getParameters() ?? [],
				];
				
				if (false === ($automation_results = $automator->executeScript($tool_automation, $initial_state, $error))) {
					$tool_response = [
						'content' => "ERROR: " . $error,
					];
				} else {
					// [TODO] Validate the return contains `content`
					$tool_response = $automation_results->get('__return', []);
				}
				
			} elseif ('tool' == $tool_type) {
				if($this->_output) {
					$this->_dict->set('__tool', [
						'id' => $tool_spec->getId(),
						'name' => $tool_spec->getName(),
						'parameters' => $tool_spec->getParameters(),
					]);
				}
				
				// Run the custom `on_tool:` branch
				if (null != ($this->node->getChild($this->node->getId() . ':on_tool'))) {
					$this->_node_memory['stack'][] = ['tool_branch', []];
					return true;
					
				} else {
					$tool_response = [
						'content' => 'ERROR: Tool is not implemented.'
					];
				}
			
			} else {
				$tool_response = [
					'content' => 'ERROR: Unknown tool type.'
				];
			}
			
		} else {
			$tool_response = [
				'content' => 'ERROR: This tool does not exist.'
			];
		}
		
		$llm_provider->returnTool($tool_spec, $tool_response['content'] ?? '', $memory_store);
		
		// [TODO] Customize the output for `on_tool:`
		if($this->_output)
			$this->_dict->set($this->_output, $tool_response);
		
		return true;
	}
}