<?php
namespace Cerb\LLM\Providers\Surfaces;

use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Exception_DevblocksLlmApiError;
use Extension_DevblocksLlmMemoryStore;
use Extension_DevblocksLlmProvider;
use GuzzleHttp\Psr7\Request;

/**
 * The `/v1/responses` dialect, as an internal SURFACE of the `openai` provider.
 *
 * Not a provider: it has no id, it is not in the registry, and nothing picks it from a menu. `OpenAI`
 * resolves a surface from the endpoint (see `OpenAI::getApiSurface()`) and delegates the dialect-bearing
 * half of the Chat/ChatStreaming contract here when that answer is `responses`. The chat dialect stays on
 * `OpenAI` itself, because the eight OpenAI-COMPATIBLE providers extend it and customize the chat path
 * through ~24 overrides (`getSystemPromptRole`, `getChatCompletionEndpointUrl`, `returnTool`, ...); moving
 * that half off the class they extend would orphan every one of them silently.
 *
 * Why the surface exists at all: `/v1/chat/completions` refuses function tools on a reasoning turn for
 * gpt-5.4+ and names this endpoint as the remedy, so `OpenAI::_applyToolReasoningGuardrail()` has to force
 * `reasoning_effort: none` there. There is no guardrail in this file, and that absence is the point.
 *
 * It still `extends Extension_DevblocksLlmProvider` -- that inheritance is what supplies the streaming
 * transport, auth, request timeouts, `expandMessageImages()` and `_normalizeToolParameters()`. It carries
 * NO catalog (model ids, model defaults, cache hints, the `/v1/models` filter): those are facts about
 * OpenAI the vendor, they are identical on both surfaces, and callers ask the PROVIDER for them.
 */
class Responses extends Extension_DevblocksLlmProvider implements ChatSurface {

	/**
	 * The types that are ITEMS in an `input` list, as opposed to the content PARTS that live inside a
	 * message item.
	 *
	 * The distinction is unforgiving. A stored envelope's `content` holds one or the other depending
	 * on which turn wrote it -- an assistant turn holds items straight off the wire, a user turn holds
	 * parts built by expandMessageImages() -- and splicing parts where items belong produces a 400 that
	 * names neither the message nor the reason.
	 */
	const INPUT_ITEM_TYPES = ['message', 'function_call', 'function_call_output', 'reasoning'];

	// Constructed by OpenAI from a params bag it has already validated, so there is no `model:` check here.
	function __construct(array $params, bool $validate=false) {
		parent::__construct($params);

		if(!$this->getParam('api_endpoint_url'))
			$this->setParam('api_endpoint_url', 'https://api.openai.com');
	}

	function getResponsesEndpointUrl(string $base_url) : string {
		return $base_url . '/v1/responses';
	}

	// ---------------------------------------------------------------------------------------------
	// Request
	// ---------------------------------------------------------------------------------------------

	/**
	 * @throws Exception_DevblocksAutomationError
	 * @throws Exception_DevblocksLlmApiError
	 */
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse {
		$http = DevblocksPlatform::services()->http();

		$base_url = rtrim($this->getParam('api_endpoint_url'), '/');
		$authentication_uri = $this->getParam('authentication', null);

		$verb = 'POST';
		$url = $this->getResponsesEndpointUrl($base_url);
		$headers = [
			'Content-Type' => 'application/json',
		];
		$streaming = $this->_isStreamingTurn();

		$body_payload = [
			'model' => $this->getParam('model', ''),
			'stream' => $streaming,
			'input' => $this->_toResponsesInput($this->sanitizeMessages($messages)),
			// CERB OWNS THE HISTORY. Leaving this at the API's default would have OpenAI retain every turn
			// server-side for 30 days, which is a data-residency decision no automation author made, and it
			// buys us nothing: we never send `previous_response_id`, because the session tree (branches,
			// compaction, cross-provider forks) is not something a linear server-side chain can represent.
			'store' => false,
		];

		// The system prompt is a first-class request field here, not a message with a special role -- which
		// also sidesteps the `system` vs `developer` role split that the chat surface has to care about.
		if($system_prompt)
			$body_payload['instructions'] = $system_prompt;

		if($tools)
			$body_payload['tools'] = $this->_toResponsesTools($tools);

		if(($reasoning = $this->_getReasoningParams()))
			$body_payload = array_merge($body_payload, $reasoning);

		// The author's escape hatch gets the LAST word. RECURSIVE, not shallow: an author routinely sets
		// several keys inside one container over more than one line, and a container this replaces wholesale
		// would silently drop whatever else was in it.
		if(($extra_body = $this->_getExtraBodyParams()))
			$body_payload = array_replace_recursive($body_payload, $extra_body);

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
			// No response at all (connect refused, DNS, cURL timeout) -> status 0, a transient/retryable class.
			if(false === ($response = $http->sendRequest($request, $request_options, $error)))
				throw new Exception_DevblocksLlmApiError($error, 0);

			if(false === ($response_json = $http->getResponseAsJson($response, $error)))
				throw new Exception_DevblocksAutomationError($error);

			// A non-2xx carries the HTTP status so the caller classifies retry-vs-surface (429/503/5xx vs 401/400),
			// plus the provider's own Retry-After when it sent one.
			if(200 != $response->getStatusCode()) {
				$status_code = $response->getStatusCode();

				throw new Exception_DevblocksLlmApiError(
					$this->_getApiErrorMessage($response_json, $status_code),
					$status_code,
					$this->_getRetryAfterSecs($response)
				);
			}
		}

		// A `status: failed` arrives inside a 200 -- the request was accepted and the RESPONSE failed. Without
		// this it reads as a well-formed turn that said nothing.
		if('failed' === ($response_json['status'] ?? '')) {
			$message = strval($response_json['error']['message'] ?? 'The response failed.');

			throw new Exception_DevblocksLlmApiError($message, static::_streamErrorStatus(strval($response_json['error']['code'] ?? '')));
		}

		$output = is_array($response_json['output'] ?? null) ? $response_json['output'] : [];
		$usage = $this->_mapUsage($response_json['usage'] ?? []);
		$finish_reason = $this->_deriveFinishReason($response_json);

		// ONE row per turn, holding the whole `output` list -- the Anthropic shape, not the chat shape.
		//
		// The alternative (a row per item) was rejected because the storage layer denormalizes `role` off the
		// message and Compaction::_userLed() trims on it: a bare `function_call` item has no role at all, so a
		// turn that produced text plus two tool calls would land as one roled row and two roleless ones, and a
		// compaction boundary could legally cut between them -- stranding results whose calls it dropped.
		$assistant_message = [
			'role' => 'assistant',
			'content' => $output,
		];

		// Add to the memory (usage rides the assistant turn -- usage_json column, not the replayed data_json)
		if($output)
			$memory->appendMessage($assistant_message, usage: $usage, finish_reason: $finish_reason);

		$response = $this->convertToGenericMessage($assistant_message);
		$response->setUsage($usage);
		$response->setFinishReason($finish_reason);

		return $response;
	}

	/**
	 * Envelope list -> a flat `input` item list.
	 *
	 * NORMALIZE, NEVER ASSUME. Four framework paths synthesize bare `{role, content: <string>}` messages that
	 * never came from a provider (Compaction's boundary exchange, summarizeSessionWindow, summarizeMessages,
	 * testConnection), and the base `toNativeMessage()` emits the OpenAI CHAT shape for cross-provider replay
	 * into any provider that doesn't override it. So this reads three dialects: Responses items, OpenAI chat
	 * messages, and a plain string.
	 *
	 * A session's OWN pre-switch rows are not among them: `getActivePath()` stops its recursive walk at a
	 * `kind = 'summary'` row, and a capability-signature change plants exactly that. The boundary is a real
	 * dialect firewall for REPLAY. It is not one for RENDER -- the transcript reads every row, pre-boundary
	 * included, through whatever provider the session holds now -- which is why convertToGenericMessage()
	 * tolerates the chat shape and this method needn't.
	 */
	protected function _toResponsesInput(array $messages) : array {
		$input = [];

		foreach($messages as $message) {
			if(!is_array($message))
				continue;

			foreach($this->_messageToItems($message) as $item)
				$input[] = $item;
		}

		return $input;
	}

	/**
	 * One stored/synthesized message -> zero or more `input` items.
	 */
	private function _messageToItems(array $message) : array {
		$role = strval($message['role'] ?? 'user');
		$content = $message['content'] ?? '';

		// --- Dialect 1: OpenAI chat. A cross-provider replay through the base toNativeMessage(), which emits
		// the chat shape for every provider that doesn't override it.
		if('tool' === $role && array_key_exists('tool_call_id', $message)) {
			return [$this->_functionCallOutputItem(strval($message['tool_call_id']), $content)];
		}

		if(!empty($message['tool_calls'])) {
			$items = [];

			if(is_string($content) && '' !== trim($content))
				$items[] = $this->_messageItem('assistant', $content);

			foreach($message['tool_calls'] as $tool_call) {
				if(!is_array($tool_call) || !($tool_call['function']['name'] ?? null))
					continue;

				$items[] = [
					'type' => 'function_call',
					'call_id' => strval($tool_call['id'] ?? ''),
					'name' => strval($tool_call['function']['name']),
					// `arguments` is a JSON STRING on both surfaces, so it rides across untouched.
					'arguments' => strval($tool_call['function']['arguments'] ?? ''),
				];
			}

			return $items;
		}

		// --- Dialect 2: a plain string (the framework's synthesized turns).
		if(!is_array($content))
			return ('' !== trim(strval($content))) ? [$this->_messageItem($role, strval($content))] : [];

		if(!$content)
			return [];

		// --- Dialect 3: native. `content` holds either ITEMS (an assistant turn, or a tool result) or content
		// PARTS (a user turn after expandMessageImages). One list is never a mix of both, so the first typed
		// entry settles it.
		$is_items = false;

		foreach($content as $entry) {
			if(!is_array($entry) || !array_key_exists('type', $entry))
				continue;

			$is_items = in_array($entry['type'], self::INPUT_ITEM_TYPES, true);
			break;
		}

		if(!$is_items)
			return [$this->_messageItem($role, $content)];

		$items = [];

		foreach($content as $item) {
			if(!is_array($item) || !($type = strval($item['type'] ?? '')))
				continue;

			// REASONING ITEMS CANNOT BE REPLAYED HERE. With `store: false` and no
			// `include: ["reasoning.encrypted_content"]`, the item we got back carries no state the API will
			// accept on a later turn, and sending one is a 400 ("Item ... not found"). They are STORED (their
			// summaries are the only reasoning text this endpoint ever hands back, and the transcript shows
			// them) and dropped on the way out. Reasoning continuity across turns is a separate feature; see
			// the plan.
			if('reasoning' === $type && !array_key_exists('encrypted_content', $item))
				continue;

			// `status` is a RESPONSE-side annotation. It round-trips harmlessly on most items but is rejected
			// on some, and it says nothing we need.
			unset($item['id'], $item['status']);

			$items[] = $item;
		}

		return $items;
	}

	// A `message` item. Roles other than `assistant` take `input_text` parts and `assistant` takes
	// `output_text` -- the API rejects the pairing the other way round.
	private function _messageItem(string $role, array|string $content) : array {
		$is_output = ('assistant' === $role);
		$parts = [];

		foreach((is_array($content) ? $content : [['type' => 'text', 'text' => $content]]) as $part) {
			if(is_string($part)) {
				$parts[] = ['type' => $is_output ? 'output_text' : 'input_text', 'text' => $part];
				continue;
			}

			if(!is_array($part))
				continue;

			$type = strval($part['type'] ?? '');

			// An image on an assistant turn has no representation here; it can only ever be input.
			if(in_array($type, ['input_image', 'image_url'], true)) {
				if(!$is_output)
					$parts[] = $this->_nativeImagePartFrom($part);

				continue;
			}

			$text = strval($part['text'] ?? $part['content'] ?? '');

			if('' !== $text)
				$parts[] = ['type' => $is_output ? 'output_text' : 'input_text', 'text' => $text];
		}

		return [
			'type' => 'message',
			// KEPT DELIBERATELY, and never collapsed to the bare `{role, content}` shorthand the API also
			// accepts: DAO_LlmAgentMessage::create() denormalizes `role` out of the stored message and
			// Compaction::_userLed() reads that column, so a roleless item is invisible to compaction.
			'role' => $role,
			'content' => $parts,
		];
	}

	// A `function_call_output` item. `output` is a STRING on the wire; a tool that returned structure gets
	// encoded rather than nested, because the API rejects a non-string here.
	private function _functionCallOutputItem(string $call_id, mixed $output) : array {
		return [
			'type' => 'function_call_output',
			'call_id' => $call_id,
			'output' => is_string($output) ? $output : strval(json_encode($output)),
		];
	}

	// Normalize either dialect's image part into the Responses one, so a chat-shaped stored message replays.
	private function _nativeImagePartFrom(array $part) : array {
		$url = is_array($part['image_url'] ?? null)
			? strval($part['image_url']['url'] ?? '')
			: strval($part['image_url'] ?? '');

		return ['type' => 'input_image', 'image_url' => $url];
	}

	/**
	 * Chat's NESTED tool schema -> Responses' FLAT one.
	 *
	 * getSessionToolSchemas() speaks the chat dialect for every provider (Anthropic reshapes it too), so the
	 * name/description/parameters arrive one level down under `function`. Here they sit on the tool itself.
	 * `strict` is deliberately not set: it defaults off, and turning it on would require every authored tool
	 * schema to carry `additionalProperties: false`, which none of them do.
	 */
	protected function _toResponsesTools(array $tools) : array {
		$out = [];

		foreach($tools as $tool) {
			if(!is_array($tool))
				continue;

			// Already flat (an author who hand-wrote one, or a future caller).
			$fn = is_array($tool['function'] ?? null) ? $tool['function'] : $tool;

			if(!($name = strval($fn['name'] ?? '')))
				continue;

			$out[] = [
				'type' => 'function',
				'name' => $name,
				'description' => strval($fn['description'] ?? ''),
				'parameters' => $fn['parameters'] ?? ['type' => 'object', 'properties' => (object) []],
			];
		}

		return $out;
	}

	/**
	 * Canonical `effort:` -> Responses' NESTED `reasoning: {effort}`, plus the grouped `thinking:` block's
	 * summary request.
	 *
	 * The nesting is the whole divergence from the chat surface's top-level `reasoning_effort`, and it is
	 * why getEffectiveEffort() below can't be inherited: the level lives at a different path.
	 */
	protected function _getReasoningParams() : array {
		$reasoning = [];

		if(($effort = $this->getEffort()))
			$reasoning['effort'] = $effort;

		if(($summary = $this->_getThinkingSummary()))
			$reasoning['summary'] = $summary;

		return $reasoning ? ['reasoning' => $reasoning] : [];
	}

	/**
	 * The grouped `thinking: display:` block -> Responses' `reasoning.summary`.
	 *
	 * Authored as `thinking: display:` rather than a top-level key so it reads the same as Anthropic's, which
	 * is the only other provider that can show its reasoning. Same block, same key, same neutral vocabulary
	 * (`summarized`/`omitted`), so an author moving a model between the two doesn't have to relearn it.
	 * OpenAI's own finer levels (`concise`, `detailed`) pass through verbatim for anyone who wants them --
	 * the API validates the value, and a level a model doesn't take is its 400 to raise, not ours.
	 *
	 * DEFAULTS ON, because the alternative is a silent misfeature. Effort alone buys reasoning TOKENS: the
	 * model thinks, the usage vector records it, and the turn comes back with `summary: []` and nothing to
	 * read -- so a `max`-effort session shows an empty Thinking section and looks broken. Anthropic already
	 * returns its thinking without being asked, so defaulting off here made the two providers disagree for no
	 * reason an author could see.
	 *
	 * `auto`, not `detailed`: OpenAI documents `auto` as the most detailed summary THAT MODEL supports, so it
	 * degrades instead of 400ing on the models that don't offer `detailed`. Asking for the maximum by name is
	 * an opt-in, not a default.
	 *
	 * Gated on `has_thinking` -- the record's own curated flag -- because a `reasoning` block sent to a model
	 * that doesn't reason is a 400. That flag is the only thing that knows: model ids are free text here, and
	 * getModelDefaults() deliberately declines to guess for an id it can't place.
	 */
	private function _getThinkingSummary() : ?string {
		$thinking = $this->getParam('thinking');
		$display = is_array($thinking) ? DevblocksPlatform::strLower(trim(strval($thinking['display'] ?? ''))) : '';

		if('' === $display)
			return $this->_hasThinking() ? 'auto' : null;

		return match($display) {
			// Omitting the key IS how the API spells "no summary".
			'omitted', 'none' => null,
			'summarized' => 'auto',
			default => $display,
		};
	}

	// Does this record say its model reasons? `has_thinking` is set per agent_model record; an explicit
	// `effort:` is the fallback tell for a params bag assembled without one.
	private function _hasThinking() : bool {
		if(null !== ($v = $this->getParam('has_thinking')))
			return DevblocksPlatform::services()->string()->toBool($v);

		return null !== $this->getEffort();
	}

	/**
	 * The level that will actually ship.
	 *
	 * NO GUARDRAIL -- and that is the headline, not an omission. On /v1/chat/completions a tool-using turn on
	 * a gpt-5.4+ model has its level forced to `none`, so the transcript's Thinking chip reports "overridden
	 * by tools" for exactly the sessions where reasoning matters most. Here tools and reasoning coexist, so
	 * `$has_tools` changes nothing and the author's level is what runs.
	 *
	 * The extra_body merge stays, for the same reason it exists on the chat provider: a level overridden
	 * through the escape hatch has to be reported as what shipped, or the chip quotes `effort:` while the
	 * wire carried something else.
	 */
	function getEffectiveEffort(bool $has_tools) : ?string {
		$params = array_replace_recursive($this->_getReasoningParams(), $this->_getExtraBodyParams());

		return $params['reasoning']['effort'] ?? null;
	}

	/**
	 * Author-supplied request-body keys, merged verbatim into the top level -- the same meaning the OpenAI
	 * SDK's `extra_body` has. Cerb keeps the keys that carry the request's STRUCTURE.
	 *
	 * Identical to OpenAI::_getExtraBodyParams() apart from the reserved list, which differs because the
	 * surfaces name their fields differently: `input` and `instructions` here, `messages` there.
	 */
	protected function _getExtraBodyParams() : array {
		$extra = $this->getParam('extra_body');

		if(!is_array($extra) || !$extra)
			return [];

		return array_diff_key($extra, array_flip(['model', 'input', 'instructions', 'stream', 'tools', 'store']));
	}

	// Neutral token usage. `input_tokens` INCLUDES cached here (as chat's prompt_tokens does), so fresh input
	// is the difference. `reasoning` is a BREAKDOWN of `output`, never an addition -- see DAO_LlmAgentMessage.
	// OpenAI reports no cache writes on either surface.
	protected function _mapUsage(mixed $native_usage) : array {
		$native_usage = is_array($native_usage) ? $native_usage : [];
		$cached = intval($native_usage['input_tokens_details']['cached_tokens'] ?? 0);

		return [
			'input' => max(0, intval($native_usage['input_tokens'] ?? 0) - $cached),
			'output' => intval($native_usage['output_tokens'] ?? 0),
			'reasoning' => intval($native_usage['output_tokens_details']['reasoning_tokens'] ?? 0),
			'cache_read' => $cached,
			'cache_write' => 0,
		];
	}

	/**
	 * Why generation stopped. There is no `finish_reason` on this surface: the fact is split across `status`
	 * and `incomplete_details.reason`, and "the model chose to call a tool" isn't reported at all -- it has to
	 * be read off the output items.
	 */
	protected function _deriveFinishReason(array $response_json) : string {
		$status = strval($response_json['status'] ?? '');

		if('incomplete' === $status) {
			$reason = strval($response_json['incomplete_details']['reason'] ?? '');

			// normalizeFinishReason() knows the chat vocabulary; these two spellings are this surface's own.
			return self::normalizeFinishReason(match($reason) {
				'max_output_tokens' => 'length',
				'content_filter' => 'filter',
				default => $reason ?: 'length',
			});
		}

		if('completed' !== $status && '' !== $status)
			return self::normalizeFinishReason($status);

		foreach((is_array($response_json['output'] ?? null) ? $response_json['output'] : []) as $item) {
			if(is_array($item) && 'function_call' === ($item['type'] ?? ''))
				return 'tool_calls';
		}

		return 'stop';
	}

	// ---------------------------------------------------------------------------------------------
	// Neutral conversion
	// ---------------------------------------------------------------------------------------------

	public function convertToGenericMessage(array $message, ?string $message_uuid=null): DevblocksLlmChatResponse {
		$chat_response = new DevblocksLlmChatResponse('', $message_uuid);

		if(array_key_exists('role', $message))
			$chat_response->setRole(strval($message['role']));

		$content = $message['content'] ?? null;

		// A framework-synthesized turn (a compaction boundary, a summary, testConnection) is a plain string.
		if(is_string($content)) {
			if('' !== $content)
				$chat_response->pushMessage($content);

		// A chat-shaped row from before a surface switch. Handled here rather than being left to fatal,
		// because every caller of this is a READ over already-persisted history -- the transcript viewer
		// included -- where an unrecognized shape costs the whole conversation, not one message.
		} elseif('tool' === ($message['role'] ?? '') && array_key_exists('tool_call_id', $message)) {
			$chat_response->pushToolResult(strval($message['tool_call_id']), $content ?? '');

		} elseif(is_array($content)) {
			foreach($content as $item) {
				if(!is_array($item))
					continue;

				switch($item['type'] ?? '') {
					case 'message':
						foreach(($item['content'] ?? []) as $part) {
							if(is_string($part)) {
								$chat_response->pushMessage($part);
							} elseif(is_array($part) && '' !== ($text = strval($part['text'] ?? ''))) {
								$chat_response->pushMessage($text);
							}
						}
						break;

					case 'function_call':
						$chat_response->pushTool(new DevblocksLlmChatResponse_Tool(
							strval($item['name'] ?? ''),
							$this->_normalizeToolParameters($item['arguments'] ?? []),
							strval($item['call_id'] ?? ''),
						));
						break;

					case 'function_call_output':
						$chat_response->setRole('tool');
						$chat_response->pushToolResult(strval($item['call_id'] ?? ''), $item['output'] ?? '');
						break;

					case 'reasoning':
						// TWO slots, because the API populates either depending on model and request:
						// `summary[]` holds `summary_text` parts, `content[]` holds `reasoning_text` parts.
						// Reading only one leaves the Thinking section mysteriously empty on the models that
						// use the other. Both are empty unless `thinking: display:` asked for a summary --
						// that is the normal case, and it contributes nothing rather than failing.
						foreach(['summary', 'content'] as $key) {
							foreach(($item[$key] ?? []) as $part) {
								$text = is_string($part) ? $part : strval($part['text'] ?? '');

								if('' !== $text)
									$chat_response->pushThinking($text);
							}
						}
						break;

					default:
						// A content PART rather than an item -- a user turn after expandMessageImages(), and
						// anything OpenAI adds later. Text is worth showing; the rest isn't ours to guess at.
						if('' !== ($text = strval($item['text'] ?? '')))
							$chat_response->pushMessage($text);
						break;
				}
			}
		}

		// Surface any neutral `images:` (resource uris) for the transcript viewer.
		$this->_pushMessageImages($message, $chat_response);

		return $chat_response;
	}

	/**
	 * Reverse of convertToGenericMessage(), for cross-provider replay INTO this provider (forkSession, and a
	 * capability-signature switch).
	 *
	 * Overridden because the base emits the OpenAI CHAT shape for everyone. _toResponsesInput() would read
	 * that fine on the wire -- but this output is what gets PERSISTED, and storing one session's history in
	 * two dialects makes every reader downstream carry the fork forever.
	 *
	 * @return array List of provider-native message arrays.
	 */
	function toNativeMessage(DevblocksLlmChatResponse $message) : array {
		$tool_results = $message->getToolResults();

		// Each tool result becomes its own envelope, matching returnTool().
		if($tool_results) {
			$out = [];

			foreach($tool_results as $tool_id => $content)
				$out[] = ['role' => 'tool', 'content' => [$this->_functionCallOutputItem(strval($tool_id), $content)]];

			return $out;
		}

		$text = '';
		foreach($message->getMessages() as $block)
			$text .= ($block['content'] ?? '');

		$role = $message->getRole() ?: 'user';
		$items = [];

		if('' !== $text)
			$items[] = $this->_messageItem($role, $text);

		foreach($message->getToolCalls() as $tool) {
			$items[] = [
				'type' => 'function_call',
				'call_id' => $tool->getId(),
				'name' => $tool->getName(),
				// `arguments` is a JSON STRING, and PHP encodes an empty array as `[]` -- so a no-argument
				// call (the common case) would replay as the wrong JSON type without the object cast.
				'arguments' => json_encode($tool->getParameters() ?: (object) []),
			];
		}

		if(!$items)
			return [];

		return [['role' => $role, 'content' => $items]];
	}

	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory): void {
		// Wrapped in a roled envelope rather than appended as a bare item: `role: 'tool'` is what
		// DAO_LlmAgentMessage::_classifyKind() reads to mark the row `tool_result`, and what
		// Compaction::_userLed() reads to drop an orphaned result off the front of a re-appended tail.
		$memory->appendMessage([
			'role' => 'tool',
			'content' => [$this->_functionCallOutputItem($tool->getId(), $content)],
		]);
	}

	/**
	 * Prune the front of the replayed history until it can legally lead a request, then expand images.
	 *
	 * The rule is NOT chat's "the first message must be role:user" -- this surface has no alternation
	 * requirement at all. What it does reject is an orphan: a `function_call_output` whose `function_call` was
	 * folded away by compaction is a 400 ("No tool call found for function call output"), and a `function_call`
	 * with no answer after it strands the turn.
	 */
	function sanitizeMessages(array $messages) : array {
		while(!empty($messages)) {
			$key = array_key_first($messages);
			$message = $messages[$key];
			$role = $message['role'] ?? '';

			// A tool result leading the window has lost its call.
			if('tool' === $role) {
				unset($messages[$key]);
				continue;
			}

			// An assistant turn leading the window may hold calls whose results were trimmed off behind it.
			if('assistant' === $role) {
				unset($messages[$key]);
				continue;
			}

			break;
		}

		// Expand any neutral `images:` into native `input_image` parts (images before text).
		return array_map(fn($m) => $this->expandMessageImages($m), array_values($messages));
	}

	// Responses takes a bare data-uri STRING at `image_url`, where chat takes an `{url}` object.
	protected function _nativeImagePart(string $mime_type, string $data) : ?array {
		if('' === $mime_type || '' === $data)
			return null;

		return [
			'type' => 'input_image',
			'image_url' => 'data:' . $mime_type . ';base64,' . $data,
		];
	}

	// `text` is `input_text` here. expandMessageImages() builds a user turn's parts from these two, so both
	// have to speak the input dialect or the whole message is rejected.
	protected function _nativeTextPart(string $text) : array {
		return ['type' => 'input_text', 'text' => $text];
	}

	// ---------------------------------------------------------------------------------------------
	// Streaming
	// ---------------------------------------------------------------------------------------------

	/**
	 * The Responses SSE grammar.
	 *
	 * THE OPPOSITE OF THE CHAT ACCUMULATOR IN EVERY STRUCTURAL RESPECT, which is why the two cannot share
	 * one implementation:
	 *   - Frames are NAMED (`event: response.output_text.delta`), so dispatch is on the event, not the
	 *     payload. Chat's frames carry no event name at all.
	 *   - Items are addressed by `output_index`, not by a `delta.tool_calls[].index`.
	 *   - Every item is closed by `response.output_item.done` carrying the COMPLETE item, and the stream ends
	 *     with `response.completed` carrying the ENTIRE final response -- usage, status and all. Chat has no
	 *     close event, which is why its tool calls can only be judged by whether their arguments parse.
	 *
	 * So `assemble()` prefers the terminal frame verbatim and only rebuilds from deltas when the stream ended
	 * without one. Deltas are still accumulated on every frame, because that is what `snapshot()` publishes to
	 * the live transcript and what survives an abort.
	 */
	protected function _streamAccumulator() : array {
		$items = [];
		$final = null;
		$api_error = null;

		// output_index -> item, with content parts and summary parts held in their own index-keyed slots until
		// they are materialized. Ordering by the index the API assigned rather than by arrival, because deltas
		// for two items legitimately interleave.
		$materialize = function() use (&$items) : array {
			ksort($items);

			return array_values(array_map(function($item) {
				foreach(['content', 'summary'] as $key) {
					if(is_array($item[$key] ?? null)) {
						ksort($item[$key]);
						$item[$key] = array_values($item[$key]);
					}
				}

				return $item;
			}, $items));
		};

		$on_event = function(string $event, string $data) use (&$items, &$final, &$api_error) : void {
			$data = trim($data);

			if('' === $data || !is_array($d = json_decode($data, true)))
				return;

			switch($event) {
				case 'response.output_item.added':
					$idx = intval($d['output_index'] ?? 0);
					$items[$idx] = is_array($d['item'] ?? null) ? $d['item'] : [];
					break;

				case 'response.output_text.delta':
					$idx = intval($d['output_index'] ?? 0);
					$ci = intval($d['content_index'] ?? 0);

					if(!isset($items[$idx]['content'][$ci]))
						$items[$idx]['content'][$ci] = ['type' => 'output_text', 'text' => ''];

					$items[$idx]['content'][$ci]['text'] .= strval($d['delta'] ?? '');
					break;

				case 'response.function_call_arguments.delta':
					$idx = intval($d['output_index'] ?? 0);
					$items[$idx]['arguments'] = strval($items[$idx]['arguments'] ?? '') . strval($d['delta'] ?? '');
					break;

				case 'response.reasoning_summary_text.delta':
					$idx = intval($d['output_index'] ?? 0);
					$si = intval($d['summary_index'] ?? 0);

					if(!isset($items[$idx]['summary'][$si]))
						$items[$idx]['summary'][$si] = ['type' => 'summary_text', 'text' => ''];

					$items[$idx]['summary'][$si]['text'] .= strval($d['delta'] ?? '');
					break;

				case 'response.output_item.done':
					// AUTHORITATIVE. Replaces whatever the deltas built, so a fragment we reassembled slightly
					// differently (or missed) can't survive into the persisted turn.
					if(is_array($d['item'] ?? null))
						$items[intval($d['output_index'] ?? 0)] = $d['item'];
					break;

				case 'response.completed':
				case 'response.incomplete':
					$final = is_array($d['response'] ?? null) ? $d['response'] : null;
					break;

				case 'response.failed':
					$final = is_array($d['response'] ?? null) ? $d['response'] : null;
					$error = $final['error'] ?? null;
					$api_error = [
						'type' => strval($error['code'] ?? ''),
						'message' => strval($error['message'] ?? 'The response failed.'),
					];
					break;

				case 'error':
					// An API error can arrive INSIDE a 200. Record it and let the stream end; the base throws
					// once the transfer closes, so this doesn't read as our own abort.
					$api_error = [
						'type' => strval($d['code'] ?? $d['type'] ?? ''),
						'message' => strval($d['message'] ?? 'Unknown streaming error.'),
					];
					break;

				default:
					// response.created, .in_progress, .content_part.*, .output_text.done and everything OpenAI
					// adds later. Ignored rather than failed -- the done/completed frames carry the truth.
					break;
			}
		};

		// The turn so far, in the shape that gets PERSISTED (`content`), which is not the shape the blocking
		// path parses (`output`). Anthropic has the same split for the same reason.
		$snapshot = function() use ($materialize) : array {
			return ['role' => 'assistant', 'content' => $materialize()];
		};

		// NB: `use (&$x)`, not `fn() => $x`. An arrow function captures BY VALUE at creation, which here is
		// before a single event has landed -- every one of these would report the empty initial state.
		return [
			'on_event' => $on_event,
			'snapshot' => $snapshot,
			'usage' => function() use (&$final) : array {
				return is_array($final['usage'] ?? null) ? $final['usage'] : [];
			},
			'error' => function() use (&$api_error) : ?array {
				return $api_error;
			},
			'assemble' => function() use ($materialize, &$final) : array {
				// The terminal frame IS the non-streamed body, so preferring it keeps one parser for both
				// paths rather than two that can drift.
				if(is_array($final) && is_array($final['output'] ?? null))
					return $final;

				// No terminal frame: the stream ended early. Rebuild what we have and leave `status` empty --
				// an unreported finish reason, which is honest, rather than claiming `completed`.
				return [
					'output' => $materialize(),
					'usage' => is_array($final['usage'] ?? null) ? $final['usage'] : [],
					'status' => '',
				];
			},
		];
	}

	// An ordinary Responses body -- what the blocking path parses. Used by the base's `$saw_event` fallback
	// when we asked for a stream and got a normal body back (a proxy that buffers, a gateway that ignores
	// `stream`). Testing `output` rather than chat's `choices[0].message` is the whole difference, and getting
	// it wrong is silent: the fallback never fires and the turn assembles empty.
	protected function _parseNonStreamedBody(array $body) : ?array {
		return isset($body['output']) ? $body : null;
	}

	/**
	 * Map an in-stream error's code back onto the HTTP status it would have carried in a non-streamed
	 * response, so the caller's retryable-vs-terminal classification keeps working even though the transport
	 * returned 200. Unknown codes stay retryable.
	 */
	protected static function _streamErrorStatus(string $type) : int {
		return match($type) {
			'invalid_request_error', 'invalid_prompt' => 400,
			'authentication_error', 'invalid_api_key' => 401,
			'permission_error', 'insufficient_quota' => 403,
			'not_found_error' => 404,
			'rate_limit_error', 'rate_limit_exceeded', 'requests' => 429,
			'server_error' => 500,
			'overloaded_error' => 529,
			// Unknown types included: treat as a server-side fault so a transient novelty stays retryable.
			default => 500,
		};
	}

	/**
	 * Structural salvage rules for a turn cut short. Purely structural by contract -- a worker killed
	 * mid-stream is recovered on the session's NEXT turn by a different process that never saw the stream.
	 */
	public function sanitizePartialContent(array $message) : array {
		$kept = [];

		foreach(($message['content'] ?? []) as $item) {
			if(!is_array($item))
				continue;

			switch($item['type'] ?? '') {
				case 'message':
					// A lone empty message item would make the turn look answered.
					$text = '';

					foreach(($item['content'] ?? []) as $part)
						$text .= is_array($part) ? strval($part['text'] ?? '') : strval($part);

					if('' !== trim($text))
						$kept[] = $item;
					break;

				case 'function_call':
					// Same tell as the chat surface: there is no per-call close event we can rely on having
					// seen, so the only structural evidence that arguments were severed mid-token is that they
					// don't parse. A legitimate no-argument call sends '' or '{}'. Nothing can rescue truncated
					// JSON, and a call whose arguments we can't read can't be answered OR executed.
					if('' === trim(strval($item['call_id'] ?? '')) || '' === trim(strval($item['name'] ?? '')))
						break;

					$arguments = trim(strval($item['arguments'] ?? ''));

					if('' === $arguments || is_array(json_decode($arguments, true)))
						$kept[] = $item;
					break;

				case 'reasoning':
					// Dropped from a partial outright. It is never replayed (see _messageToItems), and half a
					// summary is worse than none in a transcript that will show it as the turn's thinking.
					break;

				default:
					$kept[] = $item;
					break;
			}
		}

		$message['content'] = $kept;

		return $message;
	}
}
