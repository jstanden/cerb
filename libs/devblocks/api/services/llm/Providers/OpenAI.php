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

class OpenAI extends Extension_DevblocksLlmProvider implements Chat, Embedding {
	const ID = 'openai';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.openai.com');
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:openai:model: is required.');
	}
	
	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);
		
		if('tool' == $message['role'] ?? '') {
			$chat_response->setRole('tool');
			$chat_response->pushToolResult($message['tool_call_id'] ?? '', $message['content'] ?? '');
			
		} else {
			if (array_key_exists('role', $message))
				$chat_response->setRole($message['role']);
			
			if ($message['content'] ?? null)
				$chat_response->pushMessage($message['content']);
			
			if ($message['tool_calls'] ?? null) {
				foreach ($message['tool_calls'] as $tool_call) {
					if (
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
		}
		
		return $chat_response;
	}
	
	function getEmbeddingsEndpointUrl(string $base_url) : string {
		return $base_url . '/v1/embeddings';
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		
		$verb = 'POST';
		$url = $this->getEmbeddingsEndpointUrl($base_url);
		$headers = [
			'Content-Type' => 'application/json',
		];
		$body_payload = [
			'input' => $texts,
			'model' => $this->getParam('model', 'text-embedding-ada-002'),
			'encoding_format' => 'float',
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
			'embeddings' => array_map(
				fn($data) => $data['embedding'] ?? [],
				$response_json['data'] ?? []
			),
		];
	}
	
	function getChatCompletionEndpointUrl(string $base_url) : string {
		return $base_url . '/v1/chat/completions';
	}
	
	function getChatCompletionsParams() : array {
		$params = [];
		
		// low, medium, high
		if(($reasoning_effort = $this->getParam('reasoning_effort'))) {
			$params['reasoning_effort'] = $reasoning_effort;
		}
		
		return $params;
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
		$url = $this->getChatCompletionEndpointUrl($base_url);
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
		
		// Add provider-specific body params
		if(($provider_params = $this->getChatCompletionsParams())) {
			$body_payload = array_merge($body_payload, $provider_params);
		}
		
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
		
		return $this->convertToGenericMessage($message);
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