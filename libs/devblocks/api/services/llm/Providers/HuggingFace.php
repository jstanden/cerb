<?php
namespace Cerb\LLM\Providers;

use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

/**
 * HuggingFace's inference endpoints speak the OpenAI chat-completions wire format, so this inherits the
 * whole request/response path — including SSE streaming, `request_timeout`,
 * `Exception_DevblocksLlmApiError` (which is what lets the queue tell a retryable failure from a
 * permanent one) and neutral `images:` expansion, none of which its hand-copied chatCompletion() had.
 *
 * The one structural difference is that the MODEL ID RIDES THE URL PATH rather than only the body.
 */
class HuggingFace extends OpenAI {
	const ID = 'huggingface';

	function getIcon() : string {
		return 'logo-huggingface';
	}

	function getIconColor() : string {
		return '#FF9D00';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor, which would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api-inference.huggingface.co');

		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:huggingface:model: is required.');
	}

	/**
	 * OpenAI renamed `system` to `developer`, but that rename is OpenAI's alone — the compatible
	 * endpoints validate against a fixed role enum and hard-reject `developer` with a 400.
	 */
	function getSystemPromptRole() : string {
		return 'system';
	}

	// The model id is part of the PATH here, not just the body. CRLF-stripped because it lands in a
	// request line.
	function getChatCompletionEndpointUrl(string $base_url) : string {
		$model = DevblocksPlatform::services()->string()->strStripCrlf($this->getParam('model', ''));

		return sprintf('%s/models/%s/v1/chat/completions', $base_url, $model);
	}

	/**
	 * `max_tokens`, and nothing else.
	 *
	 * Deliberately NOT calling parent::, which emits `reasoning_effort` from the canonical `effort:` key
	 * — support varies wildly across the models served here, so forwarding it would be a behavior change
	 * smuggled in on a streaming refactor. Worth revisiting on its own.
	 *
	 * Leaving `reasoning_effort` out also neutralizes the inherited _applyToolReasoningGuardrail(): that
	 * helper force-sets or unsets it for the gpt-5.x family, and since no model id here matches its
	 * `^gpt-(\d+)` probe it would otherwise strip an author's effort on any tool-using turn.
	 */
	function getChatCompletionsParams() : array {
		return [
			'max_tokens' => intval($this->getParam('max_tokens', 2048)),
		];
	}

	/**
	 * Kept rather than inherited for ONE difference, on the `tool` branch: this pairs a tool result by
	 * the tool's NAME where every other OpenAI-family provider pairs by `tool_call_id`. returnTool()
	 * below writes both keys, so the id is available and pairing by it would be more correct when the
	 * same tool is called twice in a turn — but changing which key identifies a result would rewrite how
	 * existing HuggingFace transcripts replay, which is its own change and not this one.
	 */
	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);

		if('tool' == $message['role'] ?? '') {
			$chat_response->setRole('tool');
			$chat_response->pushToolResult($message['name'] ?? '', $message['content'] ?? '');

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

					$tool = new DevblocksLlmChatResponse_Tool(
						$tool_call['function']['name'] ?? '',
						$this->_normalizeToolParameters($tool_call['function']['arguments'] ?? []),
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

	// HuggingFace wants the tool NAME alongside the id on a tool result; OpenAI's base message omits it,
	// and the converter above reads that name back.
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
	 * HuggingFace's feature-extraction endpoint. Kept rather than inherited: it is NOT an
	 * OpenAI-shaped `/v1/embeddings` call — the model rides the path, the field is `inputs`, and the
	 * response IS the vector array rather than a `{data:[{embedding}]}` envelope.
	 *
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();

		$base_url = rtrim($this->getParam('api_endpoint_url', 'https://api-inference.huggingface.co'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$model = $this->getParam('model');

		$verb = 'POST';
		$url = $base_url . '/models/' . $model;
		$headers = [
			'Content-Type' => 'application/json',
		];
		$body_payload = [
			'inputs' => $texts,
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
			'embeddings' => $response_json,
		];
	}

	function getChatModels() : array {
		return [
			'meta-llama/Llama-3.2-3B-Instruct',
			'google/gemma-2-2b-it',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				'max_tokens@int: 2048',
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api-inference.huggingface.co'],
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'BAAI/bge-large-en-v1.5',
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
				'api_endpoint_url:' => ['https://api-inference.huggingface.co'],
			],
		];
	}
}