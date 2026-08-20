<?php
namespace Cerb\LLM\Providers;

use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

/**
 * Together speaks the OpenAI chat-completions wire format, so it inherits the whole request/response
 * path — including SSE streaming, `request_timeout`, `Exception_DevblocksLlmApiError` (which is what
 * lets the queue tell a retryable failure from a permanent one) and neutral `images:` expansion, none
 * of which its hand-copied chatCompletion() had. Its embeddings API is its own.
 */
class TogetherAI extends OpenAI {
	const ID = 'together';

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor, which would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.together.xyz');

		if($validate && !$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:together:authentication: is required.');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:together:model: is required.');
	}

	/**
	 * OpenAI renamed `system` to `developer`, but that rename is OpenAI's alone — the compatible
	 * endpoints validate against a fixed role enum and hard-reject `developer` with a 400.
	 */
	function getSystemPromptRole() : string {
		return 'system';
	}

	// Together's optional moderation model. Reasoning rides its own seam (_getReasoningParams), so this
	// override no longer costs the author their `effort:`.
	function getChatCompletionsParams() : array {
		$params = [];

		if(($safety_model = $this->getParam('safety_model', '')))
			$params['safety_model'] = $safety_model;

		return $params;
	}

	// OpenAI's tool-vs-reasoning refusal is its own endpoint's rule, and no Together model id matches the
	// `^gpt-(\d+)` probe it keys on -- armed, it would strip the author's effort on every tool-using turn.
	protected function _appliesToolReasoningGuardrail(string $model) : bool {
		return false;
	}

	// Together fronts many vendors' open models and support for the param varies across them; the graded
	// scale is what its reasoning-capable families read. Which models honor it is Together's call.
	function getEffortLevels() : array {
		return ['low', 'medium', 'high'];
	}

	// Together rejects an assistant turn carrying an EMPTY `tool_calls` array, which cross-provider
	// replay can produce, so drop the key rather than send it.
	function sanitizeMessages(array $messages) : array {
		return array_map(
			function($message) {
				if(
					'assistant' == ($message['role'] ?? null)
					&& array_key_exists('tool_calls', $message)
					&& !($message['tool_calls'] ?? null)
				) {
					unset($message['tool_calls']);
				}

				return $message;
			},
			parent::sanitizeMessages($messages)
		);
	}

	// Together wants the tool NAME alongside the id on a tool result; OpenAI's base message omits it.
	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'tool',
			'name' => $tool->getName(),
			'tool_call_id' => $tool->getId(),
			'content' => $content,
		];

		$memory->appendMessage($tool_message);
	}

	// A hosted inference API with no documented wall-clock prompt-cache TTL, so a countdown would lie.
	function getCacheHintSeconds(array $params) : ?int {
		return null;
	}

	/**
	 * Together's own embeddings API. Kept rather than inherited: OpenAI's sends `encoding_format: float`
	 * and defaults to an OpenAI model id, neither of which belongs here.
	 *
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();

		$base_url = rtrim($this->getParam('api_endpoint_url', 'https://api.together.xyz'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$model = $this->getParam('model', 'BAAI/bge-large-en-v1.5');

		$verb = 'POST';
		$url = $this->getEmbeddingsEndpointUrl($base_url);
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
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped.'],
				['caption' => 'effort:', 'snippet' => 'effort: ${1:medium}', 'docHTML' => '<b>effort:</b> Reasoning effort, passed through as the OpenAI-compatible <code>reasoning_effort</code>. Support varies by model across the vendors served here. Validated by the provider, not here.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.together.xyz'],
				'safety_model:' => ['Meta-Llama/Llama-Guard-7b'],
				'effort:' => $this->getEffortLevels(),
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