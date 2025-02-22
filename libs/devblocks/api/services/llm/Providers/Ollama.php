<?php
namespace Cerb\LLM\Providers;

use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Ollama extends Extension_DevblocksLlmProvider {
	const ID = 'ollama';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:ollama:api_endpoint_url: is required.');
		
		if(!$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:ollama:model: is required.');
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError|\GuzzleHttp\Exception\GuzzleException
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		
		$model_messages = $messages;
		
		// Always start with the system prompt
		array_unshift($model_messages,
			[
				'role' => 'system',
				'content' => $system_prompt,
			]
		);
		
		$verb = 'POST';
		$url = $base_url . '/api/chat';
		$headers = [
			'Content-Type' => 'application/json',
		];
		$body = json_encode([
			'model' => $this->getParam('model', ''),
			'stream' => false,
			'messages' => $model_messages,
			'tools' => $tools,
		]);
		
		$request = new Request($verb, $url, $headers, $body);
		$request_options = [
			'http_errors' => false,
		];
		$error = null;
		
		// Authenticate the request if required
		if($authentication_uri) {
			if(!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				throw new Exception_DevblocksAutomationError($error);
		}
		
		if(false === ($response = $http->sendRequest($request, $request_options, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if(false === ($response_json = $http->getResponseAsJson($response, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if(200 != $response->getStatusCode()) {
			if($response_json['error']['message'] ?? null)
				throw new Exception_DevblocksAutomationError($response_json['error']['message']);
			
			throw new Exception_DevblocksAutomationError('HTTP status code: ' . $response->getStatusCode());
		}
		
		// Add to the memory
		if($response_json['message'] ?? null)
			$messages = array_merge($messages, [$response_json['message']]);
		
		$chat_response = new DevblocksLlmChatResponse();
		
		if($response_json['message']['content'] ?? null)
			$chat_response->pushMessage($response_json['message']['content']);
		
		if($response_json['message']['tool_calls'] ?? null) {
			foreach($response_json['message']['tool_calls'] as $tool_call) {
				$chat_response->pushTool(new DevblocksLlmChatResponse_Tool(
					$tool_call['function']['name'] ?? '',
					$tool_call['function']['arguments'] ?? [],
				));
			}
		}
		
		$memory->setMessages($messages);
		
		return $chat_response;
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$memory_messages = $memory->getMessages();
		
		$tool_message = [
			'role' => 'tool',
			'name' => $tool->getName(),
			'content' => $content,
		];
		
		$memory_messages[] = $tool_message;
		
		$memory->setMessages($memory_messages);
	}
}