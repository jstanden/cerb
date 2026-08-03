<?php

use Cerb\LLM\MemoryStore\DatabaseHistory;
use GuzzleHttp\Psr7\Request;

abstract class Extension_DevblocksLlmMemoryStore {
	private string $_session_id;
	
	function __construct($session_id) {
		$this->_session_id = $session_id;
	}
	
	function getSessionId() : string {
		return $this->_session_id;
	}
	
	abstract function getMessages(int $limit=10) : array;

	/**
	 * Append a message as a child of the current cursor leaf and advance the leaf onto it
	 * (append-only tree). $kind overrides the structural classifier (e.g. 'summary'). Returns
	 * the persisted model, or null for stores without a backing table (e.g. NoHistory).
	 */
	abstract function appendMessage(array $message, ?string $kind=null, ?array $usage=null, ?string $finish_reason=null) : ?\Model_LlmAgentMessage;

	/**
	 * Return the ACTIVE-PATH message models (leaf→nearest-summary) so a history strategy can
	 * budget and compact the send-list. Stores without a backing table (e.g. NoHistory) return [].
	 * $limit 0 = the whole bounded path.
	 *
	 * @return Model_LlmAgentMessage[]
	 */
	function getMessageModels(int $limit=0) : array {
		return [];
	}

	/**
	 * Open an assistant turn that is still being generated, and make it the cursor leaf immediately.
	 *
	 * A streamed turn is persisted WHILE it runs rather than once at the end, because the process producing
	 * it and the process displaying it are different ones — the only way a poller can watch a ten-minute turn
	 * progress is if the partial is durable. Getting a real uuid up front is the other half: the client
	 * addresses that one message by id instead of re-rendering the whole transcript on every tick.
	 *
	 * Returns the new message uuid, or null for stores with no backing table (they simply don't persist, and
	 * every method below tolerates that).
	 */
	function beginStreamingMessage() : ?string {
		return null;
	}

	/** Rewrite an open streamed turn's content. Callers MUST throttle; deltas arrive far faster than any reader. */
	function updateStreamingMessage(string $uuid, array $message, ?array $usage=null) : void {}

	/**
	 * Close an open streamed turn — final content, usage, finish reason, flag cleared. After this it's
	 * ordinary immutable history.
	 */
	function finalizeStreamingMessage(string $uuid, array $message, ?array $usage=null, ?string $finish_reason=null) : void {}

	/**
	 * Drop an open streamed turn that has nothing worth keeping. An EMPTY assistant head is worse than none:
	 * downstream guards read the head's role and would treat it as a real turn that simply said nothing.
	 */
	function discardStreamingMessage(string $uuid) : void {}

	/** The uuid of the streamed turn currently open on this store, or null. */
	function getOpenStreamingMessage() : ?string {
		return null;
	}

	/**
	 * Resolve a turn left open by a process that died mid-stream (a killed worker, a fatal). Called at the
	 * START of the next turn rather than from a reaper: the next turn is exactly the moment a stale head would
	 * do damage, and it needs no scheduling to be correct.
	 */
	function resolveDanglingStream() : void {}
}

class DevblocksLlmChatResponse_Tool {
	private string $_id;
	private string $_name;
	private array $_parameters;
	
	function __construct(string $name, array $parameters, string $id = '') {
		$this->_id = $id;
		$this->_name = $name;
		$this->_parameters = $parameters;
	}
	
	function getId() : string {
		return $this->_id;
	}
	
	function getName(): string {
		return $this->_name;
	}
	
	function getParameters(): array {
		return $this->_parameters;
	}
	
	function getLabel(?array $tool_labels) : ?string {
		$label = sprintf("Tool: %s", $this->getName());
		
		if($tool_labels && array_key_exists($this->getName(), $tool_labels)) {
			$label = $tool_labels[$this->getName()];
			
			if(str_contains($label, '{{')) {
				$tpl_builder = DevblocksPlatform::services()->templateBuilder();
				if(false !== ($new_label = $tpl_builder->build($label, $this->getParameters())))
					$label = $new_label;
			}
		}
		
		return $label;
	}
	
	function serialize() : array {
		return [
			'id' => $this->_id,
			'name' => $this->_name,
			'parameters' => $this->_parameters,
		];
	}
}

class DevblocksLlmChatResponse {
	private string $_role = '';
	private ?string $_uuid = '';
	private array $_messages = [];
	private array $_images = [];
	private array $_tool_calls = [];
	private array $_tool_results = [];
	private array $_usage = [];
	private array $_thinking = [];
	private string $_finish_reason = '';

	function __construct(string $role = 'assistant', ?string $uuid = null) {
		$this->setRole($role);
		$this->setUuid($uuid);
	}
	
	function getRole() : string {
		return $this->_role;
	}
	
	function setUuid(?string $uuid) : void {
		$this->_uuid = $uuid;
	}
	
	function getUuid() : ?string {
		return $this->_uuid;
	}
	
	function setRole(string $role) : void {
		$this->_role = $role;
	}
	
	function pushMessage(string $message) : void {
		$this->_messages[] = [
			'type' => 'text',
			'content' => $message,
		];
	}
	
	function getMessages() : array {
		return $this->_messages;
	}

	// Neutral image content block for DISPLAY (the transcript viewer): mime_type + a render URL. The SEND path
	// builds native image parts from the message's raw `images:` resource uris (expandMessageImages), NOT from
	// here — so this carries a lazy URL, never a base64 blob in the rendered HTML.
	function pushImage(string $mime_type, string $url) : void {
		$this->_images[] = [
			'mime_type' => $mime_type,
			'url' => $url,
		];
	}

	function getImages() : array {
		return $this->_images;
	}
	
	function getUsage() : array {
		return $this->_usage;
	}
	
	// Provider-neutral token usage for the turn: {input, output, cache_read, cache_write} (input = fresh/uncached
	// prompt tokens; cache_write is 0 on providers that don't report it). Set by each provider's chatCompletion
	// from its native `usage` block; persisted on the assistant message (usage_json).
	function setUsage(array $usage) : void {
		$this->_usage = $usage;
	}

	// Reasoning summary blocks (Anthropic `thinking` content when display:summarized) — surfaced for the
	// transcript viewer's collapsed "Thinking" section. Empty when thinking is off or display:omitted.
	function pushThinking(string $thinking) : void {
		if('' !== trim($thinking))
			$this->_thinking[] = $thinking;
	}

	function getThinking() : array {
		return $this->_thinking;
	}

	// Why generation stopped, normalized across providers (see Extension_DevblocksLlmProvider::normalizeFinishReason).
	// '' when the provider didn't report one. `length` is the load-bearing value: the turn hit its output ceiling
	// and whatever it produced is truncated — routinely with EMPTY content, which is otherwise indistinguishable
	// from a model that had nothing to say.
	function setFinishReason(?string $finish_reason) : void {
		$this->_finish_reason = strval($finish_reason);
	}

	function getFinishReason() : string {
		return $this->_finish_reason;
	}

	function pushTool(DevblocksLlmChatResponse_Tool $tool) : void {
		$this->_tool_calls[] = $tool;
	}
	
	/**
	 * @return DevblocksLlmChatResponse_Tool[]
	 */
	function getToolCalls() : array {
		return $this->_tool_calls;
	}
	
	function pushToolResult($id, $content) : void {
		$this->_tool_results[$id] = $content;
	}
	
	function getToolResults() : array {
		return $this->_tool_results;
	}
}

abstract class Extension_DevblocksLlmProvider {
	/**
	 * A finish reason no provider reports: the turn was cut short on OUR side — a user pressed Stop, or the
	 * stream died — rather than by the model deciding to end. It sits alongside the normalized vocabulary
	 * `normalizeFinishReason()` produces (`length`/`stop`/`tool_calls`/`filter`) and matters because the
	 * difference is not cosmetic: a turn that ended this way may hold structurally incomplete blocks, so its
	 * tool calls must be ANSWERED rather than executed, and its content must be sanitized before replay.
	 */
	const FINISH_REASON_INTERRUPTED = 'interrupted';

	/**
	 * Does this provider-native message carry anything worth keeping? Asked of a salvaged partial to decide
	 * "finalize it" vs "drop the row" — and an empty assistant head is worse than none, because the guards
	 * downstream read the head's role and would treat it as a real turn that said nothing.
	 *
	 * Shape-tolerant ON PURPOSE. `data_json` is provider-NATIVE (it's replayed to the API verbatim), and the
	 * families disagree: Anthropic content is a list of blocks; the OpenAI family's is a plain STRING, or NULL
	 * when the turn is nothing but `tool_calls`. Assuming the Anthropic shape here fatals on the first
	 * (`array_filter()` on a string) and silently deletes a tool-call turn on the second.
	 */
	static function hasReplayableContent(array $message) : bool {
		$content = $message['content'] ?? null;

		if(is_string($content))
			return '' !== trim($content);

		if(is_array($content))
			return (bool) array_filter($content);

		// OpenAI-family: a turn that is purely tool calls carries no content at all.
		return !empty($message['tool_calls']);
	}

	protected array $_params = [];
	
	function __construct(array $params, bool $validate=true) {
		$this->_params = $params;
	}
	
	function getParam(string $key, mixed $default=null) : mixed {
		if(!array_key_exists($key, $this->_params))
			return $default;
		
		return $this->_params[$key] ?? null;
	}
	
	function setParam(string $key, mixed $value) : void {
		$this->_params[$key] = $value;
	}

	// The chosen reasoning-effort level from provider_params (e.g. low|medium|high|xhigh|max), normalized, or
	// null when unset. Reasoning-capable providers translate this to their native request param (Anthropic →
	// output_config.effort, OpenAI/Gemini → reasoning_effort). Passed through verbatim — the provider API
	// validates the level for its model; we don't clamp or whitelist (levels vary per model/version).
	function getEffort() : ?string {
		$e = $this->getParam('effort');
		return (is_string($e) && '' !== trim($e)) ? DevblocksPlatform::strLower(trim($e)) : null;
	}

	/**
	 * Reverse of a provider's convertToGenericMessage(): render a neutral message back
	 * into native wire format for cross-provider replay. The base emits the OpenAI chat
	 * shape (most providers are OpenAI-compatible); Anthropic-family providers override
	 * with the content-block shape. Returns a LIST of native messages — a neutral
	 * tool-result message fans out to one native `tool` message per result. Provider-
	 * specific extras (reasoning blocks, cache_control, citations) have no neutral
	 * equivalent and are intentionally dropped on conversion.
	 *
	 * @return array List of provider-native message arrays.
	 */
	function toNativeMessage(DevblocksLlmChatResponse $message) : array {
		$tool_results = $message->getToolResults();

		// Each tool result becomes its own OpenAI `tool` message.
		if($tool_results) {
			$out = [];

			foreach($tool_results as $tool_id => $content) {
				$out[] = [
					'role' => 'tool',
					'tool_call_id' => $tool_id,
					'content' => is_array($content) ? json_encode($content) : strval($content),
				];
			}

			return $out;
		}

		$text = '';
		foreach($message->getMessages() as $block)
			$text .= ($block['content'] ?? '');

		$tool_calls = $message->getToolCalls();

		if($tool_calls) {
			$native = [
				'role' => 'assistant',
				'content' => ('' !== $text) ? $text : null,
				'tool_calls' => [],
			];

			foreach($tool_calls as $tool) {
				$native['tool_calls'][] = [
					'id' => $tool->getId(),
					'type' => 'function',
					'function' => [
						'name' => $tool->getName(),
						'arguments' => json_encode($tool->getParameters()),
					],
				];
			}

			return [$native];
		}

		return [[
			'role' => $message->getRole() ?: 'user',
			'content' => $text,
		]];
	}

	// The cerb-icons name for this provider's mark; concrete providers with a brand logo override it.
	function getIcon() : string {
		return 'bot';
	}

	// A brand background color for this provider's mark (white glyph on top), for avatar chips. Empty
	// means "no brand color" — callers fall back to a hashed/seeded color. Branded providers override.
	function getIconColor() : string {
		return '';
	}

	// Per-model display/capability defaults (e.g. ['vision' => true, 'context_window' => 1000000]) used
	// when the agentPrompt catalog omits them. Base returns none; providers may override for their models.
	function getModelDefaults(string $model) : array {
		return [];
	}


	/**
	 * The endpoint that lists this provider's available chat models. Mirrors
	 * OpenAI::getChatCompletionEndpointUrl() -- most providers are OpenAI-compatible, including the local
	 * servers (llama.cpp, LM Studio, vLLM) that make a LIVE list far more useful than any list we ship.
	 */
	function getChatModelsEndpointUrl(string $base_url) : string {
		return $base_url . '/v1/models';
	}

	// Extra headers the model endpoint needs beyond authentication (Anthropic's anthropic-version).
	protected function _getChatModelsRequestHeaders() : array {
		return [];
	}

	// OpenAI's `{data: [{id: ...}]}` -- which Anthropic's /v1/models also returns. Providers with another
	// shape (Ollama's `{models: [{name: ...}]}`) override this.
	protected function _parseChatModelsResponse(array $response_json) : array {
		return array_values(array_filter(array_map(
			fn($row) => strval($row['id'] ?? ''),
			$response_json['data'] ?? []
		)));
	}

	/**
	 * Ask the provider which chat models this key can actually use, rather than guessing from a list we
	 * hardcoded. That's the only way to know for a self-hosted OpenAI-compatible endpoint (llama.cpp, LM
	 * Studio, vLLM, Ollama), where the model ids are whatever the operator loaded.
	 *
	 * The same seam as testConnection(): base class, so no interface changes and no provider is required to
	 * participate. A provider whose endpoint doesn't exist just returns null with the error, and callers
	 * fall back to getChatModels().
	 *
	 * @return ?string[] Model ids, or null with $error set
	 */
	function fetchChatModels(?string &$error=null) : ?array {
		if(!($this instanceof \Cerb\LLM\Providers\Interfaces\Chat)) {
			$error = 'This provider does not support chat completions.';
			return null;
		}

		if(!($base_url = rtrim(strval($this->getParam('api_endpoint_url')), '/'))) {
			$error = 'This provider has no API endpoint configured.';
			return null;
		}

		$http = DevblocksPlatform::services()->http();

		$request = new Request('GET', $this->getChatModelsEndpointUrl($base_url), $this->_getChatModelsRequestHeaders());
		$request_options = ['http_errors' => false];

		if($authentication_uri = $this->getParam('authentication', null)) {
			if(!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				return null;
		}

		if(false === ($response = $http->sendRequest($request, $request_options, $error)))
			return null;

		if(200 != ($status_code = $response->getStatusCode())) {
			$error = sprintf('The provider returned HTTP %d when listing models.', $status_code);
			return null;
		}

		if(false === ($response_json = $http->getResponseAsJson($response, $error)))
			return null;

		if(!is_array($response_json)) {
			$error = 'The provider returned an unexpected model list.';
			return null;
		}

		$models = $this->_parseChatModelsResponse($response_json);

		sort($models, SORT_NATURAL | SORT_FLAG_CASE);

		return $models;
	}

	/**
	 * Verify this provider instance's credentials + model with the smallest real call there is: one
	 * unrecorded chat turn. Powers the agent model editor's "Test" button, where the alternative is
	 * discovering a bad key hours later inside an automation.
	 *
	 * Lives on the base class rather than the Chat interface on purpose -- adding an interface method would
	 * break third-party providers that implement it. A provider needing a different probe (a region/ARN
	 * handshake, an embedding-only provider) overrides this.
	 *
	 * @return ?array ['reply' => string, 'usage' => array, 'elapsed_ms' => int], or null with $error set
	 */
	function testConnection(?string &$error=null) : ?array {
		if(!($this instanceof \Cerb\LLM\Providers\Interfaces\Chat)) {
			$error = 'This provider does not support chat completions.';
			return null;
		}

		$started_at = microtime(true);

		try {
			$response = $this->chatCompletion(
				[
					[
						'role' => 'user',
						'content' => 'Reply with the single word: OK',
					]
				],
				'',
				[],
				new \Cerb\LLM\MemoryStore\NoHistory()
			);

		} catch(Throwable $e) {
			// Two shapes land here: Exception_DevblocksAutomationError from the constructor (missing
			// authentication:/model:) and Exception_DevblocksLlmApiError carrying the provider's own text
			// (invalid_api_key, model_not_found, a 404 from a bad api_endpoint_url). Both are the answer.
			$error = $e->getMessage();
			return null;
		}

		$reply = '';

		foreach($response->getMessages() as $block)
			$reply .= strval($block['content'] ?? '');

		return [
			'reply' => trim($reply),
			'usage' => $response->getUsage(),
			'elapsed_ms' => intval(round((microtime(true) - $started_at) * 1000)),
		];
	}

	// Does the configured model accept image input? Mirrors AgentPromptAwait::_buildModelEntry: explicit
	// `vision@bool` param → provider getModelDefaults() → false. The params bag rides on the provider instance
	// (constructed from provider_params), so a primed session resolves this directly off its provider.
	function supportsVision() : bool {
		$strings = DevblocksPlatform::services()->string();

		if(null !== ($v = $this->getParam('vision', null)))
			return $strings->toBool($v);

		$defaults = $this->getModelDefaults(strval($this->getParam('model', '')));
		return (bool) ($defaults['vision'] ?? false);
	}

	// Neutral prompt-cache INTENT, translated per provider (Anthropic sends explicit cache_control; OpenAI-family
	// auto-caches and ignores it). `enabled` is the on/off (LlmAgentNode::_defaultCache: agent-on, chat-off).
	// `ttl` is the ROLLING TAIL lifetime the author opts into — `5m` (default: a lapsed tail just re-parses the
	// last turns) or `1h` (editor/coding-agent sessions with long think/test pauses). The STABLE prefix
	// (tools+system) is always cached at the longest supported TTL regardless — it's written once and its bytes
	// never change, so a provider that honors TTL keeps it warm across pauses; that policy lives in the provider,
	// not here (only the tail is author-controlled).
	protected function _getCacheIntent() : array {
		$strings = DevblocksPlatform::services()->string();
		$ttl = strtolower(trim(strval($this->getParam('cache_ttl', '5m'))));

		return [
			'enabled' => $strings->toBool($this->getParam('cache', false)),
			'ttl' => in_array($ttl, ['5m', '1h'], true) ? $ttl : '5m',
			// Place a MESSAGE-level breakpoint at all. Off = the stable tools+system marker only, which reads but
			// can never mint a message entry. Only worth turning off when a read is implausible: on a session
			// whose cache has certainly lapsed, a message breakpoint buys nothing and costs a full-window write
			// (measured: 28,023 tokens at 1h TTL on a >1h-old transcript).
			'tail' => $strings->toBool($this->getParam('cache_tail', true)),

			// How many TRAILING messages to leave OUTSIDE the cached region — i.e. move the rolling breakpoint
			// back N messages instead of putting it on the last one. 0 (default) = today's behavior.
			//
			// For a summarize sidecar this is 1: its appended instruction turn must not be cached. Caching
			// through it mints an entry covering `tools + system + whole conversation + instruction` that
			// nothing can ever reuse (compaction replaces the history; the dev preview is one-shot), and a
			// write bills ~1.25x — measured as a 32K 1h write on a cold-cache run.
			//
			// ⚠ Moving it, NOT dropping it. Dropping the message breakpoint entirely was tried and measured at
			// **14% cached**: hit lookback runs BACKWARD from a breakpoint, so the system-block marker (which
			// renders before every message) can't reach a message-level entry at all — only tools+system read.
			// One message back is exactly the position an ordinary agent turn caches, so the read is the same
			// mechanism that already works turn to turn.
			'tail_skip' => max(0, intval($this->getParam('cache_tail_skip', 0))),
		];
	}

	// How long (seconds) this provider's prompt cache stays warm between turns for the given params — the
	// rolling-tail lifetime that lapses first, ASSUMING caching is on (the caller decides on/off; agent turns
	// default it ON). Powers the agentPrompt composer's cache TimeRing. Base returns null = this provider does
	// no prompt caching we can hint about; caching providers override. Param-taking (like getModelDefaults) so
	// the caller needn't reconstruct the provider with the model's params.
	function getCacheHintSeconds(array $params) : ?int {
		return null;
	}

	// Map a provider's native "why generation stopped" token onto a neutral one. Normalized at WRITE time, by the
	// provider that knows its own vocabulary, because a) consumers must not carry a per-provider mapping table, and
	// b) cross-provider replay means one session can hold messages written by different providers — a native value
	// read back through a DIFFERENT provider would be misread. The vocabularies don't collide, so one map serves all.
	//
	// Anything unrecognized passes through lowercased rather than being clamped to an `other` bucket: it keeps
	// debugging detail (Anthropic `pause_turn`, Ollama `load`/`unload`) while `length` stays a reliable signal.
	static function normalizeFinishReason(mixed $native) : string {
		// Not a string (absent, or an endpoint returning something structured) → unreported.
		if(!is_string($native))
			return '';

		$native = DevblocksPlatform::strLower(trim($native));

		if('' === $native)
			return '';

		return match($native) {
			'length', 'max_tokens', 'model_length' => 'length',
			'stop', 'end_turn', 'stop_sequence', 'eos' => 'stop',
			'tool_calls', 'tool_use', 'function_call' => 'tool_calls',
			'content_filter', 'content_filtered', 'refusal', 'safety', 'recitation', 'guardrail_intervened' => 'filter',
			// Passthrough is SANITIZED: this is unvalidated third-party text (the same class of self-hosted
			// endpoint that motivated all this) landing in a varchar(32).
			default => substr(strval(preg_replace('/[^a-z0-9_.-]/', '', $native)), 0, 32),
		};
	}

	// Surface an OpenAI-shaped message's reasoning as neutral thinking blocks. Unlike Anthropic (where thinking
	// is a `content` block), the OpenAI-compatible family carries it in a SIBLING key that varies by vendor:
	// `reasoning_content` (DeepSeek/Qwen via vLLM, llama.cpp, SGLang), `reasoning` (OpenRouter, Groq), or
	// `thinking` (Ollama). A reasoning model routinely returns an EMPTY `content` alongside it, so dropping the
	// key doesn't just lose the reasoning — it makes the whole turn render as nothing.
	protected function _pushMessageReasoning(array $message, DevblocksLlmChatResponse $response) : void {
		foreach(['reasoning_content', 'reasoning', 'thinking'] as $key) {
			if(!array_key_exists($key, $message))
				continue;

			$reasoning = $message[$key];

			// OpenRouter returns an array of reasoning blocks rather than a string.
			if(is_array($reasoning)) {
				$text = '';

				foreach($reasoning as $block) {
					if(is_string($block))
						$text .= $block;
					elseif(is_array($block))
						$text .= strval($block['text'] ?? $block['summary'] ?? $block['content'] ?? '');
				}

				$reasoning = $text;
			}

			if(!is_string($reasoning))
				continue;

			$response->pushThinking($reasoning);
		}
	}
	protected function _authenticateRequest(mixed $authentication_uri, Request &$request, array &$request_options, &$error=null) : bool {
		$actor = [CerberusContexts::CONTEXT_APPLICATION, 0];
		$uri_parts = DevblocksPlatform::services()->ui()->parseURI($authentication_uri);
		
		if (is_numeric($uri_parts['context_id'])) {
			$connected_account = DAO_ConnectedAccount::get($uri_parts['context_id']);
		} else {
			$connected_account = DAO_ConnectedAccount::getByUri($uri_parts['context_id']);
		}
		
		if (!$connected_account) {
			$error = 'authentication: is an invalid connected account.';
			return false;
		}
		
		if (!$connected_account->authenticateHttpRequest($request, $request_options, $actor)) {
			$error = 'authentication: failed to authenticate the request.';
			return false;
		}
		
		return true;
	}
}

class _DevblocksLlmService {
	static ?_DevblocksLlmService $instance = null;
	
	private function __construct() {
		// We lazy load the connections
	}
	
	static function getInstance() : _DevblocksLlmService {
		if(null == self::$instance)
			self::$instance = new _DevblocksLlmService();
		
		return self::$instance;
	}
	
	// The cerb-icons name for a provider id (its brand logo, or `bot` for providers without one / unknown ids).
	function getProviderIcon(string $provider_id) : string {
		return $this->getProvider($provider_id, [], false)?->getIcon() ?? 'bot';
	}

	function getProviderIconColor(string $provider_id) : string {
		return $this->getProvider($provider_id, [], false)?->getIconColor() ?? '';
	}

	/**
	 * @return string[] Every known provider id (the getProvider() registry keys).
	 */
	function getProviderIds() : array {
		return [
			'anthropic',
			'aws_bedrock',
			'docker',
			'gemini',
			'groq',
			'huggingface',
			'ollama',
			'openai',
			'pinecone',
			'qwen',
			'together',
			'voyage',
			'zai',
		];
	}

	/**
	 * Chat-capable providers with their brand icon, for pickers (e.g. forking a
	 * transcript onto another provider). Instantiated with validate:false — no
	 * credentials needed just to enumerate.
	 *
	 * @return array<string,array{id:string,icon:string}>
	 */
	function getChatProviders() : array {
		$out = [];

		foreach($this->getProviderIds() as $id) {
			try {
				$provider = $this->getProvider($id, [], false);
			} catch(\Throwable $e) {
				continue;
			}

			if($provider instanceof \Cerb\LLM\Providers\Interfaces\Chat)
				$out[$id] = ['id' => $id, 'icon' => $provider->getIcon()];
		}

		return $out;
	}

	/**
	 * Build the KATA autocomplete for an `llm:<provider>:` params block, looped over the chat providers
	 * and re-keyed under $prefix (which must end in `:` — e.g. `(.*):llm.agent:inputs:llm:` or
	 * `(.*):await:form:elements:agentPrompt:models:(.*?):`). Each provider's block (model/auth/knobs +
	 * value lists) comes from its own capability method (getChatKataAutocomplete /
	 * getEmbeddingKataAutocomplete), so the lists live in ONE place. Optional
	 * $extra_keys append to every provider block, and $extra_values add per-provider value sub-paths
	 * (both used by the agentPrompt catalog for vision/context_window/compaction/disabled).
	 *
	 * $mode ('chat'|'embedding') selects the capability interface + which of the provider's two
	 * contributions to use (Chat::getChatKataAutocomplete() or Embedding::getEmbeddingKataAutocomplete()).
	 *
	 * Emission order matters: value/knob sub-paths (most specific) precede the block, and the provider
	 * LIST (least specific) is last — so a greedy shorter pattern never shadows a deeper value path.
	 */
	function getKataProviderAutocomplete(string $prefix, string $mode = 'chat', array $extra_keys = [], array $extra_values = []) : array {
		$is_embedding = ('embedding' === $mode);
		$interface = $is_embedding
			? \Cerb\LLM\Providers\Interfaces\Embedding::class
			: \Cerb\LLM\Providers\Interfaces\Chat::class;

		$out = [];
		$provider_list = [];

		foreach($this->getProviderIds() as $provider_id) {
			try {
				$provider = $this->getProvider($provider_id, [], false);
			} catch(\Throwable $e) {
				continue;
			}

			if(!($provider instanceof $interface))
				continue;

			$provider_list[] = $provider_id . ':';
			$base = $prefix . $provider_id . ':';
			$block = $is_embedding ? $provider->getEmbeddingKataAutocomplete() : $provider->getChatKataAutocomplete();

			// Value/knob sub-paths first (most specific), then the caller's extra value sub-paths.
			foreach(($block['values'] ?? []) as $subpath => $suggestions)
				$out[$base . $subpath] = $suggestions;
			foreach($extra_values as $subpath => $suggestions)
				$out[$base . $subpath] = $suggestions;

			// The block keys (+ any caller extras like vision/context_window/compaction/disabled).
			$out[$base] = array_merge($block['keys'] ?? [], $extra_keys);
		}

		// The provider list (least specific) last.
		$out[$prefix] = $provider_list;

		return $out;
	}

	/**
	 * KATA autocomplete for `llm.router:inputs:router:` — the configured router NAMES, as plain values.
	 *
	 * Bare names rather than `cerb:agent_model_router:<name>` URIs on purpose: `router:` only ever points at
	 * one record type, so the URI prefix disambiguates nothing and is pure ceremony to type. (The command
	 * still ACCEPTS a URI — see LlmRouterAction::_resolveRouter — it just isn't what we suggest.)
	 *
	 * The default router is listed first and labelled, since omitting `router:` entirely resolves to it — which
	 * is what portable automations should do.
	 *
	 * Rebuilt server-side per editor load, so a newly created router shows up on reload. Disabled routers are
	 * omitted: they can't be resolved.
	 *
	 * @return array a flat list of suggestion items (assign it to the `…:router:` path)
	 */
	function getKataAgentModelRouterAutocomplete() : array {
		$out = [];

		foreach(\DAO_AgentModelRouter::getAll() as $router) {
			if($router->is_disabled)
				continue;

			$out[] = [
				'caption' => $router->name,
				'snippet' => $router->name,
				'score' => $router->is_default ? 2000 : 1000,
				'docHTML' => sprintf('<b>%s</b>%s%s',
					htmlspecialchars($router->getDisplayName()),
					$router->is_default ? ' &mdash; <b>the default</b>. Omit <code>router:</code> entirely to use it.' : '',
					('' !== trim(strval($router->description))) ? '<br>' . htmlspecialchars($router->description) : ''
				),
			];
		}

		return $out;
	}


	/**
	 * The DEFAULT router's model map — the implicit fall-through when a command names no models and no agent.
	 *
	 * This is what makes the 90% case zero-config: one model + an API key, and every `llm.agent:` /
	 * `llm.chat:` / `agentPrompt` in the environment finds it without naming anything. Without it, portable
	 * automations would each need an `llm.router:` node just to say "use this environment's models."
	 *
	 * Returns `{}` (never throws) when there's no default router or it resolves nothing — the CALLER decides
	 * whether that's an error, because each surface already has its own "nothing to run" message.
	 *
	 * @param DevblocksDictionaryDelegate|null $dict evaluates `disabled@bool: {{…}}` in the router's document
	 * @return array `{<key> => <overrides>}` in the shape `model:` / `models:` accept
	 */
	function getDefaultRouterModels(?DevblocksDictionaryDelegate $dict=null, ?string &$error=null) : array {
		if(!($router = \DAO_AgentModelRouter::getDefault()))
			return [];

		return $router->getModels($dict, $error);
	}

	/**
	 * The models an AGENT offers — its own router, else the default. The middle rung of the precedence chain:
	 *
	 *     explicit `models:`/`model:`  →  agent's router  →  the default router  →  error
	 *
	 * Naming an agent is what makes a SHIPPED automation portable: the agent is the customer's record, so it
	 * carries the customer's router, which carries the customer's models. Nothing in the script names any of them.
	 *
	 * An agent with no router configured falls through to the default rather than erroring — an agent is a
	 * persona first, and not every one needs its own model policy.
	 *
	 * @param int $worker_id an AI worker (`worker.is_ai`); 0 → the default router
	 * @return array `{<key> => <overrides>}`; empty when nothing resolves
	 */
	function getAgentRouterModels(int $worker_id, ?DevblocksDictionaryDelegate $dict=null, ?string &$error=null) : array {
		if($worker_id && ($router_id = \DAO_Agent::getModelRouterId($worker_id))) {
			if(($router = \DAO_AgentModelRouter::get($router_id)) && !$router->is_disabled)
				return $router->getModels($dict, $error);

			// A router that's been deleted or disabled since it was assigned: fall through to the default
			// rather than failing. The alternative is an agent that silently stops working when an admin
			// retires a router it happened to point at.
		}

		return $this->getDefaultRouterModels($dict, $error);
	}

	/**
	 * KATA autocomplete for an `agent:` reference — the AI workers, by `@mention`.
	 *
	 * Only `is_ai` and enabled workers: a human is refused at resolve time anyway (running as one would
	 * misattribute memory), so offering them would suggest something that can't work.
	 *
	 * Suggests the `@mention` form because that's the portable one — a worker id differs per environment. All
	 * the accepted shapes (`@mention` | bare handle | id | `cerb:worker:<id|mention>`) still resolve; this is
	 * just what we put in front of an author.
	 *
	 * @return array a flat list of suggestion items (assign it to the `…:agent:` path)
	 */
	function getKataAgentWorkerAutocomplete() : array {
		$out = [];

		foreach(\DAO_Worker::getAllActive() as $worker) {
			if(!$worker->is_ai)
				continue;

			if('' === ($mention = trim(strval($worker->at_mention_name))))
				continue;

			$router_name = $this->getResolvedRouterName(intval($worker->id));

			$out[] = [
				'caption' => '@' . $mention,
				'snippet' => '@' . $mention,
				'score' => 1000,
				'docHTML' => sprintf('<b>%s</b>%s',
					htmlspecialchars($worker->getName()),
					$router_name
						? sprintf(' &mdash; models via the <code>%s</code> router.', htmlspecialchars($router_name))
						: ' &mdash; no model router resolves; this agent would have no models.'
				),
			];
		}

		return $out;
	}

	/**
	 * Which router `getAgentRouterModels()` would use — for reporting only (the `router` key in a node's
	 * output). Same fall-through, so what's reported is what ran.
	 */
	function getResolvedRouterName(int $worker_id) : string {
		if($worker_id && ($router_id = \DAO_Agent::getModelRouterId($worker_id))) {
			if(($router = \DAO_AgentModelRouter::get($router_id)) && !$router->is_disabled)
				return strval($router->name);
		}

		return ($router = \DAO_AgentModelRouter::getDefault()) ? strval($router->name) : '';
	}

	// Resolve a message's `images:` descriptors into neutral image blocks [{mime_type, data(base64)}]. Each
	// descriptor is {mime_type?, data?, uri?}: an inline base64 `data` is used verbatim; a `cerb:attachment:<id>`
	// (durable, transcript-owned) or `cerb:automation_resource:<token>` (short TTL; bare token accepted) `uri` is
	// expanded to base64 NOW (+ mime_type from the record). Non-image / unresolvable / empty entries are skipped.
	// Accepts a map (image/0, image/1, …) or a list.
	function resolveImageDescriptors(array $images) : array {
		$out = [];

		foreach($images as $image) {
			// Accept a bare uri/token string (agentPrompt posts these — mime + validation come from the
			// resource, not the client) or a descriptor {mime_type?, data?, uri?} (hand-authored KATA).
			if(is_string($image)) {
				$mime_type = '';
				$data = '';
				$uri = $image;
			} elseif(is_array($image)) {
				$mime_type = strval($image['mime_type'] ?? '');
				$data = strval($image['data'] ?? '');
				$uri = strval($image['uri'] ?? '');
			} else {
				continue;
			}

			// Expand a cerb: uri → base64 (+ mime_type from the record). Two schemes: a durable
			// `cerb:attachment:<id>` (agentPrompt uploads — transcript-owned, no TTL) or a legacy/hand-authored
			// `cerb:automation_resource:<token>` (short TTL). A bare string is treated as a resource token.
			if('' === $data && '' !== $uri) {
				$bytes = null;
				$resolved_mime = '';

				if(DevblocksPlatform::strStartsWith($uri, 'cerb:attachment:')) {
					if(($attachment = \DAO_Attachment::get(intval(substr($uri, strlen('cerb:attachment:')))))) {
						$bytes = $attachment->getFileContents();
						$resolved_mime = strval($attachment->mime_type);
					}
				} else {
					$token = DevblocksPlatform::strStartsWith($uri, 'cerb:automation_resource:')
						? substr($uri, strlen('cerb:automation_resource:'))
						: $uri;

					if(($resource = \DAO_AutomationResource::getByToken($token))) {
						$bytes = $resource->getFileContents();
						$resolved_mime = strval($resource->mime_type);
					}
				}

				if(is_resource($bytes)) {
					$buf = '';
					while(!feof($bytes))
						$buf .= fread($bytes, 8192);
					$bytes = $buf;
				}

				if(is_string($bytes) && '' !== $bytes) {
					$data = base64_encode($bytes);

					if('' === $mime_type)
						$mime_type = $resolved_mime;
				}
			}

			// Only accept image mime types that resolved to data
			if('' === $data || !DevblocksPlatform::strStartsWith(DevblocksPlatform::strLower($mime_type), 'image/'))
				continue;

			$out[] = ['mime_type' => $mime_type, 'data' => $data];
		}

		return $out;
	}

	// Prepare one inbound message for storage/send: DROP its `images:` when the provider's model lacks vision,
	// else keep the descriptors AS-IS — cerb: resource uris, NOT base64 (we store uris in history/continuations
	// and expand to base64 only at send, in expandMessageImages). Normalizes a map (image/0, …) to a list.
	// Shared by `llm.agent` (before appendMessage) and `llm.chat` (before chatCompletion).
	function normalizeMessageImages(array $message, Extension_DevblocksLlmProvider $provider) : array {
		// Empty/invalid `images` → strip the key (a stray `images: []` must never reach the provider API).
		if(!is_array($message['images'] ?? null) || !$message['images']) {
			unset($message['images']);
			return $message;
		}

		if(!$provider->supportsVision()) {
			unset($message['images']);
			return $message;
		}

		$message['images'] = array_values($message['images']);

		return $message;
	}

	function getProvider(string $provider_id, array $params=[], bool $validate=true) : ?Extension_DevblocksLlmProvider {
		return match($provider_id) {
			'anthropic' => new Cerb\LLM\Providers\Anthropic($params, $validate),
			'aws_bedrock' => new Cerb\LLM\Providers\AwsBedrock($params, $validate),
			'docker' => new Cerb\LLM\Providers\Docker($params, $validate),
			'gemini' => new Cerb\LLM\Providers\Gemini($params, $validate),
			'groq' => new Cerb\LLM\Providers\Groq($params, $validate),
			'huggingface' => new Cerb\LLM\Providers\HuggingFace($params, $validate),
			'ollama' => new Cerb\LLM\Providers\Ollama($params, $validate),
			'openai' => new Cerb\LLM\Providers\OpenAI($params, $validate),
			'pinecone' => new Cerb\LLM\Providers\Pinecone($params, $validate),
			'together' => new Cerb\LLM\Providers\TogetherAI($params, $validate),
			'voyage' => new Cerb\LLM\Providers\VoyageAI($params, $validate),
			'qwen' => new Cerb\LLM\Providers\Qwen($params, $validate),
			'zai' => new Cerb\LLM\Providers\ZAi($params, $validate),
			default => null,
		};
	}
	
	function getMemoryStore(string $session_id) : Extension_DevblocksLlmMemoryStore {
		return new DatabaseHistory($session_id);
	}
	
	function getToolSchemaForAutomation(string $tool_name, array $tool, string $schema_key='parameters') : ?array {
		if(!array_key_exists('uri', $tool))
			return null;
		
		if(!($tool_automation = DAO_Automation::getByUri($tool['uri'], \AutomationTrigger_LlmTool::ID)))
			return null;
		
		// [TODO] Cache the tool inputs per automation
		$tool_dict = DevblocksDictionaryDelegate::getDictionaryFromModel($tool_automation, CerberusContexts::CONTEXT_AUTOMATION, ['inputs']);
		
		// [TODO] strict mode
		
		$automation_inputs = $tool_dict->get('inputs', []);
		
		$tool_schema = [
			'type' => 'function',
			'function' => [
				'name' => $tool_name,
				'description' => $tool_automation->description ?? '',
				$schema_key => [
					'type' => 'object',
					'properties' => (object)[],
				],
			]
		];
		
		if($automation_inputs) {
			$tool_schema['function'][$schema_key]['properties'] = [];
			$tool_schema['function'][$schema_key]['required'] = [];
			
			foreach($automation_inputs as $automation_input) {
				$tool_property = [
					// [TODO] `type`
					'type' => 'string',
					'description' => $automation_input['description'] ?? '',
				];
				
				// [TODO] Validate
				if($automation_input['allowed_values'] ?? null && is_array($automation_input['allowed_values']))
					$tool_property['enum'] = $automation_input['allowed_values'];
				
				$tool_schema['function'][$schema_key]['properties'][$automation_input['key']] = $tool_property;
				
				if($automation_input['required'] ?? false)
					$tool_schema['function'][$schema_key]['required'][] = $automation_input['key'];
			}
		}
		
		return $tool_schema;
	}
		// Append the inbound messages to the managed history first, so the send-list build sees them. Resolve/drop
		// `images:` per the model's vision support so the STORED message carries only supportable blocks.
		foreach($messages as $new_message) {
			if(is_array($new_message))
				$new_message = $this->normalizeMessageImages($new_message, $provider);

			$memory_store->appendMessage($new_message);
		}


	/**
	 * Close out a streamed turn whose call didn't return: sanitize what arrived, then either keep it as a
	 * truncated turn or drop it entirely.
	 *
	 * Salvage matters because the failure modes here are expensive — a ten-minute generation cut at the last
	 * second was still billed in full — and because a row left open would be read as an in-flight turn forever.
	 * Sanitizing is not optional politeness: an unsignatured thinking block or an argument-less tool call left
	 * in the history gets the NEXT request rejected, turning one lost turn into a permanently stranded session.
	 */
	/**
	 * Has a Stop been raised for this session? Read on every throttled flush, which is the only moment we
	 * reliably hold control during a call that may run for minutes — before streaming, a Stop could only be
	 * honored between turns.
	 *
	 * DELIBERATELY NON-DESTRUCTIVE. `LlmAgentNode::_consumeInterrupt()` CONSUMES this flag (it removes the key)
	 * at its own tree-safe boundary, where it unwinds the node's stack and hands control back to the
	 * automation. If this read consumed it too, whichever ran first would silently rob the other: the stream
	 * would abort but the node would never learn a Stop happened, and the loop would just start another turn.
	 */
	private function _isTurnInterrupted(string $session_id) : bool {
		if('' === $session_id)
			return false;

		return boolval(DevblocksPlatform::services()->cache()->load(
			\Cerb\AutomationBuilder\Node\LlmAgentNode::interruptCacheKey($session_id),
			true
		));
	}

	private function _salvageStreamedTurn(Extension_DevblocksLlmProvider $provider, Extension_DevblocksLlmMemoryStore $memory_store) : void {
		if(null === ($uuid = $memory_store->getOpenStreamingMessage()))
			return;

		if(!($provider instanceof \Cerb\LLM\Providers\Interfaces\ChatStreaming)) {
			$memory_store->discardStreamingMessage($uuid);
			return;
		}

		$partial = $provider->sanitizePartialContent($provider->getStreamedPartial() ?? []);

		// An EMPTY assistant head is worse than none — the guards downstream read the head's role and would
		// treat it as a real turn that simply said nothing.
		if(!Extension_DevblocksLlmProvider::hasReplayableContent($partial)) {
			$memory_store->discardStreamingMessage($uuid);
			return;
		}

		$memory_store->finalizeStreamingMessage(
			$uuid,
			$partial,
			null,
			Extension_DevblocksLlmProvider::FINISH_REASON_INTERRUPTED
		);
	}


	/**
	 * The provider tool schemas for a session, built from its stored `tools` (the authored map) + `mounts`
	 * (resolved agent-filesystem specs → the synthesized `agent_fs` tool). Session-only: `llm.agent` persists
	 * both onto the session before a turn, so this matches what the node used to build inline from `inputs`.
	 */
	function getSessionToolSchemas(Model_LlmAgentSession $session) : array {
		$schemas = [];

		foreach($this->_sessionToolMap($session) as $tool_name => $tool) {
			$schema = match($tool['type'] ?? null) {
				'automation' => $this->getToolSchemaForAutomation($tool_name, $tool),
				'tool' => $this->_toolSchemaCustom($tool_name, $tool),
				'agent_fs' => $this->_toolSchemaAgentFs($tool_name, $tool),
				default => null,
			};

			if($schema)
				$schemas[$tool_name] = $schema;
		}

		return $schemas;
	}

	// Normalize the session's stored `tools` (`<type>/<name>` keys, skip disabled) into a name→descriptor map,
	// then synthesize the shared `agent_fs` tool from the stored mounts (an author tool of that name wins).
	private function _sessionToolMap(Model_LlmAgentSession $session) : array {
		$tools = [];

		foreach(($session->tools ?? []) as $tool_key => $tool) {
			if(!is_array($tool))
				continue;

			list($tool_type, $tool_name) = array_pad(explode('/', strval($tool_key)), 2, null);

			if(empty($tool_name))
				$tool_name = $tool_type;

			if(array_key_exists('disabled', $tool) && $tool['disabled'])
				continue;

			$tool['type'] = $tool_type;
			$tools[$tool_name] = $tool;
		}

		$fs_name = \Cerb\AutomationBuilder\Node\LlmAgentNode::TOOL_FS;

		// `[]` is enabled-with-no-volumes (a /tmp-only filesystem); only NULL means the session never had one.
		if(!is_null($session->mounts) && !array_key_exists($fs_name, $tools)) {
			$tools[$fs_name] = [
				'type' => 'agent_fs',
				'mounts' => $session->mounts,
			];
		}

		return $tools;
	}

	// A custom (`tool/`) tool's schema — one object of `string` params. Ported verbatim from LlmAgentNode.
	private function _toolSchemaCustom(string $tool_name, array $tool) : ?array {
		$tool_schema = [
			'type' => 'function',
			'function' => [
				'name' => $tool_name,
				'description' => $tool['description'] ?? '',
				'parameters' => [
					'type' => 'object',
					'properties' => (object)[],
				],
			]
		];

		if(array_key_exists('parameters', $tool) && is_array($tool['parameters'])) {
			$tool_schema['function']['parameters']['properties'] = [];
			$tool_schema['function']['parameters']['required'] = [];

			foreach($tool['parameters'] as $param_key => $parameter) {
				list($param_type, $param_name) = array_pad(explode('/', $param_key, 2), 2, null);

				if(!$param_name)
					$param_name = $param_type;

				// The provider echoes arguments back keyed by this name, and the tool's `labels:` are rendered
				// against them (`DevblocksLlmToolCall::getLabels()` — `{{query}}` reflects what the agent asked
				// for). A dash is wire-legal for both Anthropic and OpenAI but unlexable in Twig, so drop the
				// parameter rather than ship one whose label can never read it.
				if(!_DevblocksKataService::isVariableName($param_name))
					continue;

				if('string' == $param_type) {
					$tool_schema['function']['parameters']['properties'][$param_name] = [
						'type' => 'string',
						'description' => $parameter['description'] ?? '',
					];

					if(array_key_exists('enum', $parameter) && is_array($parameter['enum']))
						$tool_schema['function']['parameters']['properties'][$param_name]['enum'] = $parameter['enum'];

					if($parameter['required'] ?? false)
						$tool_schema['function']['parameters']['required'][] = $param_name;
				}
			}
		}

		return $tool_schema;
	}

	/**
	 * The synthesized agent-filesystem tool: a `command` line + an optional out-of-band `script`. The
	 * description carries the command vocabulary + the mount overview (a shallow `ls` per volume), so it is
	 * BUILT ONCE PER TURN and must be byte-identical across turns for a fixed mount set (or the cached prompt
	 * prefix breaks). Lists only the mounted VOLUMES — never `/tmp`, whose contents change. Ported verbatim
	 * from LlmAgentNode.
	 */
	private function _toolSchemaAgentFs(string $tool_name, array $tool) : ?array {
		$mounts = $tool['mounts'] ?? [];

		if(!is_array($mounts))
			return null;

		// No `tmp` store here: the overview is the CACHED description, and /tmp is dynamic. The runtime call
		// supplies the store so /tmp exists when a command actually runs.
		$fs = \Cerb\Agent\Filesystem::fromSpecs($mounts);
		$resolved = $fs->getMounts();

		if($resolved) {
			$overview = ["Mounted filesystems:"];

			foreach($resolved as $mount) {
				// The mode belongs here as much as the name: it's what says whether you can write to this volume,
				// and a failed write is a wasted turn.
				$overview[] = sprintf("\n%s  (%s, %s)%s",
					$mount['at'],
					$mount['fs']->name,
					$mount['mode'],
					$mount['fs']->description ? ' -- ' . $mount['fs']->description : ''
				);

				$listing = $fs->exec(sprintf('ls "%s"', $mount['at']));
				$overview[] = rtrim($listing['output'] ?? '');
			}

			$lead = [
				"Browse the agent filesystems mounted below. Give one command line exactly as you would type it in a terminal.",
				"There is no working directory: use absolute paths (`/skills/cerb-dev/SKILL.md`) or `@<filesystem>/path`.",
				"Prefer `search`/`find` to locate a file, then `read` only what you need. `/tmp` is a scratch area you",
				"can write to; a command whose output is too large to return is saved there and referenced by path.",
			];

		} else {
			// No volumes: `/tmp` alone, which is still worth having — it's a scratch pad plus the `|` pipeline,
			// so the agent can hold and transform arbitrary text without spending context on it.
			$overview = ["No volumes are mounted. `/tmp` is your whole filesystem: write text there, then read,"
				. "\nlist, or transform it with a `|` pipeline."];

			$lead = [
				"A scratch filesystem. Give one command line exactly as you would type it in a terminal.",
				"There is no working directory: use absolute paths (`/tmp/notes.md`).",
				"Write text to `/tmp` and it stays out of this conversation until you read it back — so it's the place",
				"to park a long intermediate result, then narrow it with a `|` pipeline instead of re-reading the whole",
				"thing. A command whose output is too large to return is saved there and referenced by path.",
			];
		}

		// `search` needs a fulltext index, which only a volume has — don't advertise it over /tmp alone.
		$verbs = $resolved
			? ['ls', 'find', 'search', 'read', 'write', 'append', 'edit', 'copy', 'rm', '|', '/tmp']
			: ['ls', 'find', 'read', 'write', 'append', 'edit', 'copy', 'rm', '|', '/tmp'];

		$description = implode("\n", [
			...$lead,
			"For a longer transform than fits on one line, put a Twig template in `script` instead of a trailing `|`.",
			'',
			\Cerb\Agent\Filesystem::help(null, $verbs),
			'',
			implode("\n", $overview),
		]);

		return [
			'type' => 'function',
			'function' => [
				'name' => $tool_name,
				'description' => $description,
				'parameters' => [
					'type' => 'object',
					'properties' => [
						'command' => [
							'type' => 'string',
							'description' => "The command line to run, e.g. `ls /skills`, `search prompt caching --ext md`, `find *.md --fields title`, `read @cerb-dev/SKILL.md --offset 40 --limit 60`, `write /me/notes.md`, or `edit /me/notes.md`. May end with a `| <twig filters>` pipeline.",
						],
						'script' => [
							'type' => 'string',
							'description' => "Optional. A Twig template applied to the command's output instead of a trailing `|` pipeline — use it for a multi-line transform. Sees `output`, `lines`, and (for search/ls/find) `results`/`files`. Don't also use a `|` in the command.",
						],
						'content' => [
							'type' => 'string',
							'description' => "The file body for a `write` or `append` command (read-write mounts only). The WHOLE file — use `edit` to change part of an existing file.",
						],
						'find' => [
							'type' => 'string',
							'description' => "For `edit`: the exact snippet to locate. It must match EXACTLY ONE place in the file (whitespace matters) — if it's ambiguous, include more surrounding lines until it's unique.",
						],
						'replace' => [
							'type' => 'string',
							'description' => "For `edit`: the text that replaces `find`. Empty to delete the snippet.",
						],
					],
					'required' => ['command'],
				],
			],
		];
	}
}
