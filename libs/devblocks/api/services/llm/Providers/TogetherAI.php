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

class TogetherAI extends Extension_DevblocksLlmProvider implements Chat, Embedding {
	const ID = 'together';
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.together.xyz');
		
		if($validate && !$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:together:authentication: is required.');
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:together:model: is required.');
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

		// Reasoning models return their chain of thought in a sibling key, often with an empty `content`.
		$this->_pushMessageReasoning($message, $chat_response);

		// Surface any neutral `images:` (resource uris) for the transcript viewer.
		$this->_pushMessageImages($message, $chat_response);

		return $chat_response;
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url', 'https://api.together.xyz'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$model = $this->getParam('model', 'BAAI/bge-large-en-v1.5');
		
		$verb = 'POST';
		$url = $base_url . '/v1/embeddings';
		$headers = [
			'Content-Type' => 'application/json',
		];
		$body_payload = [
			'model' => $model,
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
			'embeddings' => array_map(
				fn($data) => $data['embedding'] ?? [],
				$response_json['data'] ?? []
			),
		];
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$model = $this->getParam('model', '');
		$safety_model = $this->getParam('safety_model', '');
		
		$model_messages = $this->sanitizeMessages($messages);
		
		// Remove empty `tool_calls`
		$model_messages = array_map(function($message) {
			if (
				'assistant' == ($message['role'] ?? null)
				&& array_key_exists('tool_calls', $message)
				&& !($message['tool_calls'] ?? null)
			) {
				unset($message['tool_calls']);
			}
			return $message;
		}, $model_messages);
		
		// Always start with the system prompt
		if($system_prompt) {
			array_unshift($model_messages,
				[
					'role' => 'system',
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
			'model' => $model,
			'stream' => false,
			'messages' => $model_messages,
		];
		
		if($safety_model)
			$body_payload['safety_model'] = $safety_model;
		
		if($tools)
			$body_payload['tools'] = $tools;
		
		$body = json_encode($body_payload);
		
		$request = new Request($verb, $url, $headers, $body);
		$request_options = [
			'http_errors' => false,
		];
		// Off-request callers (the async agent worker) may allow far longer than the 30s default.
		$this->_applyRequestTimeout($request_options);
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

		// Why generation stopped. A SIBLING of `message`, so it must ride along explicitly.
		$finish_reason = self::normalizeFinishReason($response_json['choices'][0]['finish_reason'] ?? null);

		// Add to the memory
		if($message)
			$memory->appendMessage($message, finish_reason: $finish_reason);

		$response = $this->convertToGenericMessage($message);
		$response->setFinishReason($finish_reason);

		return $response;
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
		
		// Expand any neutral `images:` into native content parts (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'name' => $tool->getName(),
			'tool_call_id' => $tool->getId(),
			'content' => $content,
		];
		
		$memory->appendMessage($tool_message);
	}

	function getChatModels() : array {
		return [
			'meta-llama/Meta-Llama-3.1-8B-Instruct-Turbo',
			'meta-llama/Meta-Llama-3.1-70B-Instruct-Turbo',
			'meta-llama/Meta-Llama-3.1-405B-Instruct-Turbo',
			'meta-llama/Llama-3.3-70B-Instruct-Turbo',
			'mistralai/Mixtral-8x7B-Instruct-v0.1',
			'mistralai/Mistral-7B-Instruct-v0.1',
			'Qwen/Qwen2.5-7B-Instruct-Turbo',
			'Qwen/Qwen2.5-72B-Instruct-Turbo',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				'safety_model:',
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.together.xyz'],
				'safety_model:' => ['Meta-Llama/Llama-Guard-7b'],
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'BAAI/bge-base-en-v1.5',
			'BAAI/bge-large-en-v1.5',
			'togethercomputer/m2-bert-80M-2k-retrieval',
			'togethercomputer/m2-bert-80M-8k-retrieval',
		];
	}

	function getEmbeddingKataAutocomplete() : array {
		return [
			'keys' => [
				'api_endpoint_url:',
				'authentication:',
				'model:',
			],
			'values' => [
				'model:' => $this->getEmbeddingModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.together.xyz'],
			],
		];
	}
}