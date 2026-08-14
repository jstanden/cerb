<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Chat;
use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Exception_DevblocksLlmApiError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Ollama extends Extension_DevblocksLlmProvider implements Chat, Embedding {
	const ID = 'ollama';

	function getIcon() : string {
		return 'logo-ollama';
	}

	function getIconColor() : string {
		return '#3D3D3D';
	}

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
		// Off-request callers (the async agent worker) may allow far longer than the 30s default.
		$this->_applyRequestTimeout($request_options);
		$error = null;
		
		// Authenticate the request if required
		if($authentication_uri) {
			if(!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				throw new Exception_DevblocksAutomationError($error);
		}
		
		// No response at all (connect refused, DNS, cURL timeout) -> status 0, a transient class.
		if(false === ($response = $http->sendRequest($request, $request_options, $error)))
			throw new Exception_DevblocksLlmApiError($error, 0);

		if(false === ($response_json = $http->getResponseAsJson($response, $error)))
			throw new Exception_DevblocksAutomationError($error);

		// Typed, like every other chat provider: without the status a failed Ollama turn reached the interaction
		// classified as futile and worded as unreachable, whatever actually went wrong. Ollama Cloud is a hosted
		// endpoint with its own rate limits, so this is no longer only a localhost provider.
		if(200 != $response->getStatusCode()) {
			$status_code = $response->getStatusCode();
			$retry_after = $this->_getRetryAfterSecs($response);

			if($response_json['error']['message'] ?? null)
				throw new Exception_DevblocksLlmApiError($response_json['error']['message'], $status_code, $retry_after);

			throw new Exception_DevblocksLlmApiError('HTTP status code: ' . $status_code, $status_code, $retry_after);
		}

		// Why generation stopped. Ollama reports it top-level, OUTSIDE the `message` the converter sees.
		$finish_reason = self::normalizeFinishReason($response_json['done_reason'] ?? null);

		// Neutral token usage. Ollama has no `usage` block -- the counts are top-level siblings of `message`,
		// like `done_reason`. It reports no cache accounting at all, so both cache legs are 0 and the whole
		// prompt always counts as fresh input.
		$usage = [
			'input' => intval($response_json['prompt_eval_count'] ?? 0),
			'output' => intval($response_json['eval_count'] ?? 0),
			'cache_read' => 0,
			'cache_write' => 0,
		];

		// Add to the memory (usage rides the assistant turn -- usage_json column, not the replayed data_json)
		if($response_json['message'] ?? null)
			$memory->appendMessage($response_json['message'], usage: $usage, finish_reason: $finish_reason);

		$response = $this->convertToGenericMessage($response_json['message']);
		$response->setUsage($usage);
		$response->setFinishReason($finish_reason);

		return $response;
	}
	
	function sanitizeMessages(array $messages) : array {
		// Fix tool calls with no inputs
		foreach($messages as $message_index => $message) {
			if(!array_key_exists('tool_calls', $message))
				continue;
			
			$messages[$message_index]['tool_calls'] = array_map(
				function($tool_call) {
					// Fix tool use for empty inputs [] -> {}
					if(
						($tool_call['function'] ?? null)
						&& is_array($tool_call['function']['arguments'])
						&& empty($tool_call['function']['arguments'])
					) $tool_call['function']['arguments'] = (object)[];
					
					return $tool_call;
				},
				$message['tool_calls']
			);
		}
		
		// Expand any neutral `images:` into native content parts (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'name' => $tool->getName(),
			'content' => $content,
		];
		
		$memory->appendMessage($tool_message);
	}

	// Ollama lists what's actually PULLED locally -- its own API, not the OpenAI-compatible one, since
	// `/api/tags` is the endpoint that exists on every version.
	function getChatModelsEndpointUrl(string $base_url) : string {
		return $base_url . '/api/tags';
	}

	protected function _parseChatModelsResponse(array $response_json) : array {
		return array_values(array_filter(array_map(
			fn($row) => strval($row['name'] ?? ''),
			$response_json['models'] ?? []
		)));
	}

	function getChatModels() : array {
		return [
			'llama3.2',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				// Ollama Cloud is the bare host; a path suffix like `/v1` switches it to OpenAI compatibility.
				'api_endpoint_url:' => ['http://localhost:11434', 'https://ollama.com', 'http://host.docker.internal:11434'],
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'nomic-embed-text',
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
				'api_endpoint_url:' => ['http://localhost:11434', 'http://host.docker.internal:11434'],
			],
		];
	}
}