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
 */
class HuggingFace extends OpenAI {
	const ID = 'huggingface';

	function getIcon() : string {
		return 'logo-huggingface';
	}

	function getIconColor() : string {
		return '#FFD21E';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		// Skip OpenAI's constructor, which would default the endpoint to api.openai.com.
		Extension_DevblocksLlmProvider::__construct($params, false);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://router.huggingface.co');

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

	// `max_tokens`, and nothing else. Reasoning rides its own seam (_getReasoningParams), so this override
	// no longer costs the author their `effort:`.
	function getChatCompletionsParams() : array {
		return [
			'max_tokens' => intval($this->getParam('max_tokens', 2048)),
		];
	}

	// OpenAI's tool-vs-reasoning refusal is its own endpoint's rule, and no model id served here matches the
	// `^gpt-(\d+)` probe it keys on -- armed, it would strip the author's effort on every tool-using turn.
	protected function _appliesToolReasoningGuardrail(string $model) : bool {
		return false;
	}

	// Support varies wildly across the open models served here -- this is the graded scale the reasoning-capable
	// ones read, offered as a vocabulary. Which models honor it is the serving backend's call, not ours.
	function getEffortLevels() : array {
		return ['low', 'medium', 'high'];
	}

	/**
	 * Kept rather than inherited for the `tool` branch: HuggingFace's stored tool message carries a `name`
	 * alongside `tool_call_id`, and the pairing here tolerates a row that has only the name.
	 *
	 * Pairing reads the ID first. It used to read the name, which collapsed N parallel calls to the same tool
	 * onto one key -- and a name in that slot matches no call at all, so a transcript found no result to show
	 * and toNativeMessage() replayed an unmatchable `tool_call_id` onto another provider. returnTool() below
	 * has always stored both keys, so every existing row pairs by id without a migration.
	 */
	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);

		if('tool' == $message['role'] ?? '') {
			$chat_response->setRole('tool');
			$tool_result_id = strval($message['tool_call_id'] ?? '') ?: strval($message['name'] ?? '');
			$chat_response->pushToolResult($tool_result_id, $message['content'] ?? '');

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
	 * Feature-extraction rides a LONGER path than chat: the serving backend is named in it (`hf-inference`),
	 * and the task is a `/pipeline/<task>` suffix that only feature-extraction and sentence-similarity take.
	 * Both segments are load-bearing -- without them the router has no route and answers 404.
	 *
	 * CRLF-stripped because the model lands in a request line.
	 */
	function getEmbeddingsEndpointUrl(string $base_url) : string {
		$model = DevblocksPlatform::services()->string()->strStripCrlf($this->getParam('model', ''));

		return sprintf('%s/hf-inference/models/%s/pipeline/feature-extraction', $base_url, $model);
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

		$base_url = rtrim($this->getParam('api_endpoint_url', 'https://router.huggingface.co'), '/');
		$authentication_uri = $this->getParam('authentication', null);

		$verb = 'POST';
		$url = $this->getEmbeddingsEndpointUrl($base_url);
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

	// Hints, not an inventory -- the router fronts thousands of models across a dozen serving backends, and
	// the authoritative list is the live one from fetchChatModels() (the editor's Refresh button). Ordered by
	// how many backends serve them, so the top of the list is the least likely to be unavailable.
	function getChatModels() : array {
		return [
			'openai/gpt-oss-120b',
			'zai-org/GLM-5.2',
			'google/gemma-4-31B-it',
			'deepseek-ai/DeepSeek-V4-Pro',
			'moonshotai/Kimi-K3',
			'meta-llama/Llama-3.3-70B-Instruct',
			'Qwen/Qwen3.8-27B',
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
				['caption' => 'effort:', 'snippet' => 'effort: ${1:medium}', 'docHTML' => '<b>effort:</b> Reasoning effort, passed through as the OpenAI-compatible <code>reasoning_effort</code>. Support varies widely across the open models served here. Validated by the provider, not here.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://router.huggingface.co'],
				'effort:' => $this->getEffortLevels(),
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
				'api_endpoint_url:' => ['https://router.huggingface.co'],
			],
		];
	}
}