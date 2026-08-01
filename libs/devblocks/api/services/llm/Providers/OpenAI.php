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

	function getIcon() : string {
		return 'logo-openai';
	}

	function getIconColor() : string {
		return '#6E6E80';
	}

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

			// Reasoning models return their chain of thought in a sibling key, often with an empty `content`.
			$this->_pushMessageReasoning($message, $chat_response);

			// Surface any neutral `images:` (resource uris) for the transcript viewer.
			$this->_pushMessageImages($message, $chat_response);
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
			'model' => $this->getParam('model', 'text-embedding-3-large'),
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

		// Canonical `effort:` → OpenAI's `reasoning_effort` wire param. Verbatim — levels are version-dependent
		// (GPT-5: minimal|low|medium|high; GPT-5.4 adds none|xhigh; GPT-5.6 adds max) and validated by the API,
		// not here. The legacy `reasoning_effort:` authoring key was removed in 11.2 (standardized on `effort:`).
		if(($effort = $this->getEffort())) {
			$params['reasoning_effort'] = $effort;
		}

		return $params;
	}
	
	/**
	 * Does this model know `none` as a reasoning level? GPT-5 shipped minimal|low|medium|high; `none` arrived
	 * in GPT-5.4. Parsed out of the id rather than whitelisted — free-text model ids are deliberate here, so a
	 * new release works the day it ships. An id we can't parse returns FALSE: sending an invalid level is a
	 * 400, while not sending one is at worst the status quo.
	 */
	protected function _supportsReasoningEffortNone(string $model) : bool {
		if(!preg_match('/^gpt-(\d+)(?:\.(\d+))?/', DevblocksPlatform::strLower(trim($model)), $matches))
			return false;

		$major = intval($matches[1]);
		$minor = intval($matches[2] ?? 0);

		return $major > 5 || (5 === $major && $minor >= 4);
	}

	/**
	 * OpenAI's /v1/chat/completions refuses function tools on a REASONING turn for the gpt-5.x family and
	 * directs you to /v1/responses. Its own stated remedy is an explicit `reasoning_effort: none`.
	 *
	 * Omitting the param is NOT equivalent — the model then falls back to its own non-none default and the
	 * call fails identically ("Function tools with reasoning_effort are not supported for gpt-5.6-terra in
	 * /v1/chat/completions"). That was the original guardrail's mistaken premise.
	 *
	 * So force `none` whenever we're sending tools to a model that knows the level. This overrides an
	 * author's `effort:` ON PURPOSE: on this endpoint the choice is a tool-using agent with no reasoning or
	 * no agent at all. Models predating the `none` level keep the omit behavior — there's nothing better to
	 * send them, and their turn may well work.
	 *
	 * Split out of chatCompletion so the rule is testable without a live call; it has been wrong once.
	 */
	protected function _applyToolReasoningGuardrail(array $params, array $tools, string $model) : array {
		if(!$tools)
			return $params;

		if($this->_supportsReasoningEffortNone($model)) {
			$params['reasoning_effort'] = 'none';
		} else {
			unset($params['reasoning_effort']);
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

		// Add provider-specific body params, then reconcile reasoning effort with tools (see the guardrail)
		$provider_params = $this->_applyToolReasoningGuardrail(
			$this->getChatCompletionsParams(),
			$tools,
			$this->getParam('model', '')
		);

		if($provider_params) {
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

		// Neutral token usage. OpenAI's prompt_tokens INCLUDES cached, so fresh input = prompt − cached;
		// cache_read = cached_tokens; OpenAI doesn't report cache writes (0).
		$native_usage = $response_json['usage'] ?? [];
		$cached = intval($native_usage['prompt_tokens_details']['cached_tokens'] ?? 0);
		$usage = [
			'input' => max(0, intval($native_usage['prompt_tokens'] ?? 0) - $cached),
			'output' => intval($native_usage['completion_tokens'] ?? 0),
			'cache_read' => $cached,
			'cache_write' => 0,
		];

		$message = $response_json['choices'][0]['message'] ?? null;

		// Add to the memory (usage rides the assistant turn — usage_json column, not the replayed data_json)
		if($message)
		
		return $this->convertToGenericMessage($message);
			$memory->appendMessage($message, usage: $usage, finish_reason: $finish_reason);
		$response->setUsage($usage);
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

		// Expand any neutral `images:` into native `image_url` content parts (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}
	
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'tool_call_id' => $tool->getId(),
			'content' => $content,
		];
		
		$memory->appendMessage($tool_message);
	}

	function getChatModels() : array {
		return [
			'gpt-5.6-sol',
			'gpt-5.6-terra',
			'gpt-5.6-luna',
			'gpt-5.5',
			'gpt-5.5-pro',
			'gpt-5.4',
			'gpt-5.4-pro',
			'gpt-5.4-mini',
			'gpt-5.4-nano',
			'gpt-5.3-codex',
			'gpt-5.3-chat-latest',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'effort:', 'snippet' => "effort: medium", 'docHTML' => '<b>effort:</b>Reasoning effort (empty = provider default). Version-dependent values, e.g. <code>none|minimal|low|medium|high|xhigh|max</code>.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.openai.com', 'http://host.docker.internal:8080'],
				'effort:' => ['none', 'minimal', 'low', 'medium', 'high', 'xhigh', 'max'],
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'text-embedding-3-small',
			'text-embedding-3-large',
			'text-embedding-ada-002',
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
				'api_endpoint_url:' => ['https://api.openai.com'],
			],
		];
	}
}