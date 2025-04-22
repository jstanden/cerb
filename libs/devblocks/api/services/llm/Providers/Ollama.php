<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Chat;
use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Ollama extends Extension_DevblocksLlmProvider implements Chat, Embedding {
	const ID = 'ollama';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if($validate && !$this->getParam('api_endpoint_url'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:ollama:api_endpoint_url: is required.');
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:ollama:model: is required.');
	}
	
	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);
		
		if('tool' == $message['role'] ?? '') {
			$chat_response->setRole('tool');
			$chat_response->pushToolResult($message['name'] ?? '', $message['content'] ?? '');
		
		} else {
			if(array_key_exists('role', $message))
				$chat_response->setRole($message['role']);
			
			if(is_string($message['content'] ?? null)) {
				$chat_response->pushMessage($message['content']);
				
			} elseif(is_array($message['content'] ?? null)) {
				foreach($message['content'] as $content) {
					if('text' == $content['type']) {
						$chat_response->pushMessage($content['content']);
					} elseif('tool_result' == $content['type']) {
						$chat_response->pushToolResult($content['name'] ?? '', $content['content'] ?? '');
					}
				}
			}
			
			if($message['tool_calls'] ?? null) {
				foreach($message['tool_calls'] as $tool_call) {
					$chat_response->pushTool(new DevblocksLlmChatResponse_Tool(
						$tool_call['function']['name'] ?? '',
						$tool_call['function']['arguments'] ?? [],
					));
				}
			}
		}
		
		return $chat_response;
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		
		$verb = 'POST';
		$url = $base_url . '/api/embed';
		$headers = [
			'Content-Type' => 'application/json',
		];
		$body_payload = [
			'model' => $this->getParam('model', ''),
			'input' => $texts
		];
		
		$body = json_encode($body_payload);
		
		$request = new Request($verb, $url, $headers, $body);
		$request_options = [
			'http_errors' => false,
		];
		$error = null;
		
		// Authenticate the request if required
		if ($authentication_uri) {
			if (!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				throw new Exception_DevblocksAutomationError($error);
		}
		
		if (false === ($response = $http->sendRequest($request, $request_options, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if (false === ($response_json = $http->getResponseAsJson($response, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if (200 != $response->getStatusCode()) {
			if ($response_json['error']['message'] ?? null)
				throw new Exception_DevblocksAutomationError($response_json['error']['message']);
			
			throw new Exception_DevblocksAutomationError('HTTP status code: ' . $response->getStatusCode());
		}
		
		return [
			'embeddings' => $response_json['embeddings'] ?? [],
		];
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError|\GuzzleHttp\Exception\GuzzleException
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		
		$model_messages = $this->sanitizeMessages($messages);
		
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
		
		// Add to the memory
		if($response_json['message'] ?? null)
			$memory->appendMessage($response_json['message']);
		
		return $this->convertToGenericMessage($response_json['message']);
	}
	
	function sanitizeMessages(array $messages) : array {
		return array_values($messages);
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'name' => $tool->getName(),
			'content' => $content,
		];
		
		$memory->appendMessage($tool_message);
	}
}