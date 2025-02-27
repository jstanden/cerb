<?php
namespace Cerb\LLM\Providers;

use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class OpenAI extends Extension_DevblocksLlmProvider {
	const ID = 'openai';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.openai.com');
		
		if(!$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:openai:model: is required.');
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		
		$model_messages = $this->sanitizeMessages($messages);
		
		// Always start with the system prompt
		if($system_prompt) {
			array_unshift($model_messages,
				[
					'role' => 'developer',
					'content' => $system_prompt,
				]
			);
		}
		
		$verb = 'POST';
		$url = $base_url . '/v1/chat/completions';
		$headers = [
			'Content-Type' => 'application/json',
		];
		$body_payload = [
			'model' => $this->getParam('model', ''),
			'stream' => false,
			'messages' => $model_messages,
		];
		
		if($tools)
			$body_payload['tools'] = $tools;
		
		$body = json_encode($body_payload);
		
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
		
		$message = $response_json['choices'][0]['message'] ?? null;
		
		// Add to the memory
		if($message)
			$memory->appendMessage($message);
		
		$chat_response = new DevblocksLlmChatResponse();
		
		if($message['content'] ?? null)
			$chat_response->pushMessage($message['content']);
		
		if($message['tool_calls'] ?? null) {
			foreach($message['tool_calls'] as $tool_call) {
				if(
					!($tool_call['function']['name'] ?? null)
					|| is_null($tool_call['id'] ?? null)
				) continue;
				
				$tool_args = $tool_call['function']['arguments'] ?? [];
				
				$tool = new DevblocksLlmChatResponse_Tool(
					$tool_call['function']['name'] ?? '',
					is_string($tool_args) ? json_decode($tool_args, true) : $tool_args,
					$tool_call['id'] ?? null,
				);
				
				$chat_response->pushTool($tool);
			}
		}
		
		return $chat_response;
	}
	
	function sanitizeMessages(array $messages) : array {
		// The first message must be role:user
		while(!empty($messages)) {
			$key = array_key_first($messages);
			
			if(
				($messages[$key]['role'] ?? '') == 'user'
			) break;
			
			// Otherwise prune the message
			unset($messages[$key]);
		}
		
		return array_values($messages);
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'tool_call_id' => $tool->getId(),
			'content' => $content,
		];
		
		$memory->appendMessage($tool_message);
	}
}