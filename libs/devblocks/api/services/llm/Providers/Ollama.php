<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Chat;
use Cerb\LLM\Providers\Interfaces\ChatStreaming;
use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Exception_DevblocksLlmApiError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class Ollama extends Extension_DevblocksLlmProvider implements Chat, ChatStreaming, Embedding {
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
		// Read BEFORE _consumeStreamingFlag() runs -- Ollama signals streaming in the body, so the request
		// has to be built already knowing.
		$streaming = $this->_isStreamingTurn();

		$body_payload = [
			'model' => $this->getParam('model', ''),
			'stream' => $streaming,
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
		
		if($streaming) {
			// Consume the one-shot flag before doing anything that can throw, so a failed streamed turn
			// can't leave the provider silently primed to stream the next one too.
			$response_json = $this->_streamTurn($request, $request_options, $this->_consumeStreamingFlag());

		} else {
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

	/**
	 * Ollama streams newline-delimited JSON, not Server-Sent Events, so the transport is the one piece of
	 * the base's streaming stack that doesn't fit. Everything above it -- the abort, the salvage seam, the
	 * error classification, the not-actually-a-stream fallback -- is unchanged.
	 */
	protected function _sendStream(Request $request, array $request_options, callable $on_event, &$error, &$aborted) {
		return DevblocksPlatform::services()->http()->sendNdjsonStreamRequest(
			$request, $request_options, $on_event, $error, $aborted
		);
	}

	/**
	 * NDJSON has no keepalive frame, so the entire time-to-first-token reads as a stall -- and here that
	 * window is unusually wide at both ends of the deployment range. A local server may have to LOAD the
	 * model before it can emit a token (a large one, cold, off disk), and Ollama Cloud may queue the
	 * request behind others on shared capacity. Neither is a stuck stream.
	 *
	 * Matches AwsBedrock's reasoning, which has the same no-keepalive transport. The absolute `timeout`
	 * backstop is still what bounds a genuinely dead connection.
	 */
	protected function _minStreamStallSecs() : int {
		return 180;
	}

	/**
	 * Ollama's NDJSON grammar. Each line is a whole response object carrying a `message` DELTA; the final
	 * one adds `done_reason` and the token counts. Rebuilds the SAME `$response_json` the blocking path
	 * parses (`message` + `done_reason` + `prompt_eval_count`/`eval_count`) so usage mapping,
	 * appendMessage() and convertToGenericMessage() stay on one code path.
	 *
	 * Simpler than the OpenAI family in the one place that usually hurts: a tool call arrives as a COMPLETE
	 * object in a single chunk, with `arguments` already decoded, so there are no index-keyed fragments to
	 * reassemble and no partially-streamed argument JSON to judge.
	 */
	protected function _streamAccumulator() : array {
		$content = '';
		$thinking = '';
		$tool_calls = [];
		$finish_reason = null;
		$usage = [];
		$api_error = null;

		$on_event = function(string $event, string $data) use (
			&$content, &$thinking, &$tool_calls, &$finish_reason, &$usage, &$api_error
		) : void {
			if(!is_array($d = json_decode(trim($data), true)))
				return;

			// An API error can arrive INSIDE a 200. Ollama reports it as a bare STRING with no type, unlike
			// the OpenAI family's object -- so there is nothing to map and the base's retryable default
			// (500) stands. Record it and let the stream end; the base throws once the transfer closes, so
			// this doesn't read as our own abort.
			if(($err = $d['error'] ?? null)) {
				$api_error = is_array($err) ? $err : ['type' => '', 'message' => strval($err)];
				return;
			}

			if(($reason = $d['done_reason'] ?? null))
				$finish_reason = $reason;

			// The counts ride the FINAL chunk alone -- there is no incremental usage to accumulate, so a
			// turn that dies mid-stream reports none and falls back to the length estimate.
			foreach(['prompt_eval_count', 'eval_count'] as $key) {
				if(array_key_exists($key, $d))
					$usage[$key] = intval($d[$key]);
			}

			if(!is_array($message = $d['message'] ?? null))
				return;

			if(is_string($message['content'] ?? null))
				$content .= $message['content'];

			// Reasoning models stream their chain of thought here, routinely alongside an EMPTY content.
			if(is_string($message['thinking'] ?? null))
				$thinking .= $message['thinking'];

			foreach(($message['tool_calls'] ?? []) as $tool_call) {
				if(is_array($tool_call) && ($tool_call['function']['name'] ?? null))
					$tool_calls[] = $tool_call;
			}
		};

		// The turn so far, in the shape that gets persisted AND replayed to the API verbatim. Empty content
		// becomes NULL rather than '': hasReplayableContent() reads a blank string as nothing to keep, which
		// would discard a salvaged tool-call turn -- the exact turn most worth keeping.
		$snapshot = function() use (&$content, &$thinking, &$tool_calls) : array {
			$message = [
				'role' => 'assistant',
				'content' => ('' !== $content) ? $content : null,
			];

			if('' !== $thinking)
				$message['thinking'] = $thinking;

			if($tool_calls)
				$message['tool_calls'] = $tool_calls;

			return $message;
		};

		// NB: `use (&$x)`, not `fn() => $x`. An arrow function captures BY VALUE at creation, which here is
		// before a single event has landed -- every one of these would report the empty initial state.
		return [
			'on_event' => $on_event,
			'snapshot' => $snapshot,
			'usage' => function() use (&$usage) : array {
				return $usage;
			},
			'error' => function() use (&$api_error) : ?array {
				return $api_error;
			},
			'assemble' => function() use ($snapshot, &$finish_reason, &$usage) : array {
				return array_merge([
					'message' => $snapshot(),
					'done_reason' => $finish_reason,
				], $usage);
			},
		];
	}

	// An ordinary chat response -- what the blocking path parses. Used by the base's `$saw_event` fallback
	// when we asked for a stream and got a normal body back (an endpoint that ignores `stream`, a proxy that
	// buffers). Degrading to non-streamed is the correct outcome, not a failure.
	protected function _parseNonStreamedBody(array $body) : ?array {
		return isset($body['message']) ? $body : null;
	}

	/**
	 * Structural salvage rules for a turn cut short. Purely structural by contract -- a worker killed
	 * mid-stream is recovered on the session's NEXT turn by a different process that never saw the stream,
	 * and (via resolveDanglingStream) by a provider built with no params at all.
	 *
	 * There is no truncated-arguments tell to apply here, unlike the OpenAI family: Ollama delivers a tool
	 * call whole or not at all, and `arguments` is already a decoded object rather than a string being
	 * appended to. A name is the only thing worth checking -- it is also how returnTool() pairs the result,
	 * since these calls carry no id.
	 */
	public function sanitizePartialContent(array $message) : array {
		// Blank content becomes null rather than being dropped: that IS the shape for a turn that is
		// nothing but tool calls, and it is what gets replayed to the API.
		$content = $message['content'] ?? null;
		$message['content'] = (is_string($content) && '' !== trim($content)) ? $content : null;

		if(
			array_key_exists('thinking', $message)
			&& (!is_string($message['thinking']) || '' === trim($message['thinking']))
		) unset($message['thinking']);

		$kept = [];

		foreach(($message['tool_calls'] ?? []) as $tool_call) {
			if(!is_array($tool_call) || !is_array($fn = $tool_call['function'] ?? null))
				continue;

			if('' === trim(strval($fn['name'] ?? '')))
				continue;

			$kept[] = $tool_call;
		}

		if($kept)
			$message['tool_calls'] = $kept;
		else
			unset($message['tool_calls']);

		return $message;
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
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped. Set <code>no</code> for a proxy in front of Ollama that buffers responses.'],
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