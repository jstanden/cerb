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

class AwsBedrock extends Extension_DevblocksLlmProvider implements Chat, Embedding {
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

					return ['toolSpec' => [
						'name' => strval($fn['name']),
						'description' => strval($fn['description'] ?? ''),
						// An argument-less tool still needs a schema object, never `[]`.
						'inputSchema' => ['json' => $fn['parameters'] ?: (object)['type' => 'object']],
					]];
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

		$verb = 'POST';
		$url = $base_url . '/model/' . $model . '/converse';
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
		if($authentication_uri) {
			if(!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				throw new Exception_DevblocksAutomationError($error);
		}
		
		if(false === ($response = $http->sendRequest($request, $request_options, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		if(false === ($response_json = $http->getResponseAsJson($response, $error)))
			throw new Exception_DevblocksAutomationError($error);
		
		// Converse reports failures as a TOP-LEVEL `{message}`, not OpenAI's `{error:{message}}` -- reading only
		// the nested key turned every validation error into a bare "HTTP status code: 400" with nothing to act
		// on. _getApiErrorMessage() knows both shapes.
		if(200 != $response->getStatusCode()) {
			throw new Exception_DevblocksAutomationError(
				$this->_getApiErrorMessage($response_json, $response->getStatusCode())
			);
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

			$messages[$message_index]['content'] = array_map(
				function($content) {
					// Fix tool use for empty inputs [] -> {}
					if(
						is_array($content['toolUse'] ?? null)
						&& is_array($content['toolUse']['input'] ?? null)
						&& empty($content['toolUse']['input'])
					) $content['toolUse']['input'] = (object)[];

					return $content;
				},
				$message['content']
			);
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
		$support = $this->_getPromptCachingSupport();

		if(array_key_exists($model, $support))
			return $support[$model];

		// An inference profile (`us.anthropic.foo`) isn't in the foundation-model catalog under that id --
		// resolve it to the model it fronts.
		$bare = preg_replace('#^[a-z0-9-]+\.(?=[a-z0-9-]+\.)#i', '', $model);

		return $support[$bare] ?? false;
	}

	/** @return array<string,bool> modelId => supports cachePoint */
	private function _getPromptCachingSupport() : array {
		if(!($base_url = rtrim(strval($this->getParam('api_endpoint_url')), '/')))
			return [];

		$cache = DevblocksPlatform::services()->cache();
		$cache_key = 'bedrock_prompt_caching_' . sha1($base_url);

		if(is_array($support = $cache->load($cache_key)))
			return $support;

		$error = null;
		$response_json = $this->_fetchModelsJson($this->getChatModelsEndpointUrl($base_url), [], $error);

		// No catalog (no `bedrock:ListFoundationModels`, network trouble) -> nobody gets cachePoints. Cached
		// anyway so a broken lookup doesn't re-fire on every turn.
		$support = [];

		foreach(($response_json['modelSummaries'] ?? []) as $row) {
			if(!is_array($row) || '' === ($model_id = strval($row['modelId'] ?? '')))
				continue;

			$support[$model_id] = (bool) (
				($row['explicitPromptCaching']['isSupported'] ?? false)
				|| ($row['featuresSupported']['promptCaching'] ?? false)
			);
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
			],
			'values' => [
				'model:' => $this->getChatModels(),
				'authentication:' => ['type' => 'cerb-uri', 'params' => ['connected_account' => null]],
				'api_endpoint_url:' => $this->_getEndpointUrls(),
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