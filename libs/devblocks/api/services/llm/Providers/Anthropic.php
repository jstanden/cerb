<?php
namespace Cerb\LLM\Providers;

use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Anthropic extends Extension_DevblocksLlmProvider {
	const ID = 'anthropic';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.anthropic.com');
		
		if(!$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:anthropic:authentication: is required.');
		
		if(!$this->getParam('max_tokens'))
			$this->setParam('max_tokens', 2048);
		
		if(!$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:anthropic:model: is required.');
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$max_tokens = intval($this->getParam('max_tokens', 2048));
		
		$body_payload = [
			'model' => $this->getParam('model', ''),
			'max_tokens' => $max_tokens,
			'stream' => false,
			'messages' => $messages,
		];
		
		if($system_prompt)
			$body_payload['system'] = $system_prompt;
		
		// Convert OpenAI format tools to Anthropic format
		if($tools) {
			$tools = array_map(function($tool){
				$tool = $tool['function'];
				
				if($tool['parameters'] ?? null) {
					$tool['input_schema'] = $tool['parameters'];
					unset($tool['parameters']);
				}
				
				return $tool;
			}, $tools);
			
			$body_payload['tools'] = $tools;
		}
		
		$verb = 'POST';
		$url = $base_url . '/v1/messages';
		$headers = [
			'Content-Type' => 'application/json',
			'anthropic-version' => '2023-06-01', // [TODO] Configurable
		];
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
		
		// Add to the memory
		if($response_json['content'] ?? null)
			$messages = array_merge($messages, [
				[
					'role' => $response_json['role'],
					'content' => $response_json['content'],
				]
			]);
		
		$chat_response = new DevblocksLlmChatResponse();
		
		foreach($response_json['content'] ?? [] as $message_content) {
			if ('text' == ($message_content['type'] ?? null))
				$chat_response->pushMessage($message_content['text']);
			
			if ('tool_use' == $message_content['type'] ?? null) {
				if (!($message_content['id'] ?? null) || !($message_content['name'] ?? null))
					continue;
				
				$tool = new DevblocksLlmChatResponse_Tool(
					$message_content['name'] ?? '',
					$message_content['input'] ?? [],
					$message_content['id'],
				);
				
				$chat_response->pushTool($tool);
			}
		}
		
		$memory->setMessages($messages);
		
		return $chat_response;
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$memory_messages = $memory->getMessages();
		
		$tool_message = [
			'role' => 'user',
			'content' => [
				[
					'type' => 'tool_result',
					'tool_use_id' => $tool->getId(),
					'content' => $content,
				],
			],
		];
		
		$memory_messages[] = $tool_message;
		
		$memory->setMessages($memory_messages);
	}
}