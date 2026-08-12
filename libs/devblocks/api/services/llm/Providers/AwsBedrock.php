<?php
namespace Cerb\LLM\Providers;

use Cerb\LLM\Providers\Interfaces\Chat;
use Cerb\LLM\Providers\Interfaces\ChatStreaming;
use Cerb\LLM\Providers\Interfaces\Embedding;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

class AwsBedrock extends Extension_DevblocksLlmProvider implements Chat, ChatStreaming, Embedding {
	const ID = 'aws_bedrock';

	// Per-model capabilities harvested from the live catalog during a fetchChatModels() pass, keyed by the id
	// it's invoked as (a foundation model id AND, for a profile, the profile id). Read back by
	// getModelDefaults() -- the agent model editor calls both on the same provider instance, which is the
	// only place this is populated. Empty at runtime, exactly like the base class.
	private array $_model_meta = [];

	function getIcon() : string {
		return 'logo-bedrock';
	}

	function getIconColor() : string {
		return '#EC7211';
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function __construct(array $params, bool $validate=true) {
		parent::__construct($params);
		
		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://bedrock-runtime.us-east-1.amazonaws.com');
		
		if($validate && !$this->getParam('authentication'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:aws_bedrock:authentication: is required.');
		
		if(!$this->getParam('max_tokens'))
			$this->setParam('max_tokens', 2048);
		
		if($validate && !$this->getParam('model'))
			throw new Exception_DevblocksAutomationError('llm:inputs:llm:aws_bedrock:model: is required.');
	}
	
	/**
	 * Converse content blocks are keyed by SHAPE, not by a `type` discriminator: `{text:…}`, `{toolUse:{…}}`,
	 * `{toolResult:{…}}`, `{reasoningContent:{…}}`, `{image:{…}}`. Anything unrecognized is skipped rather than
	 * guessed at.
	 */
	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);

		if(array_key_exists('role', $message))
			$chat_response->setRole($message['role']);

		// Converse always sends blocks, but a hand-authored message may still be a bare string.
		if(is_string($message['content'] ?? null))
			$message['content'] = [['text' => $message['content']]];

		foreach($message['content'] ?? [] as $block) {
			if(!is_array($block))
				continue;

			if(array_key_exists('text', $block)) {
				$chat_response->pushMessage(strval($block['text']));

			} elseif(is_array($block['toolUse'] ?? null)) {
				$tool_use = $block['toolUse'];

				if(!($tool_use['toolUseId'] ?? null) || !($tool_use['name'] ?? null))
					continue;

				$chat_response->pushTool(new DevblocksLlmChatResponse_Tool(
					strval($tool_use['name']),
					$tool_use['input'] ?? [],
					strval($tool_use['toolUseId']),
				));

			} elseif(is_array($block['toolResult'] ?? null)) {
				$chat_response->setRole('tool');
				$chat_response->pushToolResult(
					strval($block['toolResult']['toolUseId'] ?? ''),
					$this->_flattenToolResultContent($block['toolResult']['content'] ?? [])
				);

			} elseif(is_array($block['reasoningContent'] ?? null)) {
				// The reason this provider moved to Converse: on `/invoke` the OpenAI-compat layer inlined
				// reasoning into the answer text as `<reasoning>…</reasoning>`. Converse gives it its own block.
				$chat_response->pushThinking(strval($block['reasoningContent']['reasoningText']['text'] ?? ''));
			}
		}

		// Surface any neutral `images:` (resource uris) for the transcript viewer.
		$this->_pushMessageImages($message, $chat_response);

		return $chat_response;
	}

	/**
	 * Strip a model's INTERNAL tool-call markup out of its visible text.
	 *
	 * DeepSeek narrates a tool call in its text block using its own DSML control markup, redundantly with the
	 * structured `toolUse` block that carries the actual call. Bedrock then truncates the text where the call
	 * begins, so what lands is an AMPUTATED opener with no `>` and no closing tag -- observed verbatim:
	 *
	 *   "I'll check the weather in Paris for you.\n\n<\u{FF5C}DSML\u{FF5C}function_calls"
	 *
	 * That shape is why the usual `<tag>...</tag>` scrub doesn't work here: there is no well-formed element to
	 * match. Well-formed pairs are removed first (in case a model ever emits one), then any dangling opener
	 * through end-of-string.
	 *
	 * Done at PARSE time, before the message is persisted, so storage / replay / display all agree. That also
	 * keeps prompt caching safe: the bytes we store are the bytes we resend, so the prefix stays stable turn to
	 * turn. Scrubbing at send time instead would risk a different prefix per turn and cost every cache read.
	 * (`\u{FF5C}` is FULLWIDTH VERTICAL LINE, not an ASCII pipe -- the `u` flag is required.)
	 */
	private function _stripToolControlMarkup(array $message) : array {
		if(!is_array($message['content'] ?? null))
			return $message;

		$blocks = [];

		foreach($message['content'] as $block) {
			if(is_array($block) && array_key_exists('text', $block)) {
				$text = strval($block['text']);

				$text = preg_replace('/<\x{FF5C}DSML\x{FF5C}.*?<\/\x{FF5C}DSML\x{FF5C}[^>]*>/su', '', $text);
				$text = preg_replace('/<\x{FF5C}DSML\x{FF5C}.*$/su', '', $text);

				// A block that was ONLY markup has nothing left to say; the toolUse block still carries the call
				if('' === trim(strval($text)))
					continue;

				$block['text'] = rtrim($text);
			}

			$blocks[] = $block;
		}

		$message['content'] = $blocks;

		return $message;
	}

	// A Converse toolResult carries a LIST of blocks; the neutral model wants one scalar.
	private function _flattenToolResultContent(mixed $content) : string {
		if(is_string($content))
			return $content;

		if(!is_array($content))
			return strval($content);

		$out = '';

		foreach($content as $block) {
			if(is_string($block))
				$out .= $block;
			elseif(is_array($block) && array_key_exists('text', $block))
				$out .= strval($block['text']);
			elseif(is_array($block) && array_key_exists('json', $block))
				$out .= json_encode($block['json']);
		}

		return $out;
	}

	function toNativeMessage(DevblocksLlmChatResponse $message) : array {
		$tool_results = $message->getToolResults();

		// Converse tool results are user-role `toolResult` blocks, and their content is itself a block list.
		if($tool_results) {
			$blocks = [];

			foreach($tool_results as $tool_id => $content) {
				$blocks[] = ['toolResult' => [
					'toolUseId' => $tool_id,
					'content' => [['text' => is_array($content) ? json_encode($content) : strval($content)]],
				]];
			}

			return [[
				'role' => 'user',
				'content' => $blocks,
			]];
		}

		$blocks = [];

		foreach($message->getMessages() as $block) {
			if('' !== ($block['content'] ?? ''))
				$blocks[] = ['text' => $block['content']];
		}

		foreach($message->getToolCalls() as $tool) {
			$blocks[] = ['toolUse' => [
				'toolUseId' => $tool->getId(),
				'name' => $tool->getName(),
				'input' => $tool->getParameters() ?: (object)[],
			]];
		}

		$role = $message->getRole();
		$role = ('' === $role || 'tool' === $role) ? 'assistant' : $role;

		return [[
			'role' => $role,
			'content' => $blocks,
		]];
	}

	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function embed(array $texts) : array {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$model = $this->getParam('model', 'amazon.titan-embed-text-v2:0');
		$dimensions = $this->getParam('dimensions', 512);
		
		$embeddings = [];
		
		foreach($texts as $text) {
			$body_payload = [
				'inputText' => $text,
				'dimensions' => intval($dimensions),
				'normalize' => true,
			];
			
			$verb = 'POST';
			$url = $base_url . '/model/' . $model . '/invoke';
			$headers = [
				'Content-Type' => 'application/json',
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
			
			$embeddings[] = $response_json['embedding'];
		}
		
		return $embeddings;
	}
	
	/**
	 * @throws Exception_DevblocksAutomationError
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();
		
		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);
		$max_tokens = intval($this->getParam('max_tokens', 2048));
		$model = $this->getParam('model', 'us.anthropic.claude-haiku-4-5-20251001-v1:0');

		$body_payload = [
			'messages' => $this->sanitizeMessages($messages),
			'inferenceConfig' => ['maxTokens' => $max_tokens],
		];

		// Converse takes `system` as a BLOCK LIST, not a scalar.
		if($system_prompt)
			$body_payload['system'] = [['text' => $system_prompt]];

		// OpenAI `{type:function, function:{name,description,parameters}}` -> Converse `{toolSpec:{name,
		// description, inputSchema:{json}}}`. One format for every model, which is the whole point of Converse.
		if($tools) {
			$body_payload['toolConfig'] = ['tools' => array_values(array_filter(array_map(
				function($tool) {
					$fn = $tool['function'] ?? null;

					if(!is_array($fn) || !($fn['name'] ?? null))
						return null;

					$spec = [
						'name' => strval($fn['name']),
						// An argument-less tool still needs a schema object, never `[]`.
						'inputSchema' => ['json' => $fn['parameters'] ?: (object)['type' => 'object']],
					];

					// `description` is OPTIONAL to Converse but constrained to length >= 1, so an
					// undescribed tool must OMIT the key rather than send "". Sending it empty fails the
					// whole request -- "Value '' at 'toolConfig.tools.N.member.toolSpec.description' failed
					// to satisfy constraint" -- taking every other tool down with it. The OpenAI-family
					// endpoints accept the empty string, which is why this only bites here.
					if('' !== ($description = trim(strval($fn['description'] ?? ''))))
						$spec['description'] = $description;

					return ['toolSpec' => $spec];
				},
				$tools
			)))];
		}

		// Must come AFTER `system`/`toolConfig` are set -- the prefix marker is appended to the system list,
		// which caches tools+system together because they render first. Enabled by Cerb-primitive intent
		// (agent-on, chat-off), which the provider API can't infer for us -- AND gated on the model actually
		// supporting cachePoints, since sending one to a model that doesn't is a 403, not a no-op.
		if($this->_getCacheIntent()['enabled'] && $this->_supportsPromptCaching($model))
			$this->_applyPromptCache($body_payload);

		// Reasoning knobs. Converse has no first-class field for them; they ride in
		// `additionalModelRequestFields`, which it forwards to the model untouched.
		$this->_applyAdditionalModelRequestFields($body_payload);

		// ConverseStream takes the SAME body -- unlike Anthropic there is no `stream` field, the ENDPOINT is
		// the switch. Gated on the catalog as well as the caller's request: a model that can't stream must
		// fall back to sync silently rather than fail the turn (30 of 112 models in us-west-2 can't).
		$streaming = $this->_isStreamingTurn() && $this->_supportsConverseStreaming($model);

		$verb = 'POST';
		$url = $base_url . '/model/' . $model . ($streaming ? '/converse-stream' : '/converse');
		$headers = [
			'Content-Type' => 'application/json',
		];
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
			// Consume the one-shot flag before anything that can throw, so a failed streamed turn can't leave
			// the provider silently primed to stream the next one too.
			$response_json = $this->_streamTurn($request, $request_options, $this->_consumeStreamingFlag());

		} else {
			// Consume it here TOO. Reaching this branch with the flag set means the caller asked to stream and
			// the model can't -- leaving it armed would stream the NEXT turn, to a caller expecting sync.
			$this->_consumeStreamingFlag();

			if(false === ($response = $http->sendRequest($request, $request_options, $error)))
				throw new Exception_DevblocksAutomationError($error);

			if(false === ($response_json = $http->getResponseAsJson($response, $error)))
				throw new Exception_DevblocksAutomationError($error);

			// Converse reports failures as a TOP-LEVEL `{message}`, not OpenAI's `{error:{message}}` -- reading
			// only the nested key turned every validation error into a bare "HTTP status code: 400" with nothing
			// to act on. _getApiErrorMessage() knows both shapes.
			if(200 != $response->getStatusCode()) {
				throw new Exception_DevblocksAutomationError(
					$this->_getApiErrorMessage($response_json, $response->getStatusCode())
				);
			}
		}

		// Converse: one response shape for every model family. `output.message` is already the native
		// {role, content:[blocks]} we persist and replay.
		$native_message = $this->_stripToolControlMarkup($response_json['output']['message'] ?? []);
		$native_usage = is_array($response_json['usage'] ?? null) ? $response_json['usage'] : [];

		// Bedrock reports cache counters only on models that support caching, and ships BOTH spellings side by
		// side on Anthropic (`cacheReadInputTokens` and `cacheReadInputTokenCount`) -- take either.
		$usage = [
			'input' => intval($native_usage['inputTokens'] ?? 0),
			'output' => intval($native_usage['outputTokens'] ?? 0),
			'cache_read' => intval($native_usage['cacheReadInputTokens'] ?? $native_usage['cacheReadInputTokenCount'] ?? 0),
			'cache_write' => intval($native_usage['cacheWriteInputTokens'] ?? $native_usage['cacheWriteInputTokenCount'] ?? 0),
		];

		// `end_turn` / `tool_use` / `max_tokens` / `stop_sequence` / `content_filtered` / `guardrail_intervened`
		$finish_reason = self::normalizeFinishReason($response_json['stopReason'] ?? null);

		// Add to the memory (usage rides the assistant turn -- usage_json column, not the replayed data_json)
		if($native_message['content'] ?? null) {
			$memory->appendMessage($native_message, usage: $usage, finish_reason: $finish_reason);
		}

		// Outside the content guard on purpose: a turn that stops with zero content blocks persists no row,
		// but it still spent tokens and still has a reason -- both belong on the response either way.
		$response = $this->convertToGenericMessage($native_message);
		$response->setUsage($usage);
		$response->setFinishReason($finish_reason);

		return $response;
	}
	
	/**
	 * ConverseStream answers in AWS's binary `application/vnd.amazon.eventstream` framing, not SSE, so the
	 * default SSE sink can't read it. This is the ONLY reason Bedrock needed its own transport -- everything
	 * else about streaming (the one-shot flag, timeouts, abort, salvage, the non-stream fallback) is the
	 * base class's, unchanged.
	 */
	protected function _sendStream(Request $request, array $request_options, callable $on_event, &$error, &$aborted) {
		return DevblocksPlatform::services()->http()->sendEventStreamRequest(
			$request, $request_options, $on_event, $error, $aborted
		);
	}

	/**
	 * ConverseStream sends NOTHING while the model reasons -- no keepalive of any kind -- so the entire
	 * time-to-first-token reads as a stall. Measured against `us.anthropic.claude-sonnet-5`: a trivial
	 * prompt's first byte arrives at 1.9s, but a hard reasoning prompt sent **zero bytes for 45 seconds**
	 * and then delivered the whole answer in 32ms. A real agent turn ran 67s before its first byte.
	 *
	 * That is the difference from Anthropic's own SSE, which emits `ping` frames throughout thinking and so
	 * keeps a healthy turn's worst-case gap around 6s. Here silence is not evidence of a dead stream until
	 * it exceeds plausible think time, so the shared 60s window kills healthy turns.
	 *
	 * Three minutes is well past any reasoning latency observed and still far inside the 900s absolute
	 * ceiling, which is what actually bounds a wedged connection.
	 */
	protected function _minStreamStallSecs() : int {
		return 180;
	}

	/**
	 * ConverseStream's event grammar. Rebuilds the SAME `$response_json` the sync path parses -- an
	 * `{output:{message}}, usage, stopReason` triple -- so `convertToGenericMessage()`, the memory append,
	 * usage mapping and finish reason are all untouched and the two paths cannot drift.
	 *
	 * Blocks are keyed by `contentBlockIndex` while accumulating and re-indexed on the way out, because the
	 * index is streaming bookkeeping and `data_json` is replayed to the API verbatim.
	 */
	protected function _streamAccumulator() : array {
		$role = 'assistant';
		$blocks = [];
		$tool_json = [];
		$stop_reason = null;
		$usage = [];
		$api_error = null;

		// The persisted/replayed shape. Runs the same DeepSeek markup strip the sync path does -- the marker
		// arrives split across deltas, so it can only match once a block is assembled, never per-delta.
		$snapshot = function() use (&$role, &$blocks) : array {
			ksort($blocks);

			return $this->_stripToolControlMarkup([
				'role' => $role,
				'content' => array_values($blocks),
			]);
		};

		$on_event = function(string $event, string $data) use (
			&$role, &$blocks, &$tool_json, &$stop_reason, &$usage, &$api_error
		) : void {
			$d = ('' === $data) ? null : json_decode($data, true);

			if(!is_array($d))
				$d = [];

			// An `exception` frame arrives INSIDE a 200 and is named by its AWS exception type. Capture it and
			// let the stream end; the base throws once the transfer closes, so this can't read as our own abort.
			if(str_ends_with($event, 'Exception') || str_ends_with($event, 'Error')) {
				$api_error = [
					'type' => $event,
					'message' => strval($d['message'] ?? $d['Message'] ?? 'Streaming error.'),
				];
				return;
			}

			$idx = intval($d['contentBlockIndex'] ?? 0);

			switch($event) {
				case 'messageStart':
					$role = strval($d['role'] ?? 'assistant');
					break;

				case 'contentBlockStart':
					// Only a toolUse announces itself; a text block simply starts deltaing.
					if(is_array($tool_use = $d['start']['toolUse'] ?? null)) {
						$blocks[$idx] = ['toolUse' => [
							'toolUseId' => strval($tool_use['toolUseId'] ?? ''),
							'name' => strval($tool_use['name'] ?? ''),
						]];
						$tool_json[$idx] = '';
					}
					break;

				case 'contentBlockDelta':
					$delta = is_array($d['delta'] ?? null) ? $d['delta'] : [];

					if(is_string($delta['text'] ?? null)) {
						$blocks[$idx]['text'] = strval($blocks[$idx]['text'] ?? '') . $delta['text'];

					} elseif(is_array($delta['toolUse'] ?? null)) {
						// PARTIAL JSON STRINGS, not an object. Accumulate and decode once the block closes.
						$tool_json[$idx] = strval($tool_json[$idx] ?? '') . strval($delta['toolUse']['input'] ?? '');

					} elseif(is_array($delta['reasoningContent'] ?? null)) {
						$reasoning = $delta['reasoningContent'];

						// Its own block type -- the whole reason this provider speaks Converse. Keep it out of
						// the answer text or it lands in the reply instead of the thinking pane.
						if(is_string($reasoning['text'] ?? null)) {
							$blocks[$idx]['reasoningContent']['reasoningText']['text'] =
								strval($blocks[$idx]['reasoningContent']['reasoningText']['text'] ?? '') . $reasoning['text'];

						} elseif(is_string($reasoning['signature'] ?? null)) {
							// `text` FIRST, and unconditionally. A signature delta can arrive for a block that
							// has had no text delta yet, and Converse rejects a reasoningText whose `text` is
							// null on REPLAY -- "Member must not be null" -- which strands the session
							// permanently rather than failing one turn, because every later turn resends it.
							$blocks[$idx]['reasoningContent']['reasoningText']['text'] ??= '';
							$blocks[$idx]['reasoningContent']['reasoningText']['signature'] = $reasoning['signature'];

						} elseif(array_key_exists('redactedContent', $reasoning)) {
							$blocks[$idx]['reasoningContent']['redactedContent'] = $reasoning['redactedContent'];
						}
					}
					break;

				case 'contentBlockStop':
					if(!array_key_exists($idx, $blocks) || !is_array($blocks[$idx]['toolUse'] ?? null))
						break;

					$raw = trim(strval($tool_json[$idx] ?? ''));

					// No argument fragments at all is a legitimate no-argument call -- an empty OBJECT, never
					// `[]`, which Converse rejects. Mirrors the sync path's empty-input rule.
					if('' === $raw) {
						$blocks[$idx]['toolUse']['input'] = (object) [];
						break;
					}

					// A decode failure means the arguments were severed mid-token. Leave `input` ABSENT so the
					// block reads as unfinished downstream -- see sanitizePartialContent().
					if(is_array($decoded = json_decode($raw, true)))
						$blocks[$idx]['toolUse']['input'] = $decoded ?: (object) [];
					break;

				case 'messageStop':
					$stop_reason = $d['stopReason'] ?? $stop_reason;
					break;

				case 'metadata':
					// FINAL values, not increments -- replace, never sum, or the token denorm inflates.
					if(is_array($d['usage'] ?? null))
						$usage = $d['usage'];
					break;

				default:
					// `contentBlockStop` for a text block, `messageStop` variants, anything AWS adds later.
					break;
			}
		};

		// NB: `use (&$x)`, not `fn() => $x`. An arrow function captures BY VALUE at creation, which here is
		// before a single event has landed -- every one of these would report the empty initial state, so
		// usage would silently read zero while the text output looked perfectly correct.
		return [
			'on_event' => $on_event,
			'snapshot' => $snapshot,
			'usage' => function() use (&$usage) : array {
				return $usage;
			},
			'error' => function() use (&$api_error) : ?array {
				return $api_error;
			},
			'assemble' => function() use ($snapshot, &$usage, &$stop_reason) : array {
				return [
					'output' => ['message' => $snapshot()],
					'usage' => $usage,
					'stopReason' => $stop_reason,
				];
			},
		];
	}

	// An ordinary Converse response -- what the sync path parses. Used by the base's `$saw_event` fallback
	// when we asked for a stream and got a normal body back, so it degrades instead of returning an empty turn.
	protected function _parseNonStreamedBody(array $body) : ?array {
		return isset($body['output']['message']) ? $body : null;
	}

	/**
	 * Map an in-stream AWS exception back onto the HTTP status it would have carried in a non-streamed
	 * response, so the queue's retryable-vs-terminal classification keeps working despite the 200.
	 */
	protected static function _streamErrorStatus(string $type) : int {
		return match(lcfirst($type)) {
			'validationException' => 400,
			'accessDeniedException' => 403,
			'resourceNotFoundException' => 404,
			'modelTimeoutException' => 408,
			'throttlingException' => 429,
			'modelNotReadyException' => 429,
			'serviceUnavailableException' => 503,
			'modelStreamErrorException' => 500,
			'internalServerException' => 500,
			// Unknown types included: a transient novelty should stay retryable.
			default => 500,
		};
	}

	/**
	 * Structural salvage rules for a turn cut short. Purely structural by contract -- a worker killed
	 * mid-stream is recovered on the session's NEXT turn by a different process that never saw the stream,
	 * via a provider built with no params at all.
	 */
	public function sanitizePartialContent(array $message) : array {
		$kept = [];

		foreach(($message['content'] ?? []) as $block) {
			if(!is_array($block))
				continue;

			if(array_key_exists('text', $block)) {
				// An empty text block is noise, and a lone empty one would make the turn look answered.
				if('' !== trim(strval($block['text'])))
					$kept[] = $block;

			} elseif(is_array($block['toolUse'] ?? null)) {
				$tool_use = $block['toolUse'];

				// `input` is set ONLY when the block closed with parseable arguments (see the accumulator), so
				// its ABSENCE is what identifies a call severed mid-arguments -- readable by any process,
				// including one that never saw the stream. A call whose arguments we can't read can neither be
				// answered nor executed.
				if(
					'' !== strval($tool_use['toolUseId'] ?? '')
					&& '' !== strval($tool_use['name'] ?? '')
					&& array_key_exists('input', $tool_use)
				) $kept[] = $block;

			} elseif(is_array($block['reasoningContent'] ?? null)) {
				// A SIGNATURE alone is enough to keep the block: Claude on Bedrock routinely returns
				// `{"text":"","signature":"…"}` when the reasoning summary isn't surfaced, and that replays
				// fine -- the signature is what proves the reasoning wasn't tampered with. Redacted reasoning
				// is opaque and arrives whole. Only a block with none of the three is worth dropping.
				if(
					array_key_exists('redactedContent', $block['reasoningContent'])
					|| '' !== trim(strval($block['reasoningContent']['reasoningText']['text'] ?? ''))
					|| '' !== strval($block['reasoningContent']['reasoningText']['signature'] ?? '')
				) $kept[] = $block;

			} else {
				// Server-tool results and any block type added after this was written arrive complete.
				$kept[] = $block;
			}
		}

		$message['content'] = $kept;

		return $message;
	}

	function sanitizeMessages(array $messages) : array {
		// Converse requires the conversation to START on a user turn, and a leading `toolResult` is an orphan
		// (its `toolUse` was pruned with the assistant turn above it).
		while(!empty($messages)) {
			$key = array_key_first($messages);

			if(
				($messages[$key]['role'] ?? '') == 'user'
				&& !is_array($messages[$key]['content'][0]['toolResult'] ?? null)
			) break;

			// Prune non-user messages
			unset($messages[$key]);
		}

		// Converse has NO scalar-content shorthand -- `content` is always a block list, and a bare string is a
		// 400 ("expected list, got string"). Callers hand us `content: "..."` all the time (every first user
		// turn), and the Anthropic invoke API used to accept it, so normalize here rather than at each caller.
		foreach($messages as $message_index => $message) {
			if(is_string($message['content'] ?? null))
				$messages[$message_index]['content'] = ('' === $message['content'])
					? []
					: [['text' => $message['content']]];
		}

		// Converse requires EVERY toolResult answering an assistant turn to sit in the ONE user message that
		// follows it. The agent loop calls returnTool() once per tool and each call appends its own user
		// message, so a PARALLEL tool call (Kimi routinely emits three) is stored as N consecutive user turns.
		// Anthropic's API tolerates that shape -- there are hundreds of such turns in existing sessions --
		// but Converse rejects it: "Expected toolResult blocks at messages.4.content for the following Ids: …".
		//
		// Merged at SEND time on purpose: storage keeps one row per result, which is the honest history and
		// what returnTool() wrote, and it means sessions recorded before this fix replay correctly too.
		$merged = [];

		foreach($messages as $message) {
			$prev = $merged ? array_key_last($merged) : null;

			if(
				null !== $prev
				&& 'user' === ($message['role'] ?? '')
				&& 'user' === ($merged[$prev]['role'] ?? '')
				&& $this->_isAllToolResults($message['content'] ?? null)
				&& $this->_isAllToolResults($merged[$prev]['content'] ?? null)
			) {
				$merged[$prev]['content'] = array_merge($merged[$prev]['content'], $message['content']);
				continue;
			}

			$merged[] = $message;
		}

		$messages = $merged;

		// Fix tool calls with no inputs
		foreach($messages as $message_index => $message) {
			if(!is_array($message['content'] ?? null))
				continue;

			$messages[$message_index]['content'] = array_values(array_filter(array_map(
				function($content) {
					if(!is_array($content))
						return $content;

					// Fix tool use for empty inputs [] -> {}
					if(
						is_array($content['toolUse'] ?? null)
						&& is_array($content['toolUse']['input'] ?? null)
						&& empty($content['toolUse']['input'])
					) $content['toolUse']['input'] = (object)[];

					// Converse rejects a `reasoningText` whose `text` is null -- "Member must not be null" --
					// and it rejects it on EVERY subsequent turn, because history is replayed in full. So one
					// malformed block strands the whole session rather than costing a single turn.
					//
					// COERCED, never dropped. An EMPTY text with a signature is normal and valid: Claude on
					// Bedrock routinely returns `{"text":"","signature":"EoMC…"}` when the reasoning summary
					// isn't surfaced, and those turns replay fine. The signature is the part that matters --
					// dropping the block to avoid a null would throw away the proof the reasoning wasn't
					// tampered with, on every Bedrock Claude turn. Only the NULL is illegal.
					//
					// Repaired at SEND time, not just at write time, so a session ALREADY carrying a malformed
					// block (from a streamed turn whose signature delta landed before any text delta) starts
					// working again instead of having to be abandoned.
					if(is_array($content['reasoningContent']['reasoningText'] ?? null)) {
						if(!is_string($content['reasoningContent']['reasoningText']['text'] ?? null))
							$content['reasoningContent']['reasoningText']['text'] = '';

						// A block with neither reasoning nor a signature carries nothing at all.
						if(
							'' === $content['reasoningContent']['reasoningText']['text']
							&& '' === strval($content['reasoningContent']['reasoningText']['signature'] ?? '')
						) return null;
					}

					return $content;
				},
				$message['content']
			)));
		}

		// Expand any neutral `images:` into native content parts (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}

	// A user turn that is NOTHING but tool results -- the only kind safe to merge with its neighbour. A turn
	// mixing text with results is the author saying something alongside them, and must keep its own position.
	private function _isAllToolResults(mixed $blocks) : bool {
		if(!is_array($blocks) || !$blocks)
			return false;

		foreach($blocks as $block) {
			if(!is_array($block) || !is_array($block['toolResult'] ?? null))
				return false;
		}

		return true;
	}

	// Converse blocks carry no `type` discriminator, so the shared image expander needs the bare-`{text}` shape.
	protected function _nativeTextPart(string $text) : array {
		return ['text' => $text];
	}

	// Converse image block: a FORMAT enum (not a mime type) plus base64 bytes.
	protected function _nativeImagePart(string $mime_type, string $data) : ?array {
		if('' === $mime_type || '' === $data)
			return null;

		$format = match(DevblocksPlatform::strLower($mime_type)) {
			'image/png' => 'png',
			'image/jpeg', 'image/jpg' => 'jpeg',
			'image/gif' => 'gif',
			'image/webp' => 'webp',
			default => null,
		};

		// Converse rejects an unknown format outright, so drop the image rather than fail the whole turn.
		if(is_null($format))
			return null;

		return [
			'image' => [
				'format' => $format,
				'source' => ['bytes' => $data],
			],
		];
	}

	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		$tool_message = [
			'role' => 'user',
			'content' => [
				['toolResult' => [
					'toolUseId' => $tool->getId(),
					'content' => [['text' => $content]],
				]],
			],
		];

		$memory->appendMessage($tool_message);
	}

	/**
	 * Does this model accept `cachePoint` blocks?
	 *
	 * This has to be asked, not assumed: a cachePoint sent to a model that doesn't support caching is a hard
	 * **403** ("You invoked an unsupported model or your request did not allow prompt caching"), NOT a
	 * silently-ignored block. Sending them unconditionally broke every non-Claude/Nova model outright.
	 *
	 * The catalog is the authority (`explicitPromptCaching.isSupported`), so support is READ rather than
	 * guessed from a vendor whitelist that would go stale the day AWS enables caching on another family.
	 * It's cached for an hour because this sits on the chat path -- and every failure mode returns FALSE,
	 * which only costs a cache miss. Losing caching is survivable; a 403 on every turn is not.
	 */
	private function _supportsPromptCaching(string $model) : bool {
		return $this->_modelSupports($model, 'caching');
	}

	/**
	 * Can this model be streamed over `/converse-stream`?
	 *
	 * Read from the SAME hour-cached catalog map as caching -- one fetch, two booleans. The flag is
	 * `inferenceAPIsSupported.converse.streaming`; do NOT confuse it with the top-level
	 * `responseStreamingSupported`, which is the flag for the OLD InvokeModel API and answers a different
	 * question (measured across 112 models in us-west-2: no model has one without the other, so the
	 * distinction never buys coverage -- but reading the wrong one would still be wrong).
	 *
	 * False is the safe direction: the caller falls back to a sync `/converse` turn, which always works.
	 */
	private function _supportsConverseStreaming(string $model) : bool {
		return $this->_modelSupports($model, 'streaming');
	}

	/** Resolve one capability for a model id, falling back to the model an inference profile fronts. */
	private function _modelSupports(string $model, string $capability) : bool {
		$support = $this->_getModelSupport();

		if(array_key_exists($model, $support))
			return boolval($support[$model][$capability] ?? false);

		// An inference profile (`us.anthropic.foo`) isn't in the foundation-model catalog under that id --
		// resolve it to the model it fronts.
		$bare = preg_replace('#^[a-z0-9-]+\.(?=[a-z0-9-]+\.)#i', '', $model);

		return boolval($support[$bare][$capability] ?? false);
	}

	/**
	 * Per-model capabilities read from the live catalog: ONE fetch, cached an hour, two booleans. Adding a
	 * second lookup for streaming would have meant a second `/foundation-models` call on the chat path for
	 * data that arrives in the same row.
	 *
	 * Every failure mode yields an EMPTY map, which reads as false for everything -- no cachePoints (losing
	 * caching is survivable, a 403 per turn is not) and no streaming (falls back to a sync turn, which
	 * always works).
	 *
	 * @return array<string,array{caching:bool,streaming:bool}> modelId => capabilities
	 */
	private function _getModelSupport() : array {
		if(!($base_url = rtrim(strval($this->getParam('api_endpoint_url')), '/')))
			return [];

		$cache = DevblocksPlatform::services()->cache();

		// Deliberately NOT the old `bedrock_prompt_caching_` key: the value shape changed from a bare bool to
		// a capability map, and a warm cache from a previous deploy would deserialize into booleans that
		// `['caching']` silently reads as false. A new key lets the old one expire harmlessly.
		$cache_key = 'bedrock_model_support_' . sha1($base_url);

		if(is_array($support = $cache->load($cache_key)))
			return $support;

		$error = null;
		$response_json = $this->_fetchModelsJson($this->getChatModelsEndpointUrl($base_url), [], $error);

		// Cached even when empty so a broken lookup doesn't re-fire on every turn.
		$support = [];

		foreach(($response_json['modelSummaries'] ?? []) as $row) {
			if(!is_array($row) || '' === ($model_id = strval($row['modelId'] ?? '')))
				continue;

			$support[$model_id] = [
				'caching' => (bool) (
					($row['explicitPromptCaching']['isSupported'] ?? false)
					|| ($row['featuresSupported']['promptCaching'] ?? false)
				),
				// `inferenceAPIsSupported.converse.streaming`, NOT the top-level `responseStreamingSupported`
				// (that one is the old InvokeModel API's flag).
				'streaming' => (bool) ($row['inferenceAPIsSupported']['converse']['streaming'] ?? false),
			];
		}

		$cache->save($support, $cache_key, [], 3600);

		return $support;
	}

	/**
	 * Converse's prompt-cache marker is a `{cachePoint:{type:default}}` CONTENT BLOCK appended after the
	 * content it covers -- a different mechanism from Anthropic's `cache_control` attribute, which is why this
	 * provider has its own placement rather than sharing Anthropic's.
	 *
	 * Two cache points, mirroring the Anthropic policy:
	 *   1. PREFIX — after the last `system` block. Tools+system render first, so this caches them together;
	 *      its bytes are stable turn to turn, which is what makes it worth writing at all.
	 *   2. TAIL — after the last content block of the last message. Advances each turn.
	 *
	 * There is deliberately no TTL knob: Bedrock cache points have a fixed lifetime (~5 minutes), so
	 * `cache_ttl` is not offered for this provider. `cache_tail`/`cache_tail_skip` still apply.
	 */
	private function _applyPromptCache(array &$body_payload) : void {
		$intent = $this->_getCacheIntent();
		$cache_point = ['cachePoint' => ['type' => 'default']];

		if($this->_isCacheable($body_payload['system'] ?? null))
			$body_payload['system'][] = $cache_point;

		if($intent['tail'] && is_array($body_payload['messages'] ?? null) && $body_payload['messages']) {
			$keys = array_keys($body_payload['messages']);
			$last = $keys[count($keys) - 1 - $intent['tail_skip']] ?? null;

			if(is_null($last))
				return;

			if($this->_isCacheable($body_payload['messages'][$last]['content'] ?? null))
				$body_payload['messages'][$last]['content'][] = $cache_point;
		}
	}

	/**
	 * Is there anything here for a cache point to mark?
	 *
	 * A cachePoint must FOLLOW real content. Appending one to an empty block list leaves a message whose only
	 * block is the marker, which Bedrock rejects with "There is nothing available to cache" -- on every model,
	 * Claude included. Empty lists reach here legitimately: sanitizeMessages() turns a `content: ""` into `[]`.
	 * Also refuses to stack a second marker on a list that already ends in one.
	 */
	private function _isCacheable(mixed $blocks) : bool {
		if(!is_array($blocks) || !$blocks)
			return false;

		// Already marked -- a second adjacent marker buys nothing and risks another "nothing to cache".
		$last = end($blocks);

		if(is_array($last) && array_key_exists('cachePoint', $last))
			return false;

		foreach($blocks as $block) {
			if(is_array($block) && !array_key_exists('cachePoint', $block))
				return true;
		}

		return false;
	}

	/**
	 * Collect the model-specific request params into Converse's `additionalModelRequestFields`.
	 *
	 * Converse is a MULTI-VENDOR envelope: its top-level fields are only the ones every model family shares
	 * (`messages`, `system`, `toolConfig`, `inferenceConfig`). Anything family-specific goes in
	 * `additionalModelRequestFields`, which Bedrock forwards to the model verbatim -- so the shapes here are
	 * the SAME ones the native Anthropic Messages API takes (`thinking`, `output_config.effort`), nested one
	 * level down rather than translated. Documented at:
	 * https://docs.aws.amazon.com/bedrock/latest/userguide/claude-messages-adaptive-thinking.html
	 *
	 * ConverseStream takes the identical body, so streamed turns get this for free.
	 *
	 * OMITTED ENTIRELY when the author sets neither key. That silence is the model-family gate: `thinking`
	 * reaching a Nova/Kimi/DeepSeek model is a validation error rather than an ignored field, and this
	 * provider serves every vendor in the catalog. We don't classify the model to decide (ids are free-text
	 * and the catalog changes weekly) -- an author who writes `thinking:` under a non-Anthropic model gets
	 * Bedrock's own 400, which names the offending field.
	 */
	private function _applyAdditionalModelRequestFields(array &$body_payload) : void {
		$fields = [];

		$this->_applyThinking($fields);
		$this->_applyEffort($fields);

		if($fields)
			$body_payload['additionalModelRequestFields'] = $fields;
	}

	/**
	 * The grouped `thinking:` block -> Anthropic's native `thinking` shape (`type`/`display`).
	 *
	 * Mirrors Anthropic.php's method of the same name; the shapes are identical because Bedrock passes them
	 * through. Kept separate rather than shared: the two providers don't share an ancestor, and hoisting
	 * Anthropic-family semantics onto the vendor-neutral base class would be worse than this much repetition.
	 *
	 * Reasoning depth is NOT authored here -- it's the canonical top-level `effort:` key (see _applyEffort),
	 * which the legacy `enabled` budget also derives from. No `thinking:` block -> no change.
	 */
	private function _applyThinking(array &$fields) : void {
		$thinking = $this->getParam('thinking');

		if(!is_array($thinking) || !$thinking)
			return;

		$type = DevblocksPlatform::strLower(trim(strval($thinking['type'] ?? '')));
		$display = DevblocksPlatform::strLower(trim(strval($thinking['display'] ?? '')));

		if('enabled' === $type) {
			// Pre-4.6 models (Sonnet 4.5, Opus 4.5, and the default Haiku 4.5) don't support `adaptive` and
			// reject `output_config`; they need a literal token budget instead. It has to fit inside
			// inferenceConfig.maxTokens, which is the same `max_tokens` param chatCompletion reads.
			$budget = $this->_effortToBudget($this->getEffort() ?? '', intval($this->getParam('max_tokens', 2048)));

			if(!is_null($budget))
				$fields['thinking'] = ['type' => 'enabled', 'budget_tokens' => $budget];

			return;
		}

		if('disabled' === $type) {
			$fields['thinking'] = ['type' => 'disabled'];
		} elseif('adaptive' === $type) {
			$fields['thinking'] = ['type' => 'adaptive'];

			if('' !== $display)
				$fields['thinking']['display'] = $display;
		}
	}

	/**
	 * The canonical top-level `effort:` -> `output_config.effort`, passed verbatim.
	 *
	 * `effort` is its own sibling object inside additionalModelRequestFields, NOT a member of `thinking` --
	 * putting it there is a ValidationException. Skipped for legacy `thinking: {type: enabled}`, which
	 * rejects output_config outright and takes its depth from budget_tokens instead. No effort -> no change.
	 *
	 * The level isn't validated here: which of low|medium|high|xhigh|max a given model accepts varies by
	 * model and version (and disabling thinking caps it at `high`), so we let Bedrock be the authority.
	 */
	private function _applyEffort(array &$fields) : void {
		if(null === ($effort = $this->getEffort()))
			return;

		$thinking = $this->getParam('thinking');
		$type = is_array($thinking) ? DevblocksPlatform::strLower(trim(strval($thinking['type'] ?? ''))) : '';

		if('enabled' === $type)
			return;

		$fields['output_config'] = array_merge($fields['output_config'] ?? [], ['effort' => $effort]);
	}

	// Map an effort level -> a legacy `budget_tokens` value, clamped so it's >=1024 and < max_tokens. Returns
	// null when max_tokens can't fit a valid budget (skip legacy thinking rather than send a 400). Twin of
	// Anthropic.php::_effortToBudget -- keep the table in sync.
	private function _effortToBudget(string $effort, int $max_tokens) : ?int {
		$budget = [
			'low' => 4096,
			'medium' => 8192,
			'high' => 16384,
			'xhigh' => 24576,
			'max' => 32768,
		][$effort] ?? 8192;

		$ceiling = $max_tokens - 1;

		if($ceiling < 1024)
			return null;

		return max(1024, min($budget, $ceiling));
	}

	/**
	 * Bedrock has TWO hosts. `api_endpoint_url` points at the RUNTIME plane
	 * (`bedrock-runtime.<region>.amazonaws.com`), where every `invoke` goes -- but the model catalog lives on
	 * the CONTROL plane (`bedrock.<region>.amazonaws.com`). Listing against the runtime host is a 404, which
	 * is exactly what the inherited OpenAI-shaped `/v1/models` did before this override existed.
	 *
	 * SigV4 needs nothing: `deriveServiceRegionFromHost()` already scopes `bedrock-runtime` to the `bedrock`
	 * service, and the control-plane host parses to that same service and region.
	 *
	 * A host that isn't `bedrock-runtime` (a VPC endpoint, a proxy) is left alone -- the operator pointed us
	 * somewhere deliberate and we'd only be guessing at its layout.
	 */
	private function _getControlPlaneBaseUrl(string $base_url) : string {
		return preg_replace('#^(https?://)bedrock-runtime(-fips)?\.#i', '$1bedrock$2.', $base_url);
	}

	// ListFoundationModels. No query string on purpose: the SigV4 canonicalizer round-trips the query through
	// DevblocksPlatform::strParseQueryString(), which rewrites `.` and `[...]` keys -- so we take the whole
	// (~100 row) catalog and filter it below rather than risk a signature mismatch on a `by*` filter.
	function getChatModelsEndpointUrl(string $base_url) : string {
		return $this->_getControlPlaneBaseUrl($base_url) . '/foundation-models';
	}

	/**
	 * `{modelSummaries: [{modelId, modelName, providerName, inputModalities, outputModalities,
	 * inferenceTypesSupported, modelLifecycle:{status}}]}` -- nothing like OpenAI's `{data:[{id}]}`.
	 *
	 * Returns only the ON_DEMAND ids, which are the ones invocable as they stand. Everything else on Bedrock
	 * is reached through an inference profile, and those ids are merged in by fetchChatModels().
	 *
	 * Side effect by design: this fills `_model_meta` for EVERY text model it sees, not just the ones it
	 * returns. The inference-profile pass resolves its ARNs against that map, and getModelDefaults() reads
	 * the capabilities back out for the editor.
	 */
	protected function _parseChatModelsResponse(array $response_json) : array {
		$this->_model_meta = [];
		$models = [];

		$upper = fn($values) => array_map(fn($v) => DevblocksPlatform::strUpper(strval($v)), is_array($values) ? $values : []);

		foreach($response_json['modelSummaries'] ?? [] as $row) {
			if(!is_array($row))
				continue;

			if('' === ($model_id = strval($row['modelId'] ?? '')))
				continue;

			// A model that's been deprecated or withdrawn can't be invoked; absent means ACTIVE.
			if('ACTIVE' !== DevblocksPlatform::strUpper(strval($row['modelLifecycle']['status'] ?? 'ACTIVE')))
				continue;

			$input_modalities = $upper($row['inputModalities'] ?? []);
			$output_modalities = $upper($row['outputModalities'] ?? []);

			// Chat is text in, text out. This is what drops the embedding (EMBEDDING out), image, and video
			// models that share the catalog.
			if(!in_array('TEXT', $input_modalities, true) || !in_array('TEXT', $output_modalities, true))
				continue;

			// We speak the SYNCHRONOUS Converse call, so `converse.sync` is the predicate -- not the mere
			// presence of a `converse` block. They differ in practice: twelvelabs.pegasus advertises
			// `converse: {streaming:true, sync:false}` and answers `/converse` with "This action doesn't
			// support the model that you provided". Without this the picker offers models that can't run.
			if(!($row['inferenceAPIsSupported']['converse']['sync'] ?? true))
				continue;

			$this->_model_meta[$model_id] = [
				'vision' => in_array('IMAGE', $input_modalities, true),
				'description' => trim(sprintf('%s %s',
					strval($row['providerName'] ?? ''),
					strval($row['modelName'] ?? '')
				)),
			];

			$inference_types = $upper($row['inferenceTypesSupported'] ?? []);

			if(in_array('ON_DEMAND', $inference_types, true))
				$models[] = $model_id;
		}

		return $models;
	}

	/**
	 * ListInferenceProfiles -- the OTHER half of the catalog, and the half people actually want. Modern
	 * Claude and Nova models on Bedrock are INFERENCE_PROFILE-only: the bare `anthropic.claude-...` id from
	 * the catalog above 400s at invoke, and the id that works is the cross-region profile
	 * (`us.anthropic.claude-...`) that only this endpoint knows.
	 *
	 * `maxResults` is safe to sign (alphanumeric key and value). `nextToken` deliberately is NOT followed:
	 * it's a base64 blob whose `+/=` are exactly what the canonicalizer noted above would mangle, and no
	 * region is anywhere near 1000 profiles.
	 *
	 * @return string[] Invocable profile ids ([] on any failure -- see fetchChatModels)
	 */
	private function _fetchInferenceProfiles(?string &$error=null) : array {
		if(!($base_url = rtrim(strval($this->getParam('api_endpoint_url')), '/')))
			return [];

		$url = $this->_getControlPlaneBaseUrl($base_url) . '/inference-profiles?maxResults=1000';

		if(null === ($response_json = $this->_fetchModelsJson($url, [], $error)))
			return [];

		$profiles = [];

		foreach($response_json['inferenceProfileSummaries'] ?? [] as $row) {
			if(!is_array($row))
				continue;

			if('' === ($profile_id = strval($row['inferenceProfileId'] ?? '')))
				continue;

			if('ACTIVE' !== DevblocksPlatform::strUpper(strval($row['status'] ?? 'ACTIVE')))
				continue;

			// Resolve the profile to the foundation model it fronts: the ARN basename
			// (`arn:aws:bedrock:us-east-1::foundation-model/<modelId>`), else the geo-stripped profile id
			// (`us.anthropic.foo` -> `anthropic.foo`) for a profile that fronts another profile.
			$model_arn = strval($row['models'][0]['modelArn'] ?? '');
			$model_id = str_contains($model_arn, '/') ? substr($model_arn, strrpos($model_arn, '/') + 1) : '';

			if(!array_key_exists($model_id, $this->_model_meta))
				$model_id = preg_replace('#^[a-z0-9-]+\.(?=[a-z0-9-]+\.)#i', '', $profile_id);

			// Unknown to the catalog means it isn't a text model we kept (or isn't a model at all), so it has
			// no business in a chat picker.
			if(!array_key_exists($model_id, $this->_model_meta))
				continue;

			$this->_model_meta[$profile_id] = $this->_model_meta[$model_id];
			$profiles[] = $profile_id;
		}

		return $profiles;
	}

	/**
	 * The catalog is two calls, and only the first is load-bearing.
	 *
	 * The profile pass is ADDITIVE and its failure is swallowed on purpose: an IAM policy that grants
	 * `bedrock:ListFoundationModels` but not `bedrock:ListInferenceProfiles` should still get a usable list
	 * of on-demand models rather than an error where a list used to be.
	 */
	function fetchChatModels(?string &$error=null) : ?array {
		// The vendor catalog, via the base class (getChatModelsEndpointUrl + _parseChatModelsResponse above).
		// Fatal: without it we know nothing, including how to filter the profiles.
		if(null === ($models = parent::fetchChatModels($error)))
			return null;

		$profiles_error = null;

		if(($profiles = $this->_fetchInferenceProfiles($profiles_error)))
			$models = array_merge($models, $profiles);

		$models = array_values(array_unique($models));

		sort($models, SORT_NATURAL | SORT_FLAG_CASE);

		return $models;
	}

	// Live capabilities from the catalog, for the editor's fill-on-select. Bedrock reports no context window,
	// so that key is simply absent and the field keeps whatever's typed.
	function getModelDefaults(string $model) : array {
		return $this->_model_meta[$model] ?? [];
	}

	// Bedrock cache points have a fixed ~5 minute lifetime with no author knob, so this is a constant rather
	// than a reading of `cache_ttl`. (Caller gates on cache being on.)
	function getCacheHintSeconds(array $params) : ?int {
		return 300;
	}

	/**
	 * The regional runtime endpoints, us-east-1 first. Bedrock is the one provider where the endpoint is a
	 * routine per-install choice rather than an override, so the whole list is worth offering -- both here in
	 * the KATA editor and in the agent model editor's Endpoint URL field, which reads this same list out of
	 * the provider catalog.
	 */
	private function _getEndpointUrls() : array {
		$regions = [
			'us-east-1', 'us-east-2', 'us-west-2',
			'ca-central-1',
			'eu-central-1', 'eu-west-1', 'eu-west-2', 'eu-west-3', 'eu-north-1',
			'ap-northeast-1', 'ap-northeast-2', 'ap-northeast-3', 'ap-south-1', 'ap-southeast-1', 'ap-southeast-2',
			'sa-east-1',
		];

		return array_map(fn($region) => sprintf('https://bedrock-runtime.%s.amazonaws.com', $region), $regions);
	}

	function getChatModels() : array {
		return [
			'us.anthropic.claude-opus-4-8',
			'us.anthropic.claude-sonnet-5',
			'us.anthropic.claude-haiku-4-5-20251001-v1:0',
			'us.anthropic.claude-fable-5',
		];
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'cache@bool:', 'snippet' => 'cache@bool: yes', 'docHTML' => '<b>cache@bool:</b>Prompt caching. Defaults ON for <code>llm.agent</code> (multi-turn), OFF for <code>llm.chat</code> (one-shot). Bedrock cache points have a FIXED lifetime (~5 minutes), so there is no <code>cache_ttl</code> here. Only some models support caching; on the rest it is ignored.'],
				'max_tokens@int: 2048',
				['caption' => 'model:', 'snippet' => "# See: https://docs.aws.amazon.com/bedrock/latest/userguide/inference-profiles-support.html\nmodel:", 'score' => 2000],
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped. Models that do not support <code>ConverseStream</code> fall back to a single response automatically.'],
				['caption' => 'thinking:', 'snippet' => "thinking:\n\ttype: adaptive", 'docHTML' => '<b>thinking:</b>Extended thinking, for Anthropic models only &mdash; sending it to another vendor fails the request. <code>type</code>: adaptive|enabled|disabled &middot; <code>display</code>: summarized|omitted. Modern models use <code>adaptive</code>; older models (Haiku 4.5, Sonnet 4.5, Opus 4.5) use <code>enabled</code>. Reasoning depth is the top-level <code>effort:</code> key.'],
				['caption' => 'effort:', 'snippet' => "effort: high", 'docHTML' => '<b>effort:</b>Reasoning effort (empty = model default). Values: <code>low|medium|high|xhigh|max</code>, though which are accepted varies by model. On legacy <code>thinking: {type: enabled}</code> models it maps to a thinking budget instead.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => $this->_getEndpointUrls(),
				'thinking:' => ['type:', 'display:'],
				'thinking:type:' => ['adaptive', 'enabled', 'disabled'],
				'thinking:display:' => ['summarized', 'omitted'],
				'effort:' => ['low', 'medium', 'high', 'xhigh', 'max'],
			],
		];
	}

	function getEmbeddingModels() : array {
		return [
			'amazon.titan-embed-text-v2:0',
		];
	}

	function getEmbeddingKataAutocomplete() : array {
		return [
			'keys' => [
				'api_endpoint_url:',
				'authentication:',
				'dimensions:',
				'model:',
			],
			'values' => [
				'model:' => $this->getEmbeddingModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => $this->_getEndpointUrls(),
				'dimensions:' => ['256', '512', '1024'],
			],
		];
	}
}