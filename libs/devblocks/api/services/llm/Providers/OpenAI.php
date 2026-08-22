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

class OpenAI extends Extension_DevblocksLlmProvider implements Chat, ChatStreaming, Embedding {
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
			
			if ($message['content'] ?? null) {
				$content = $message['content'];

				// Cross-provider replay: an Anthropic-stored message keeps `content` as an array of blocks. Flatten
				// to text (a provider switch degrades to text — image/tool_use blocks drop) so the OpenAI converter
				// and the transcript viewer don't choke on a non-string (a fatal TypeError before this).
				if(is_array($content)) {
					$text = '';

					foreach($content as $block) {
						if(is_string($block))
							$text .= $block;
						elseif(is_array($block))
							$text .= strval($block['text'] ?? $block['content'] ?? '');
					}

					$content = $text;
				}

				if('' !== $content)
					$chat_response->pushMessage($content);
			}
			
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
	
	/**
	 * Provider-specific body knobs. Reasoning is NOT among them -- see _getReasoningParams().
	 *
	 * Keeping the two apart is what makes effort survive a subclass. This method is the grab-bag every
	 * OpenAI-compatible provider overrides to change ITS knobs, and while effort was mixed in here, three of
	 * them (Groq, Together, HuggingFace) silently dropped the author's level as collateral damage of an
	 * override that was only ever about something else.
	 */
	function getChatCompletionsParams() : array {
		return [];
	}

	/**
	 * Author-supplied request-body keys, merged verbatim into the top level.
	 *
	 * Named and shaped after the OpenAI SDK's `extra_body`, deliberately: an OpenAI-compatible server
	 * documents its extensions as `extra_body={...}` snippets, and an author who can transcribe one
	 * straight into KATA gets it right. Like the SDK, the CONTENTS merge into the request body -- so
	 * `extra_body: chat_template_kwargs: reasoning_effort: medium` puts a top-level `chat_template_kwargs`
	 * on the wire, not a key called `extra_body`. (Gemini's getChatCompletionsParams() sends a literal
	 * `extra_body` key because Google's compat layer really does accept one; that's their wire, not ours.)
	 *
	 * This exists because the OpenAI-compatible ecosystem puts its knobs somewhere other than where the
	 * OpenAI spec does, and a fixed set of typed params can't keep up. The case that forced it: Apple-silicon
	 * oMLX serving Qwen3 forwards `chat_template_kwargs` to the Jinja template but does NOT map the standard
	 * top-level `reasoning_effort` into it -- so `effort:` reaches the server, is dropped on the floor, and
	 * NOTHING says so. Measured on a live endpoint: an invalid level returned 200 and no level was
	 * distinguishable from sending none. llama.cpp, vLLM, SGLang and unsloth all have their own such keys.
	 */
	protected function _getExtraBodyParams() : array {
		$extra = $this->getParam('extra_body');

		if(!is_array($extra) || !$extra)
			return [];

		// Cerb owns the request's STRUCTURE -- the conversation, the model, the tool schemas, and whether the
		// turn streams are all resolved from the session, and a passthrough that could overwrite them would
		// break a turn in ways no error message would explain. Everything else is the author's business.
		return array_diff_key($extra, array_flip(['model', 'messages', 'stream', 'stream_options', 'tools']));
	}

	/**
	 * Canonical `effort:` -> OpenAI's `reasoning_effort` wire param. Verbatim -- levels are version-dependent
	 * (GPT-5: minimal|low|medium|high; GPT-5.4 adds none|xhigh; GPT-5.6 adds max) and validated by the API,
	 * not here. The legacy `reasoning_effort:` authoring key was removed in 11.2 (standardized on `effort:`).
	 *
	 * Merged separately from getChatCompletionsParams() so the whole OpenAI-compatible family inherits effort
	 * whether or not it overrides its knobs. A provider whose endpoint has no reasoning param at all overrides
	 * this to return [] AND declares supportsReasoning() false, so the UI stops offering levels too.
	 */
	protected function _getReasoningParams() : array {
		if(!($effort = $this->getEffort()))
			return [];

		return ['reasoning_effort' => $effort];
	}

	// The union across GPT-5.x. `none` and `xhigh` arrived in 5.4, `max` in 5.6, so an older model rejects the
	// top of this list -- it's the vocabulary to offer, not a per-model whitelist.
	function getEffortLevels() : array {
		return ['none', 'minimal', 'low', 'medium', 'high', 'xhigh', 'max'];
	}

	/**
	 * Does this model know `none` as a reasoning level? GPT-5 shipped minimal|low|medium|high; `none` arrived
	 * in GPT-5.4. Parsed out of the id rather than whitelisted — free-text model ids are deliberate here, so a
	 * new release works the day it ships. An id we can't parse returns FALSE: sending an invalid level is a
	 * 400, while not sending one is at worst the status quo.
	 */
	protected function _supportsReasoningEffortNone(string $model) : bool {
		if(null === ($v = $this->_parseGptVersion($model)))
			return false;

		[$major, $minor] = $v;

		return $major > 5 || (5 === $major && $minor >= 4);
	}

	// `gpt-5.6-sol` -> [5, 6]. Null for anything that isn't a parseable `gpt-<major>[.<minor>]` id, which is
	// the signal to assert NOTHING about it rather than guess.
	protected function _parseGptVersion(string $model) : ?array {
		if(!preg_match('/^gpt-(\d+)(?:\.(\d+))?/', DevblocksPlatform::strLower(trim($model)), $matches))
			return null;

		return [intval($matches[1]), intval($matches[2] ?? 0)];
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
	 * no agent at all. Models predating the `none` level (gpt-5.0 to 5.3) keep the omit behavior -- there is
	 * nothing better to send them, and their turn may well work. Anything that is not a gpt id at all is
	 * left strictly alone; see the early return.
	 *
	 * This whole rule is a chat-completions workaround with an expiry date: `/v1/responses` is the endpoint
	 * where tools and reasoning coexist, so once a gpt-5.4+ turn routes there the guardrail stops firing for
	 * the models it was written for, and survives only for someone who has explicitly asked for `api: chat`.
	 *
	 * Split out of chatCompletion so the rule is testable without a live call; it has been wrong once.
	 */
	protected function _applyToolReasoningGuardrail(array $params, array $tools, string $model) : array {
		if(!$tools)
			return $params;

		if(!$this->_appliesToolReasoningGuardrail($model))
			return $params;

		// An id that won't sit on OpenAI's version scale isn't OpenAI's model, so that endpoint's refusal
		// can't be this turn's rule -- and _parseGptVersion() already states that null means "assert NOTHING
		// about it rather than guess". The else branch below was guessing: it UNSET the level for every id
		// that failed the probe, which is every self-hosted and third-party model reached through
		// `provider: openai` (a local `Qwen3.8-27B-8bit`, an `unsloth/gpt-oss-20b-GGUF`, a `DeepSeek-V4-Pro`).
		// Those turns lost the author's reasoning level entirely, on every tool call, with no error anywhere
		// -- indistinguishable from a model that simply doesn't think very hard.
		//
		// Returning early is not merely safer, it is the status quo ante: a server that never had the
		// limitation gets exactly the request it would have got before this guardrail existed.
		if(null === $this->_parseGptVersion($model))
			return $params;

		if($this->_supportsReasoningEffortNone($model)) {
			$params['reasoning_effort'] = 'none';
		} else {
			unset($params['reasoning_effort']);
		}

		return $params;
	}

	/**
	 * Is the tool-vs-reasoning refusal THIS endpoint's rule?
	 *
	 * It belongs to OpenAI's own /v1/chat/completions, not to the dialect. Left unguarded it misfires on every
	 * compatible subclass at once: the `^gpt-(\d+)` probe in _supportsReasoningEffortNone() can't match a
	 * `gemini-3-pro` or a `moonshotai/kimi-k3`, so each one takes the else branch and has the author's effort
	 * silently STRIPPED on any tool-using turn -- an endpoint that never had the limitation refusing to reason
	 * because OpenAI's does.
	 *
	 * That was survivable only while Groq/Together/HuggingFace blanked effort outright; now that the whole
	 * family forwards it, the rule has to say which endpoints it governs. Compatible providers override to
	 * false. OpenRouter is the one true-but-narrowed case -- it fronts OpenAI's own models among others, so it
	 * gates on the `openai/` namespace in its own override instead.
	 */
	protected function _appliesToolReasoningGuardrail(string $model) : bool {
		return true;
	}

	/**
	 * The role that carries the system prompt. OpenAI renamed `system` to `developer` for the reasoning-model
	 * era, but that rename is OpenAI's alone — the OpenAI-COMPATIBLE endpoints validate against a fixed role
	 * enum and hard-reject `developer` with a 400 ("developer is not one of [...]"). Any subclass pointed at a
	 * compatible endpoint rather than at OpenAI itself MUST override this to `system`.
	 */
	function getSystemPromptRole() : string {
		return 'developer';
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
					'role' => $this->getSystemPromptRole(),
					'content' => $system_prompt,
				]
			);
		}
		
		$verb = 'POST';
		$url = $this->getChatCompletionEndpointUrl($base_url);
		$headers = [
			'Content-Type' => 'application/json',
		];
		$streaming = $this->_isStreamingTurn();

		$body_payload = [
			'model' => $this->getParam('model', ''),
			'stream' => $streaming,
			'messages' => $model_messages,
		];

		// WITHOUT THIS, A STREAMED TURN REPORTS NO USAGE AT ALL. OpenAI omits the usage object entirely
		// from a stream unless it's asked for, and the failure is silent: every streamed turn records
		// zero tokens, so the session denorm, the context-window gauge and the transcript chips all go
		// quietly wrong rather than visibly breaking.
		if($streaming && null !== ($stream_options = $this->_getStreamOptions()))
			$body_payload['stream_options'] = $stream_options;

		if($tools)
			$body_payload['tools'] = $tools;

		// Add provider-specific body params + the canonical reasoning level (separate seams so a subclass's
		// knobs override can't drop effort), then reconcile that level with tools (see the guardrail).
		$provider_params = $this->_applyToolReasoningGuardrail(
			array_merge($this->getChatCompletionsParams(), $this->_getReasoningParams()),
			$tools,
			$this->getParam('model', '')
		);

		if($provider_params) {
			$body_payload = array_merge($body_payload, $provider_params);
		}

		// The author's escape hatch gets the LAST word, including over the tool guardrail above. An escape
		// hatch a guardrail can overrule isn't one -- and overruling this particular guardrail is loud (the
		// API refuses the turn and names the reason), which is the right shape for "I know what I'm doing".
		//
		// RECURSIVE, not shallow: an author routinely sets several keys inside one container over more than
		// one line, and a container this replaces wholesale would silently drop whatever else was in it.
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
			// No response at all (connect refused, DNS, cURL timeout) → status 0, a transient/retryable class.
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

		// Neutral token usage. OpenAI's prompt_tokens INCLUDES cached, so fresh input = prompt − cached;
		// cache_read = cached_tokens; OpenAI doesn't report cache writes (0).
		//
		// `reasoning_tokens` is a BREAKDOWN of completion_tokens, not a sibling cost — it stays inside `output`
		// and is reported alongside so a reader can split the invisible half from the visible reply. It is the
		// only artifact of reasoning this endpoint returns: /v1/chat/completions sends back no reasoning
		// content, so without this number a 1,132-token turn that printed nine words looks like a billing
		// mystery. Absent (a non-reasoning model, an OpenAI-compatible endpoint that omits the detail block)
		// reads as 0, which is indistinguishable from "reasoned nothing" — deliberate, since neither is a cost.
		$native_usage = $response_json['usage'] ?? [];
		$cached = intval($native_usage['prompt_tokens_details']['cached_tokens'] ?? 0);
		$usage = [
			'input' => max(0, intval($native_usage['prompt_tokens'] ?? 0) - $cached),
			'output' => intval($native_usage['completion_tokens'] ?? 0),
			'reasoning' => intval($native_usage['completion_tokens_details']['reasoning_tokens'] ?? 0),
			'cache_read' => $cached,
			'cache_write' => 0,
		];

		$message = $response_json['choices'][0]['message'] ?? null;

		// Why generation stopped. A SIBLING of `message`, so convertToGenericMessage() never sees it — it has to
		// ride along explicitly, exactly like usage. `length` = hit the output ceiling, routinely with empty content.
		$finish_reason = self::normalizeFinishReason($response_json['choices'][0]['finish_reason'] ?? null);

		// Add to the memory (usage rides the assistant turn — usage_json column, not the replayed data_json)
		if($message)
			$memory->appendMessage($message, usage: $usage, finish_reason: $finish_reason);

		$response = $this->convertToGenericMessage($message);
		$response->setUsage($usage);
		$response->setFinishReason($finish_reason);

		return $response;
	}

	/**
	 * Ask for a usage frame on streamed turns. Overridable because it is an extra top-level body key,
	 * and a stricter OpenAI-COMPATIBLE endpoint (llama.cpp, vLLM, a vendor's compat layer) may reject
	 * what it doesn't recognize. Return null there and accept unreported usage.
	 */
	protected function _getStreamOptions() : ?array {
		return ['include_usage' => true];
	}

	/**
	 * The OpenAI-family streaming grammar. The base class owns the transport, the abort, the error
	 * classification and the non-SSE fallback; this rebuilds the SAME `$response_json` the blocking
	 * path parses (`choices[0].message` + `choices[0].finish_reason` + `usage`) so usage mapping,
	 * appendMessage() and convertToGenericMessage() stay on one code path.
	 *
	 * Two shape differences from Anthropic drive everything here:
	 *   - Frames carry NO `event:` name, so there is nothing to switch on — dispatch on the payload.
	 *   - There is no per-block close event, so a tool call's completeness can only be judged from
	 *     whether its accumulated `arguments` parse. See sanitizePartialContent().
	 */
	protected function _streamAccumulator() : array {
		$content = '';
		$reasoning = '';
		// Which sibling key this server uses for reasoning; kept so the accumulated message round-trips
		// through _pushMessageReasoning() exactly as the blocking response would have.
		$reasoning_key = 'reasoning_content';
		$tool_calls = [];
		$finish_reason = null;
		$usage = [];
		$api_error = null;

		$on_event = function(string $event, string $data) use (
			&$content, &$reasoning, &$reasoning_key, &$tool_calls, &$finish_reason, &$usage, &$api_error
		) : void {
			$data = trim($data);

			// The end-of-stream sentinel is a bare token, not JSON — decoding it yields null.
			if('' === $data || '[DONE]' === $data)
				return;

			if(!is_array($d = json_decode($data, true)))
				return;

			// An API error can arrive INSIDE a 200. Record it and let the stream end; the base throws
			// once the transfer closes, so this doesn't read as our own abort.
			if(($err = $d['error'] ?? null)) {
				$api_error = is_array($err) ? $err : ['type' => '', 'message' => strval($err)];
				return;
			}

			// The usage frame arrives LAST and carries an EMPTY `choices`, so nothing below may assume
			// `choices[0]` exists.
			if(is_array($d['usage'] ?? null))
				$usage = $d['usage'];

			if(!is_array($choice = $d['choices'][0] ?? null))
				return;

			if(($reason = $choice['finish_reason'] ?? null))
				$finish_reason = $reason;

			if(!is_array($delta = $choice['delta'] ?? null))
				return;

			if(is_string($delta['content'] ?? null))
				$content .= $delta['content'];

			foreach(['reasoning_content', 'reasoning'] as $key) {
				if(is_string($delta[$key] ?? null) && '' !== $delta[$key]) {
					$reasoning_key = $key;
					$reasoning .= $delta[$key];
				}
			}

			// Tool calls arrive as INDEX-KEYED FRAGMENTS -- `id`, `function.name` and
			// `function.arguments` each split across chunks, and only `index` ties them together.
			foreach(($delta['tool_calls'] ?? []) as $fragment) {
				if(!is_array($fragment))
					continue;

				$idx = intval($fragment['index'] ?? 0);

				if(!array_key_exists($idx, $tool_calls)) {
					$tool_calls[$idx] = [
						'id' => '',
						'type' => 'function',
						'function' => ['name' => '', 'arguments' => ''],
					];
				}

				if(is_string($fragment['id'] ?? null) && '' !== $fragment['id'])
					$tool_calls[$idx]['id'] = $fragment['id'];

				if(is_string($fragment['type'] ?? null) && '' !== $fragment['type'])
					$tool_calls[$idx]['type'] = $fragment['type'];

				if(!is_array($fn = $fragment['function'] ?? null))
					continue;

				// Appended, not assigned: the spec splits a name across chunks like anything else. It
				// is sent once per call in practice, so appending is the safe reading of both cases.
				if(is_string($fn['name'] ?? null))
					$tool_calls[$idx]['function']['name'] .= $fn['name'];

				if(is_string($fn['arguments'] ?? null))
					$tool_calls[$idx]['function']['arguments'] .= $fn['arguments'];
			}
		};

		// The turn so far, in the shape that gets persisted AND replayed to the API verbatim. `content`
		// is a plain STRING here (not a block list), and NULL when the turn is nothing but tool calls —
		// both shapes hasReplayableContent() already understands.
		$snapshot = function() use (&$content, &$reasoning, &$reasoning_key, &$tool_calls) : array {
			$message = [
				'role' => 'assistant',
				'content' => ('' !== $content) ? $content : null,
			];

			if('' !== $reasoning)
				$message[$reasoning_key] = $reasoning;

			if($tool_calls) {
				// Fragments can interleave, so order by the index the API assigned rather than arrival.
				ksort($tool_calls);
				$message['tool_calls'] = array_values($tool_calls);
			}

			return $message;
		};

		// NB: `use (&$x)`, not `fn() => $x`. An arrow function captures BY VALUE at creation, which here
		// is before a single event has landed — every one of these would report the empty initial state.
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
				return [
					'choices' => [
						[
							'message' => $snapshot(),
							'finish_reason' => $finish_reason,
						],
					],
					'usage' => $usage,
				];
			},
		];
	}

	// An ordinary chat completion — what the blocking path parses. Used by the base's `$saw_event`
	// fallback when we asked for a stream and got a normal body back (an endpoint that ignores
	// `stream`, a proxy that buffers). Degrading to non-streamed is the correct outcome, not a failure.
	protected function _parseNonStreamedBody(array $body) : ?array {
		return isset($body['choices'][0]['message']) ? $body : null;
	}

	/**
	 * Map an in-stream error's `type` back onto the HTTP status it would have carried in a non-streamed
	 * response, so the caller's retryable-vs-terminal classification keeps working even though the
	 * transport returned 200. Compatible endpoints invent their own types, so unknown stays retryable.
	 */
	protected static function _streamErrorStatus(string $type) : int {
		return match($type) {
			'invalid_request_error' => 400,
			'authentication_error', 'invalid_api_key' => 401,
			'permission_error', 'insufficient_quota' => 403,
			'not_found_error' => 404,
			'rate_limit_error', 'rate_limit_exceeded', 'requests' => 429,
			'overloaded_error' => 529,
			// Unknown types included: treat as a server-side fault so a transient novelty stays retryable.
			default => 500,
		};
	}

	/**
	 * Structural salvage rules for a turn cut short. Purely structural by contract — a worker killed
	 * mid-stream is recovered on the session's NEXT turn by a different process that never saw the
	 * stream, and (via resolveDanglingStream) by a provider built with no params at all.
	 */
	public function sanitizePartialContent(array $message) : array {
		// Blank content becomes null rather than being dropped: that IS the native shape for a turn
		// that is nothing but tool calls, and it is what gets replayed to the API.
		$content = $message['content'] ?? null;
		$message['content'] = (is_string($content) && '' !== trim($content)) ? $content : null;

		foreach(['reasoning_content', 'reasoning'] as $key) {
			if(!array_key_exists($key, $message))
				continue;

			// No signature to verify, unlike an Anthropic thinking block — the OpenAI family doesn't
			// reject partial reasoning on replay, so a non-blank fragment is worth keeping.
			if(!is_string($message[$key]) || '' === trim($message[$key]))
				unset($message[$key]);
		}

		$kept = [];

		foreach(($message['tool_calls'] ?? []) as $tool_call) {
			if(!is_array($tool_call))
				continue;

			// No id means nothing can be paired against it, so it can neither be answered nor executed.
			if('' === trim(strval($tool_call['id'] ?? '')))
				continue;

			if(!is_array($fn = $tool_call['function'] ?? null))
				continue;

			if('' === trim(strval($fn['name'] ?? '')))
				continue;

			$arguments = trim(strval($fn['arguments'] ?? ''));

			// THE OPENAI EQUIVALENT OF ANTHROPIC'S ABSENT-`input` TELL. There is no per-call close event
			// here, so the only structural evidence that arguments were severed mid-token is that they
			// don't parse. A legitimate no-argument call sends '' or '{}'; a severed one sends a prefix
			// like `{"path":"/et`. Nothing can rescue truncated JSON, and a tool call whose arguments we
			// can't read can't be answered OR executed.
			if('' !== $arguments && !is_array(json_decode($arguments, true)))
				continue;

			$kept[] = $tool_call;
		}

		if($kept)
			$message['tool_calls'] = $kept;
		else
			unset($message['tool_calls']);

		return $message;
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

	// The gpt-5.x family is multimodal with a 400K-token window. Powers the agent model editor's "default on
	// select" (vision + context window) when a model is picked from the live list.
	function getModelDefaults(string $model) : array {
		if(!str_starts_with($model, 'gpt-'))
			return [];

		$defaults = [
			'vision' => true,
			'context_window' => 400000,
		];

		// Per-MODEL levels, narrowed by version. `reasoning_effort` is a GPT-5-era param, so anything below 5
		// (and any id we can't parse) gets NO list -- the picker then offers nothing rather than levels the
		// model has no notion of. Within 5.x: `none`/`xhigh` arrived in 5.4 and `max` in 5.6, so handing over
		// the whole provider vocabulary would offer an older model levels it rejects.
		if(null !== ($v = $this->_parseGptVersion($model))) {
			[$major, $minor] = $v;

			if($major >= 5) {
				$levels = ['minimal', 'low', 'medium', 'high'];

				if($major > 5 || $minor >= 4)
					$levels = array_merge(['none'], $levels, ['xhigh']);

				if($major > 5 || $minor >= 6)
					$levels[] = 'max';

				$defaults['effort_levels'] = $levels;
			}
		}

		return $defaults;
	}

	// The HOSTED OpenAI API auto-caches with a documented ~5-minute reuse window → a soft 5m ring hint. A
	// self-hosted OpenAI-compatible endpoint (llama.cpp / vLLM / unsloth via `api_endpoint_url`) also caches —
	// you'll see `cache_read` — but as an in-memory KV/prefix cache with NO wall-clock TTL we can predict (it
	// holds until the server evicts the prefix), so a time countdown would lie: return null (no ring) there.
	function getCacheHintSeconds(array $params) : ?int {
		if('' !== trim(strval($params['api_endpoint_url'] ?? '')))
			return null;

		return 300;
	}

	// `/v1/models` returns every model this key can see -- embeddings, tts, transcription, image, moderation
	// -- with NO type field to tell them apart, so the only signal is the id. Drop the known non-chat families
	// (best-effort: a mis-classified id just means a free-text model string still works). A local
	// OpenAI-compatible endpoint keys off this same override, so its non-chat models are filtered too.
	protected function _parseChatModelsResponse(array $response_json) : array {
		$models = parent::_parseChatModelsResponse($response_json);

		$non_chat = [
			'text-embedding-', 'text-similarity-', 'text-search-', 'code-search-', // embeddings
			'tts-', 'whisper-', 'gpt-4o-transcribe', 'gpt-4o-mini-transcribe', 'gpt-audio', // audio
			'dall-e-', 'gpt-image-', 'sora', // image / video
			'text-moderation-', 'omni-moderation-', // moderation
			'davinci', 'curie', 'babbage', 'ada', // legacy completions/embeddings
		];

		return array_values(array_filter($models, function($id) use ($non_chat) {
			foreach($non_chat as $needle)
				if(str_contains($id, $needle))
					return false;
			return true;
		}));
	}

	function getChatKataAutocomplete() : array {
		return [
			'keys' => [
				['caption' => 'model:', 'snippet' => 'model:', 'score' => 2000],
				'api_endpoint_url:',
				'authentication:',
				['caption' => 'effort:', 'snippet' => "effort: medium", 'docHTML' => '<b>effort:</b>Reasoning effort (empty = provider default). Version-dependent values, e.g. <code>none|minimal|low|medium|high|xhigh|max</code>.'],
				['caption' => 'stream@bool:', 'snippet' => 'stream@bool: no', 'docHTML' => '<b>stream@bool:</b>Stream the response (default <code>yes</code>). Streaming replaces the request timeout with an inactivity cutoff, so a long turn is not killed partway through, and it lets a running turn be stopped. Set <code>no</code> for an OpenAI-compatible endpoint that does not stream correctly.'],
				['caption' => 'extra_body:', 'snippet' => "extra_body:\n\tchat_template_kwargs:\n\t\treasoning_effort: \${1:medium}", 'docHTML' => '<b>extra_body:</b>Extra request-body keys, merged verbatim into the top level &mdash; the same meaning the OpenAI SDK\'s <code>extra_body</code> has, so a vendor\'s snippet transcribes directly. For an OpenAI-<i>compatible</i> server whose knobs aren\'t where the OpenAI spec puts them: oMLX and vLLM read reasoning out of <code>chat_template_kwargs</code> and ignore the standard top-level <code>reasoning_effort</code> entirely, so <code>effort:</code> alone does nothing there. <b>A level sent this way is validated by the chat template</b> &mdash; Qwen3 accepts only <code>low|medium|xhigh</code> and fails the request on anything else, where before it was silently dropped. Cerb keeps <code>model</code>, <code>messages</code>, <code>tools</code>, <code>stream</code> and <code>stream_options</code>; anything else you set here wins, including over <code>effort:</code>.'],
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => ['https://api.openai.com', 'http://host.docker.internal:8080'],
				'effort:' => $this->getEffortLevels(),
				'extra_body:' => ['chat_template_kwargs:'],
				'extra_body:chat_template_kwargs:' => ['reasoning_effort: medium', 'enable_thinking@bool: no', 'preserve_thinking@bool: yes'],
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