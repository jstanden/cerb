<?php

use Cerb\LLM\MemoryStore\DatabaseHistory;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

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
	
	/**
	 * `$parameters` accepts an OBJECT as well as an array, because an empty parameter bag has two legitimate
	 * shapes in flight: storage/`json_decode(assoc)` gives `[]`, but the Anthropic/Bedrock wire format requires
	 * `{}` — so `sanitizeMessages()` casts empty inputs to `(object)[]` at send time. Anything that re-reads a
	 * SEND-shaped message (the dev transcript's compact preview does exactly this: sanitize → convert) would
	 * otherwise hand us a stdClass and fatal on the type. Normalize once, here, rather than in every provider's
	 * converter — `getParameters()` keeps returning an array, so the reverse (`toNativeMessage`) is unaffected.
	 */
	function __construct(string $name, array|object $parameters, string $id = '') {
		$this->_id = $id;
		$this->_name = $name;
		$this->_parameters = is_object($parameters) ? (array) $parameters : $parameters;
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
	
	// Summary phrasings for this call, resolved from the session's tool map (Model_LlmAgentSession::getToolMap()).
	// Returns [summary, active]; each backfills the other, and BOTH ARE EMPTY when the tool authored no labels
	// — the renderer owns the fallback, because only it knows what it can say (the transcript component turns
	// an empty summary into "Worked for 340ms" from the measured duration; inventing a generic "Worked" here
	// would shadow that and throw the number away). Built against THIS call's parameters, so `{{query}}`
	// reflects what the agent actually asked for.
	function getLabels(?array $tool_map) : array {
		// A name absent from a map we DO have is a tool that doesn't exist -- most often a model calling a
		// terminal COMMAND as though it were a tool. Falling through to empty labels lets the renderer print
		// "Worked for 340ms" over a call that only ever returned an error, which is the one thing a transcript
		// must not say. Guarded on a present, non-empty map: null or empty means we simply don't know.
		if(is_array($tool_map) && $tool_map && !array_key_exists($this->getName(), $tool_map)) {
			$unknown = sprintf('Unknown tool: %s', $this->getName());

			return ['summary' => $unknown, 'active' => $unknown];
		}

		$labels = $tool_map[$this->getName()]['labels'] ?? [];

		if(!is_array($labels))
			$labels = [];

		$summary = strval($labels['summary'] ?? '');
		$active = strval($labels['active'] ?? '');

		return [
			'summary' => $this->_buildAgainstParams($summary ?: $active),
			'active' => $this->_buildAgainstParams($active ?: $summary),
		];
	}

	// Resolve a tool-map string against THIS call's parameters. A plain string passes through untouched, so
	// the common case costs nothing.
	private function _buildAgainstParams(string $str) : string {
		if(!str_contains($str, '{{'))
			return $str;

		$tpl_builder = DevblocksPlatform::services()->templateBuilder();

		if(false !== ($built = $tpl_builder->build($str, $this->getParameters())))
			return $built;

		return $str;
	}

	/**
	 * The tool's icon from the session's tool map — a cerb-icons name. Empty lets the renderer pick its own
	 * default rather than baking one in here.
	 *
	 * Templated against this call's parameters, exactly like the labels, so ONE tool can wear a different
	 * glyph per invocation — the agent terminal is a filesystem, a search, and a CLI depending on the verb,
	 * and a single folder icon for all three makes a transcript harder to skim than it needs to be.
	 * A template that fails, or yields anything that isn't an icon name, falls back to the renderer's default
	 * rather than emitting a broken class.
	 */
	function getIcon(?array $tool_map) : string {
		$icon = trim($this->_buildAgainstParams(strval($tool_map[$this->getName()]['icon'] ?? '')));

		return preg_match('/^[a-z0-9-]+$/', $icon) ? $icon : '';
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
	
	// Provider-neutral token usage for the turn: {input, output, reasoning, cache_read, cache_write} (input =
	// fresh/uncached prompt tokens; cache_write is 0 on providers that don't report it). `reasoning` is a
	// BREAKDOWN of `output`, not an addition to it, so a total is `input + cache_read + cache_write + output`
	// with reasoning left out; only the OpenAI family reports the split, so 0 means "unreported" rather than
	// "none". Set by each provider's chatCompletion from its native `usage` block; persisted on the assistant
	// message (usage_json).
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
	 * The level this provider will ACTUALLY put on the wire for a turn of the given shape, or null for none.
	 *
	 * Normally the same as getEffort(), and that stays the default. It's a separate question because a
	 * provider may reconcile the author's level against the turn before sending it -- OpenAI's
	 * /v1/chat/completions forces `none` whenever tools are present (see _applyToolReasoningGuardrail), so a
	 * tool-using session reasons at a level nobody configured and no stored artifact records.
	 *
	 * Read-only and side-effect free, for a reader (the transcript header) that needs to report what ran
	 * rather than what was asked for.
	 */
	function getEffectiveEffort(bool $has_tools) : ?string {
		return $this->getEffort();
	}

	/**
	 * Which wire format this provider's configuration talks, or '' when the provider has only one.
	 *
	 * Exists because ONE provider id can front more than one API. `openai` serves both `/v1/chat/completions`
	 * and `/v1/responses`, and which one a record uses is resolved from its endpoint rather than chosen from a
	 * menu -- the two are different dialects on the wire and in storage, so anything that has to notice a
	 * capability change (see _capabilitySignature) needs to be able to ask.
	 */
	function getApiSurface() : string {
		return '';
	}

	/**
	 * Does this provider actually offer an embeddings endpoint?
	 *
	 * Normally the same question as `instanceof Embedding`, and that stays the default. It needs its own
	 * predicate because the OpenAI-compatible family inherits the interface along with the dialect: Groq
	 * speaks OpenAI chat completions but ships no embeddings API, so a structural check would offer it in
	 * `llm.embed:` autocomplete and then 404 at run time — a worse failure than not offering it.
	 */
	function supportsEmbeddings() : bool {
		return $this instanceof \Cerb\LLM\Providers\Interfaces\Embedding;
	}

	/**
	 * Does this provider forward the canonical `effort:` to its API at all?
	 *
	 * A PROVIDER-level question, not a model-level one: it asks whether there is a wire param to put the
	 * level in, not whether the model behind it reasons. Which of getEffortLevels() a given model accepts
	 * stays the API's call (see getEffort) -- a chat provider that forwards effort answers true even when
	 * most of its catalog ignores the param.
	 *
	 * Every chat provider forwards it, so `instanceof Chat` is the default; an embeddings-only provider is
	 * false for free. Kept as a predicate rather than a structural check for the same reason as
	 * supportsEmbeddings(): the OpenAI-compatible family inherits the dialect, so a subclass that genuinely
	 * has nowhere to put the level needs a way to declare that.
	 */
	function supportsReasoning() : bool {
		return $this instanceof \Cerb\LLM\Providers\Interfaces\Chat;
	}

	/**
	 * The reasoning levels this provider DOCUMENTS, shallowest first -- the vocabulary, not a whitelist.
	 *
	 * Nothing validates against this: `getEffort()` still passes the author's level verbatim and the API is
	 * still the authority (levels vary by model and version, and a new release has to work the day it ships).
	 * It exists so the UI can OFFER levels for a model whose entry never hand-declared `effort_choices`.
	 * Without it the agentPrompt picker has nothing to show and silently sends no effort at all.
	 *
	 * Base returns [] = "no vocabulary to advertise"; chat providers override with their own set.
	 */
	function getEffortLevels() : array {
		return [];
	}

	/**
	 * Map a reasoning level -> a literal `budget_tokens` value, clamped so it's >=1024 and < max_tokens.
	 *
	 * For the legacy Anthropic-family shape (`thinking: {type: enabled}`) that predates a named effort scale
	 * and wants a token budget instead. Returns null when max_tokens can't fit a valid budget -- skip legacy
	 * thinking rather than send a request the API will reject.
	 *
	 * Lives on the base because Anthropic and AwsBedrock speak the same dialect and MUST agree: the two had
	 * drifted apart before, kept in step only by a "keep the table in sync" comment on each copy.
	 */
	protected function _effortToBudget(string $effort, int $max_tokens) : ?int {
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

	// ---------------------------------------------------------------------------------------------
	// Streaming. The state and the control flow are shared; only the EVENT GRAMMAR is per-provider.
	//
	// A provider opts in by implementing Cerb\LLM\Providers\Interfaces\ChatStreaming, which the two
	// public methods below already satisfy, then overriding _streamAccumulator() (its grammar),
	// _parseNonStreamedBody() (the non-SSE fallback's shape test), _streamErrorStatus() (its error
	// map) and sanitizePartialContent() (its structural salvage rules).
	// ---------------------------------------------------------------------------------------------

	// Set by enableStreaming(); consumed and reset by the next chatCompletion().
	protected bool $_streaming = false;
	protected $_stream_progress = null;

	// The last streamed turn's accumulated `['role'=>…,'content'=>…]`, readable after a failure or an
	// abort so the caller can salvage a partial rather than lose the whole turn.
	protected ?array $_streamed_partial = null;

	function enableStreaming(?callable $on_progress = null) : void {
		$this->_streaming = true;
		$this->_stream_progress = $on_progress;
		$this->_streamed_partial = null;
	}

	function getStreamedPartial() : ?array {
		return $this->_streamed_partial;
	}

	// Is a streamed turn armed? Read while BUILDING the request (a provider that signals streaming in
	// its body, like Anthropic's `stream` field, needs to know before _consumeStreamingFlag() runs).
	protected function _isStreamingTurn() : bool {
		return $this->_streaming;
	}

	/**
	 * Take the one-shot streaming flag and hand back its progress callback.
	 *
	 * CALL THIS BEFORE ANYTHING THAT CAN THROW. The flag is deliberately one-shot: if a streamed turn
	 * fails with the flag still set, the provider stays silently primed and streams the NEXT turn too
	 * — a turn whose caller never opened a streaming row to receive it.
	 */
	protected function _consumeStreamingFlag() : ?callable {
		$on_progress = $this->_stream_progress;

		$this->_streaming = false;
		$this->_stream_progress = null;

		return $on_progress;
	}

	/**
	 * Author-facing `stream@bool:`. Streaming is ON by default wherever the provider implements it;
	 * this exists so a non-conforming OpenAI-compatible endpoint (a proxy that buffers, a local server
	 * that ignores `stream`) can be pinned to the blocking path without a code change.
	 */
	function isStreamingEnabled() : bool {
		$v = $this->getParam('stream');

		if(is_null($v))
			return true;

		return DevblocksPlatform::services()->string()->toBool($v);
	}

	/**
	 * The per-provider event grammar, as a bundle of closures over ONE accumulator's private state.
	 * Closures rather than a state array because the state shape is entirely the provider's business
	 * and never needs to be read from out here.
	 *
	 * @return array{
	 *   on_event: callable(string,string):void,  Consume one SSE frame (event name may be '').
	 *   snapshot: callable():array,              The turn SO FAR, in the shape that gets persisted.
	 *   usage:    callable():array,              Native usage accumulated so far (may be empty).
	 *   assemble: callable():array,              The finished `$response_json` the blocking path parses.
	 *   error:    callable():?array,             An in-stream API error frame, or null. `{type,message}`.
	 * }
	 */
	protected function _streamAccumulator() : array {
		return [
			'on_event' => function(string $event, string $data) : void {},
			'snapshot' => fn() : array => [],
			'usage' => fn() : array => [],
			'assemble' => fn() : array => [],
			'error' => fn() : ?array => null,
		];
	}

	/**
	 * The `$saw_event` fallback's shape test: is this decoded body an ORDINARY (non-streamed) response
	 * from this provider? Returning it degrades cleanly to the blocking parse; null means we don't
	 * recognize it and the caller throws.
	 */
	protected function _parseNonStreamedBody(array $body) : ?array {
		return null;
	}

	/**
	 * Map an in-stream error's `type` back onto the HTTP status it would have carried in a non-streamed
	 * response, so the caller's retryable-vs-terminal classification keeps working even though the
	 * transport returned 200. Unknown types should stay retryable (500).
	 */
	protected static function _streamErrorStatus(string $type) : int {
		return 500;
	}

	/**
	 * The transport. Overridable because not every streaming provider speaks Server-Sent Events —
	 * Bedrock's ConverseStream returns `application/vnd.amazon.eventstream` binary frames and needs a
	 * different sink handing the SAME `fn(string $event, string $data) : bool` contract upward.
	 * See PLANS/PLAN-llm-bedrock-streaming.md.
	 */
	protected function _sendStream(Request $request, array $request_options, callable $on_event, &$error, &$aborted) {
		return DevblocksPlatform::services()->http()->sendStreamingRequest(
			$request, $request_options, $on_event, $error, $aborted
		);
	}

	/**
	 * Apply the streaming timeout profile to a request's options.
	 *
	 * The absolute ceiling MUST be explicit: omitting `timeout` doesn't mean "unbounded", it means the
	 * HTTP service injects its 30s default and guillotines a healthy stream mid-generation.
	 *
	 * The real control is aborting on INACTIVITY rather than elapsed time. Measured worst-case gap
	 * between chunks during extended thinking is ~6.5s, so the default leaves a wide margin. Note curl
	 * treats this as an average-speed window rather than a silence timer, so the effective cutoff lands
	 * somewhat later than the configured value — it's a floor, not a deadline.
	 */
	protected function _applyStreamTimeouts(array $request_options) : array {
		if(!array_key_exists('timeout', $request_options))
			$request_options['timeout'] = 900;

		$request_options['curl'] = ($request_options['curl'] ?? []) + [
			CURLOPT_LOW_SPEED_LIMIT => 1,
			CURLOPT_LOW_SPEED_TIME => max(
				$this->_minStreamStallSecs(),
				intval($this->getParam('stream_stall_secs', 60))
			),
		];

		return $request_options;
	}

	/**
	 * The shortest silence this provider's transport can treat as "stuck" without false-positiving on a
	 * healthy turn. A FLOOR, not a default: the async worker passes an explicit `stream_stall_secs`, so a
	 * provider that needs more headroom cannot express it any other way.
	 *
	 * It differs per provider because it is a property of the TRANSPORT, not the model. Anthropic's SSE
	 * emits `ping` frames while the model is thinking, so silence really does mean stuck and a few seconds
	 * is plenty. A transport with NO keepalive is silent for the entire time-to-first-token -- and
	 * `CURLOPT_LOW_SPEED_TIME` measures from the start of the transfer, so that silence spends the whole
	 * budget before a single byte arrives.
	 */
	protected function _minStreamStallSecs() : int {
		return 5;
	}

	/**
	 * Run the turn as a stream and rebuild the SAME `$response_json` array the blocking path parses, so
	 * everything downstream — usage mapping, appendMessage(), convertToGenericMessage() — stays on one
	 * code path. Adding a second parser per provider is how the two would silently drift.
	 *
	 * @throws Exception_DevblocksLlmApiError
	 */
	protected function _streamTurn(Request $request, array $request_options, ?callable $on_progress) : array {
		$http = DevblocksPlatform::services()->http();

		$request_options = $this->_applyStreamTimeouts($request_options);

		$acc = $this->_streamAccumulator();

		$aborted_by_caller = false;

		// Did we receive ANY parseable frame? Distinguishes "the response wasn't a stream" from "the
		// stream was legitimately empty" — see the fallback at the end.
		$saw_event = false;

		$on_event = function(string $event, string $data) use ($acc, &$aborted_by_caller, &$saw_event, $on_progress) : bool {
			$saw_event = true;

			($acc['on_event'])($event, $data);

			if($on_progress && false === $on_progress(($acc['snapshot'])(), ($acc['usage'])())) {
				$aborted_by_caller = true;
				return false;
			}

			return true;
		};

		$error = null;
		$aborted = false;
		$response = $this->_sendStream($request, $request_options, $on_event, $error, $aborted);

		// Always publish what we accumulated, even on failure — this is the salvage seam.
		$this->_streamed_partial = ($acc['snapshot'])();

		if(($api_error = ($acc['error'])())) {
			throw new Exception_DevblocksLlmApiError(
				$api_error['message'] ?? 'Streaming error.',
				static::_streamErrorStatus($api_error['type'] ?? '')
			);
		}

		if($aborted_by_caller || $aborted)
			throw new Exception_DevblocksLlmApiError('The streamed turn was stopped before it finished.', 0);

		if(false === $response)
			throw new Exception_DevblocksLlmApiError($error, 0);

		// A non-2xx never streams; the sink passes the error document through untouched. Headers are intact
		// here (unlike the in-stream `error` frame above, which rides inside a 200), so this is the one
		// streaming path where the provider's own Retry-After survives.
		if(200 != $response->getStatusCode()) {
			$status_code = $response->getStatusCode();
			$response_json = $http->getResponseAsJson($response, $error);

			throw new Exception_DevblocksLlmApiError(
				$this->_getApiErrorMessage($response_json, $status_code),
				$status_code,
				$this->_getRetryAfterSecs($response)
			);
		}

		// WE ASKED FOR A STREAM AND GOT SOMETHING ELSE. An endpoint that ignores `stream`, a proxy that
		// buffers the body, a gateway that answers in its own shape — all return a perfectly good 200
		// that simply isn't a stream. Nothing above notices: the parser finds no complete frames, so
		// we'd return an empty turn, which then slips through silently (the empty-turn guard allowlists
		// an unreported finish reason) AND strands the streaming row, because chatCompletion only
		// finalizes it when there is content to append.
		//
		// `$saw_event` is the honest discriminator — NOT "did we accumulate content". A real stream that
		// legitimately produced nothing still emits frames, and must stay an empty turn rather than
		// being re-read as a non-stream.
		if(!$saw_event) {
			// The sink hands back whatever it couldn't consume as frames, which here is the entire body.
			$fallback = $http->getResponseAsJson($response, $error);

			// An ordinary response — exactly the shape the blocking path parses. Hand it straight back:
			// same downstream code, same usage mapping, same persistence. Degrading to non-streamed is
			// the correct outcome, not a failure.
			if(is_array($fallback) && null !== ($parsed = $this->_parseNonStreamedBody($fallback)))
				return $parsed;

			if(is_array($fallback) && ($fallback['error'] ?? null))
				throw new Exception_DevblocksLlmApiError($this->_getApiErrorMessage($fallback, 500), 500);

			// Neither events nor a message we recognize. Throwing (rather than returning empty) is what
			// lets the caller's salvage path discard the open row instead of leaving it streaming forever.
			throw new Exception_DevblocksLlmApiError(
				'The provider returned no streaming events and no recognizable response.', 0
			);
		}

		return ($acc['assemble'])();
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
						// `arguments` is an OBJECT in the OpenAI schema, and PHP encodes an empty array as `[]`
						// -- so a no-argument call (the common case) would replay as the wrong JSON type. Cast
						// the empty bag the way the Anthropic-family converters already do for `input`.
						'arguments' => json_encode($tool->getParameters() ?: (object) []),
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

	/**
	 * Per-model display/capability defaults used when the agentPrompt catalog omits them:
	 * `vision` (bool), `context_window` (int), `effort_levels` (string[]).
	 *
	 * PER-MODEL is the point. `getEffortLevels()` answers for the provider, which is the right grain for KATA
	 * autocomplete but the wrong one for a picker: an OpenAI-compatible endpoint serving an arbitrary local
	 * model would advertise the whole GPT scale for something that tops out at `high`. Answer here only for
	 * ids this provider actually recognizes.
	 *
	 * An unrecognized model returns [] -- assert nothing rather than guess. Same for an individual key: OMIT
	 * `effort_levels` for a model that takes no reasoning level, since an empty list and a missing one both
	 * mean "offer nothing" and neither invents a level the API would reject.
	 *
	 * Base returns none; providers override for their own models.
	 */
	function getModelDefaults(string $model) : array {
		return [];
	}

	/**
	 * Best-effort human text for a non-2xx response body, for the operator staring at a log line.
	 *
	 * OpenAI's `{error:{message}}` is the shape everyone claims, but OpenAI-compatible endpoints leak their
	 * NATIVE error shape on the paths their compatibility layer doesn't cover — notably throttling, where
	 * DashScope answers a 429 with a top-level `{code:"Throttling.RateQuota", message:"..."}`. Reading only
	 * the nested key turned every one of those into a bare "HTTP status code: 429", hiding whether it was a
	 * per-minute rate limit (wait) or an exhausted quota (won't fix itself).
	 *
	 * Falls back to the bare status so the caller always has something to throw.
	 */
	protected function _getApiErrorMessage(mixed $response_json, int $status_code) : string {
		$fallback = 'HTTP status code: ' . $status_code;

		if(!is_array($response_json))
			return $fallback;

		$error = $response_json['error'] ?? null;

		// `error` is sometimes the message itself (a plain string) rather than an object
		$message = is_array($error)
			? ($error['message'] ?? null)
			: (is_string($error) ? $error : null);

		// Native shapes put the message (and a machine code worth keeping) at the top level
		$message = $message ?: ($response_json['message'] ?? null);

		if(!is_string($message) || '' === trim($message))
			return $fallback;

		$code = (is_array($error) ? ($error['code'] ?? null) : null) ?: ($response_json['code'] ?? null);

		if(is_string($code) && '' !== $code && !str_contains($message, $code))
			$message = sprintf('%s (%s)', $message, $code);

		return $message;
	}

	/**
	 * How long the provider asked us to wait, in whole seconds, or NULL when it didn't say.
	 *
	 * `Retry-After` is commonly sent on a 429 and MUST NOT be assumed — a rate limit without one is
	 * ordinary, and inventing a number would be worse than admitting we don't have it. Two spellings are
	 * accepted because the OpenAI family answers throttling with a millisecond header alongside (or instead
	 * of) the RFC one, and rounding 300ms down to 0 loses the only useful part of it.
	 *
	 * RFC 9110 allows either a delta-seconds integer or an HTTP-date; both appear in the wild (Azure sends
	 * seconds, some gateways send a date). A date in the past, or a clock skewed the wrong way, is clamped
	 * to 0 rather than turned into a negative wait.
	 *
	 * The 86400 ceiling is a sanity bound on a malformed header, not the retry policy — the caller decides
	 * what wait is too long to hold an interaction open for (see _DevblocksLlmService::RETRY_AFTER_MAX_SECS).
	 */
	protected function _getRetryAfterSecs(ResponseInterface $response) : ?int {
		if('' !== ($ms = trim($response->getHeaderLine('retry-after-ms'))) && is_numeric($ms))
			return min(86400, max(0, intval(ceil(floatval($ms) / 1000))));

		if('' === ($value = trim($response->getHeaderLine('Retry-After'))))
			return null;

		if(is_numeric($value))
			return min(86400, max(0, intval(ceil(floatval($value)))));

		if(false === ($when = strtotime($value)))
			return null;

		return min(86400, max(0, $when - time()));
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
	 * Let an OFF-REQUEST caller raise the per-turn HTTP timeout above the service's 30s default.
	 *
	 * `nextSessionTurn()` puts `request_timeout` into the params bag for EVERY provider, but it only takes
	 * effect where a provider reads it back — and a provider that doesn't dies at 30000ms mid-generation
	 * after the tokens are already paid for, while the async worker sat there willing to wait minutes.
	 * That silent asymmetry is why this is a shared helper rather than a line each provider remembers.
	 *
	 * CHAT ONLY. `embed()` deliberately keeps the 30s default: an embedding call is sub-second, so there the
	 * timeout is a guardrail rather than a limitation.
	 *
	 * 0 / unset keeps the default, which is what the synchronous callers (the simulator, one-off `llm.chat`)
	 * want — they are request-bound and must stay short.
	 */
	protected function _applyRequestTimeout(array &$request_options) : void {
		if(($request_timeout = intval($this->getParam('request_timeout', 0))) > 0)
			$request_options['timeout'] = $request_timeout;
	}

	/**
	 * One authenticated GET against a provider's model catalog, decoded.
	 *
	 * Split out of fetchChatModels() so a provider whose catalog spans MORE than one endpoint can reuse the
	 * request/auth/status/decode path instead of copying it — AWS Bedrock needs both `/foundation-models`
	 * (the vendor catalog) and `/inference-profiles` (the ids most of those models are actually invoked by).
	 *
	 * @return ?array The decoded JSON body, or null with $error set
	 */
	protected function _fetchModelsJson(string $url, array $headers=[], ?string &$error=null) : ?array {
		$http = DevblocksPlatform::services()->http();

		$request = new Request('GET', $url, $headers);
		$request_options = ['http_errors' => false];

		if($authentication_uri = $this->getParam('authentication', null)) {
			if(!$this->_authenticateRequest($authentication_uri, $request, $request_options, $error))
				return null;
		}

		if(false === ($response = $http->sendRequest($request, $request_options, $error)))
			return null;

		if(200 != ($status_code = $response->getStatusCode())) {
			// The provider's own text is the actionable part — AWS names the missing IAM action outright
			// ("is not authorized to perform: bedrock:ListInferenceProfiles"), which a bare status code hides.
			$body_error = null;
			$body_json = $http->getResponseAsJson($response, $body_error);
			$detail = $this->_getApiErrorMessage(is_array($body_json) ? $body_json : null, $status_code);

			$error = sprintf('The provider returned HTTP %d when listing models.%s',
				$status_code,
				('HTTP status code: ' . $status_code) === $detail ? '' : (' ' . $detail)
			);

			return null;
		}

		if(false === ($response_json = $http->getResponseAsJson($response, $error)))
			return null;

		if(!is_array($response_json)) {
			$error = 'The provider returned an unexpected model list.';
			return null;
		}

		return $response_json;
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

		$response_json = $this->_fetchModelsJson(
			$this->getChatModelsEndpointUrl($base_url),
			$this->_getChatModelsRequestHeaders(),
			$error
		);

		if(null === $response_json)
			return null;

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

	// Render this provider's native image content part from a neutral image block (mime_type + base64 data).
	// The default is the OpenAI-compatible `image_url` data-uri (most providers are OpenAI-spec wrappers);
	// Anthropic-family providers override. Returns null for an empty/invalid image.
	protected function _nativeImagePart(string $mime_type, string $data) : ?array {
		if('' === $mime_type || '' === $data)
			return null;

		return [
			'type' => 'image_url',
			'image_url' => ['url' => 'data:' . $mime_type . ';base64,' . $data],
		];
	}

	// Render a plain text content part. Split out so a provider whose blocks aren't `{type, ...}`-tagged (AWS
	// Bedrock's Converse API uses a bare `{text}`) can reshape it without duplicating expandMessageImages().
	protected function _nativeTextPart(string $text) : array {
		return ['type' => 'text', 'text' => $text];
	}

	// If a stored/neutral message carries an `images:` list [{mime_type, data}], expand it into the provider's
	// native content parts — images PREPENDED before the text (the APIs recommend images-first) — and drop the
	// `images` key. A message with no images is returned unchanged. Providers call this from sanitizeMessages().
	function expandMessageImages(array $message) : array {
		$images = $message['images'] ?? null;

		// An empty/invalid `images` is a no-op — strip the key so a stray `images: []` never reaches the
		// provider API (which rejects unknown message fields with "Extra inputs are not permitted").
		if(!is_array($images) || !$images) {
			unset($message['images']);
			return $message;
		}

		$parts = [];

		// Resolve descriptors (cerb: resource uris → base64) transiently, for THIS send only — the stored
		// message keeps its uris (we favor resource references over base64 blobs in history/continuations).
		foreach(DevblocksPlatform::services()->llm()->resolveImageDescriptors($images) as $image) {
			if(($part = $this->_nativeImagePart($image['mime_type'], $image['data'])))
				$parts[] = $part;
		}

		// Text follows the images. `content` is normally a string here; if a provider already made it a parts
		// array, append those after the images.
		$content = $message['content'] ?? '';

		if(is_string($content) && '' !== $content)
			$parts[] = $this->_nativeTextPart($content);
		elseif(is_array($content))
			$parts = array_merge($parts, $content);

		unset($message['images']);
		$message['content'] = $parts;

		return $message;
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

	// Normalize a tool call's parameters into the bag DevblocksLlmChatResponse_Tool requires. A NO-ARGUMENT
	// call has no single wire shape: '{}' from most of the OpenAI family, but '' from llama.cpp/Qwen -- and
	// from our own streaming accumulator, which seeds `arguments` with '' and appends nothing when the model
	// sends no fragment. `json_decode('')` is NULL, which isn't a legal $parameters, so the raw decode fataled
	// on exactly the call sanitizePartialContent() deliberately keeps as legitimate. Anything else that fails
	// to decode to a bag (a severed prefix, a scalar) degrades to empty for the same reason: every caller is a
	// READ over already-persisted messages, where a fatal costs the whole transcript rather than one argument.
	protected function _normalizeToolParameters(mixed $parameters) : array {
		if(is_string($parameters))
			$parameters = json_decode($parameters, true);

		if(is_object($parameters))
			$parameters = (array) $parameters;

		return is_array($parameters) ? $parameters : [];
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

	// Surface a stored message's neutral `images:` descriptors (mime_type + resource uri) onto the response as
	// displayable image blocks (mime_type + a lazy render URL) — for the transcript viewer. Called by each
	// provider's convertToGenericMessage(). Accepts a bare uri/token string or a {mime_type?, uri} descriptor.
	protected function _pushMessageImages(array $message, DevblocksLlmChatResponse $response) : void {
		$images = $message['images'] ?? null;

		if(!is_array($images))
			return;

		$url_writer = DevblocksPlatform::services()->url();

		foreach($images as $image) {
			if(is_string($image)) {
				$uri = $image;
				$mime_type = '';
			} elseif(is_array($image)) {
				$uri = strval($image['uri'] ?? '');
				$mime_type = strval($image['mime_type'] ?? '');
			} else {
				continue;
			}

			if('' === $uri)
				continue;

			// Durable attachment → the ACL-gated file endpoint; legacy resource token → the lazy image endpoint.
			if(DevblocksPlatform::strStartsWith($uri, 'cerb:attachment:')) {
				$url = $url_writer->write(sprintf('c=files&id=%d&name=image', intval(substr($uri, strlen('cerb:attachment:')))), true);
			} else {
				$token = DevblocksPlatform::strStartsWith($uri, 'cerb:automation_resource:')
					? substr($uri, strlen('cerb:automation_resource:'))
					: $uri;

				$url = $url_writer->write(sprintf('c=ui&a=image&token=%s', urlencode($token)), true);
			}

			$response->pushImage($mime_type, $url);
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
			'openrouter',
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
	 * The chat providers as the model-card picker (`CerbUI.AgentPrompt.ModelPicker`) needs them:
	 * `[{id, label, icon, models, endpoint_default}]`. Shared by the form builder's `agentPrompt` inspector and
	 * the worker profile's AI tab, so both offer the same providers, icons, and model suggestions.
	 */
	function getAgentProviders() : array {
		$labels = [
			'openai' => 'OpenAI',
			'anthropic' => 'Anthropic',
			'gemini' => 'Google Gemini',
			'groq' => 'Groq',
			'ollama' => 'Ollama',
			'aws_bedrock' => 'AWS Bedrock',
			'huggingface' => 'Hugging Face',
			'together' => 'Together AI',
			'docker' => 'Docker',
			'zai' => 'z.ai',
			'qwen' => 'Qwen Cloud',
			'openrouter' => 'OpenRouter',
		];

		$out = [];

		foreach(array_keys($this->getChatProviders()) as $id) {
			try {
				$provider = $this->getProvider($id, [], false);
			} catch(\Throwable $e) {
				continue;
			}

			if(!$provider)
				continue;

			$out[] = [
				'id' => $id,
				'label' => $labels[$id] ?? ucfirst($id),
				'icon' => $provider->getIcon(),
				'models' => method_exists($provider, 'getChatModels') ? $provider->getChatModels() : [],
				'endpoint_default' => (string) ($provider->getParam('api_endpoint_url') ?? ''),
			];
		}

		return $out;
	}

	/**
	 * The `display:` sub-block — the model's brand mark and friendly name for transcripts and pickers. It's a
	 * CERB-side concern rather than a provider knob (providers read `$_params` by key and never see it), so
	 * it's advertised HERE for every provider instead of being repeated in each getChatKataAutocomplete().
	 * Chat only: it exists to label a conversation, and an embedding call has none.
	 */
	private function _getDisplayKataAutocomplete() : array {
		return [
			'keys' => [
				[
					'caption' => 'display:',
					'snippet' => "display:\n\ticon: \${1:bot}",
					'docHTML' => '<b>display:</b> Override the brand mark and name this model reads as in transcripts and pickers. Useful when the provider id can\'t name the model: a self-hosted OpenAI-compatible server (llama.cpp, LM Studio, vLLM), where the brand says nothing about which model answers; or a gateway (OpenRouter, AWS Bedrock) that fronts another vendor\'s model and paints its own mark. Never sent to the provider.',
				],
			],
			'values' => [
				'display:' => ['name:', 'icon:', 'icon_color:'],
				'display:icon:' => ['type' => 'icon'],
			],
		];
	}

	/**
	 * The `effort_choices:` knob -- the reasoning levels this model offers as a submenu in the chat picker.
	 *
	 * Advertised HERE for every reasoning-capable chat provider, for the same reason as `display:`: it's a
	 * CERB-side concern that no provider ever sees on the wire (only AgentPromptAwait reads it), so repeating
	 * it in each getChatKataAutocomplete() would be nine copies of one key.
	 *
	 * It was already READ from the params bag and already offered inside an automation's `agentPrompt: models:`
	 * block -- but nowhere else, so the agent model editor's params box never suggested it and the knob was
	 * effectively unreachable from a record. That's the gap this closes: a reasoning model whose id its
	 * provider doesn't recognize (a local Qwen or DeepSeek behind an OpenAI-compatible endpoint) has no other
	 * way to say which levels it takes, because getModelDefaults() deliberately declines to guess for an
	 * unrecognized id rather than offer a scale the model would 400 on.
	 *
	 * The suggested values are generic example COMBINATIONS, not a provider vocabulary -- deliberately. Seeding
	 * them from getEffortLevels() would hand a local model the GPT scale, which is the exact wrong answer this
	 * key exists to correct.
	 */
	private function _getEffortChoicesKataAutocomplete() : array {
		return [
			'keys' => [
				[
					'caption' => 'effort_choices:',
					'snippet' => 'effort_choices: ${1:low,medium,high}',
					'docHTML' => '<b>effort_choices:</b> The reasoning levels to offer for this model, comma-separated (or a <code>@list</code>). Shown as the effort submenu on the chat model picker; the fixed <code>effort:</code> stays the pre-selected default. Declaring this WINS over whatever the provider infers from the model id, and is the only way to offer levels for a model the provider doesn\'t recognize -- a self-hosted Qwen or DeepSeek on an OpenAI-compatible endpoint. Never sent to the provider.',
				],
			],
			'values' => [
				'effort_choices:' => ['low,medium,high', 'medium,high,xhigh,max', 'minimal,low,medium,high'],
			],
		];
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

		if(!$is_embedding) {
			$display = $this->_getDisplayKataAutocomplete();
			$extra_keys = array_merge($extra_keys, $display['keys']);
			$extra_values = array_merge($extra_values, $display['values']);
		}

		// Not merged into $extra_keys like `display:` above, because this one is PER PROVIDER: it's gated on
		// supportsReasoning(), so a chat provider that has nowhere to put a reasoning level doesn't advertise
		// a list of them. Every chat provider answers true today; the gate is what makes declaring otherwise
		// actually mean something.
		$effort_choices = $is_embedding ? ['keys' => [], 'values' => []] : $this->_getEffortChoicesKataAutocomplete();

		// Embeddings ask the provider rather than its class hierarchy — see supportsEmbeddings().
		$supports = $is_embedding
			? fn($provider) => $provider->supportsEmbeddings()
			: fn($provider) => $provider instanceof \Cerb\LLM\Providers\Interfaces\Chat;

		$out = [];
		$provider_list = [];

		foreach($this->getProviderIds() as $provider_id) {
			try {
				$provider = $this->getProvider($provider_id, [], false);
			} catch(\Throwable $e) {
				continue;
			}

			if(!$supports($provider))
				continue;

			$provider_list[] = $provider_id . ':';
			$base = $prefix . $provider_id . ':';
			$block = $is_embedding ? $provider->getEmbeddingKataAutocomplete() : $provider->getChatKataAutocomplete();

			$reasons = !$is_embedding && $provider->supportsReasoning();

			// Value/knob sub-paths first (most specific), then the caller's extra value sub-paths.
			foreach(($block['values'] ?? []) as $subpath => $suggestions)
				$out[$base . $subpath] = $suggestions;
			if($reasons) {
				foreach($effort_choices['values'] as $subpath => $suggestions)
					$out[$base . $subpath] = $suggestions;
			}
			foreach($extra_values as $subpath => $suggestions)
				$out[$base . $subpath] = $suggestions;

			// The block keys (+ any caller extras like vision/context_window/compaction/disabled).
			$out[$base] = array_merge(
				$block['keys'] ?? [],
				$reasons ? $effort_choices['keys'] : [],
				$extra_keys
			);
		}

		// The provider list (least specific) last.
		$out[$prefix] = $provider_list;

		return $out;
	}

	/**
	 * KATA autocomplete for `llm.agent:inputs:model:` — the `agent_model` reference grammar. Mirrors
	 * getKataProviderAutocomplete(), but keyed by RECORD NAME instead of provider id: the level under `model:`
	 * lists the model names we've configured, and under each name are that record's OWN provider knobs — the
	 * record already supplies model + authentication, so those are dropped from the override hints.
	 *
	 * Rebuilt server-side per editor load (the automation editor regenerates its schema each render), so a
	 * newly added model shows up on reload. Disabled records are omitted — they can't be referenced.
	 *
	 * Optional $extra_keys append to every per-model block and $extra_values add per-model value sub-paths
	 * (used by the agentPrompt catalog for effort_choices / disabled / compaction), mirroring
	 * getKataProviderAutocomplete().
	 *
	 * @param string $prefix e.g. `(.*):llm.agent:inputs:model:` (must end in `:`)
	 */
	function getKataAgentModelAutocomplete(string $prefix, array $extra_keys = [], array $extra_values = []) : array {
		// The record already supplies `display:` from its own columns, but a per-invocation override is
		// legitimate here for the same reason `context_window:` is.
		$display = $this->_getDisplayKataAutocomplete();
		$extra_keys = array_merge($extra_keys, $display['keys']);
		$extra_values = array_merge($extra_values, $display['values']);

		$out = [];
		$names = [];
		$blocks = []; // one provider block per id, reused across records of that provider

		foreach(\DAO_AgentModel::getAll() as $model) {
			// Unlisted models stay offered here -- naming one explicitly is exactly how they're meant to run.
			if(!$model->isUsable())
				continue;

			$provider_id = strval($model->provider);

			$names[] = [
				'caption' => $model->name . ':',
				'snippet' => $model->name . ":\n\t",
				'score' => 2000,
				'docHTML' => sprintf('<b>%s</b> &mdash; %s%s. Overrides below apply in this model\'s provider grammar; the record already supplies its model and authentication.',
					htmlspecialchars($model->name),
					htmlspecialchars($provider_id ?: '(no provider)'),
					('' !== strval($model->model)) ? ' <code>' . htmlspecialchars(strval($model->model)) . '</code>' : ''
				),
			];

			// That record's provider block (keys + value sub-paths), cached per provider.
			if(!array_key_exists($provider_id, $blocks)) {
				$block = ['keys' => [], 'values' => []];

				try {
					$provider = $this->getProvider($provider_id, [], false);

					if($provider instanceof \Cerb\LLM\Providers\Interfaces\Chat)
						$block = $provider->getChatKataAutocomplete();

				} catch(\Throwable $e) {}

				$blocks[$provider_id] = $block;
			}

			$block = $blocks[$provider_id];

			// preg_quote the name: schema keys are matched as anchored JS regexes (`^…$`), so a literal `.`/`-`
			// in a name must be escaped or it would over-match.
			//
			// The trailing `(?:/[^:]*)?` accepts the ALIAS form `<name>/<alias>:`, which mounts the same record
			// more than once with different auth/endpoint/knobs. Without it the anchored pattern built for the
			// bare name wouldn't match an aliased key, and the editor would flag valid KATA as invalid. The
			// record name is always the first segment, so one pattern covers every mount of that model.
			$base = $prefix . preg_quote(strval($model->name)) . '(?:/[^:]*)?' . ':';

			foreach(($block['values'] ?? []) as $subpath => $suggestions)
				$out[$base . $subpath] = $suggestions;
			foreach($extra_values as $subpath => $suggestions)
				$out[$base . $subpath] = $suggestions;

			// Drop the record-owned keys from the override hints, then append the caller's extras.
			$keys = array_values(array_filter($block['keys'] ?? [], function($key) {
				$caption = is_array($key) ? strval($key['caption'] ?? '') : strval($key);
				return !in_array($caption, ['model:', 'authentication:'], true);
			}));
			$out[$base] = array_merge($keys, $extra_keys);
		}

		// The model-name list (least specific) at the prefix itself.
		$out[$prefix] = $names;

		return $out;
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

			$out[] = [
				'caption' => '@' . $mention,
				'snippet' => '@' . $mention,
				'score' => 1000,
				'docHTML' => sprintf('<b>%s</b> -- run this turn as an AI worker: attribution, memory, and the credentials it holds. It does <b>not</b> decide which models are available.',
					htmlspecialchars($worker->getName())
				),
			];
		}

		return $out;
	}

	/**
	 * Resolve a `model:` input — `{<agent_model name>: <overrides>, …}` — to the `[provider, params]` block a
	 * command reconciles/runs from, in the exact shape an inline `llm:` block supplies. Shared by `llm.agent:`
	 * and `llm.chat:` so the grammar and precedence are identical.
	 *
	 * Multiple entries are a FALLBACK list in author order: the FIRST that resolves to an ENABLED record wins
	 * (a not-found or disabled entry is skipped, so a fallback survives a deleted/retired primary). A later
	 * `disabled@bool:` per-entry override for conditional selection is planned but not built.
	 *
	 * @return ?array [provider_id, params]; null (no error) when no `model:` was given; null WITH $error when
	 *                the block matched no enabled record.
	 */
	function resolveModelInput(?array $model_input, ?string &$error=null) : ?array {
		if(!is_array($model_input) || !$model_input)
			return null;

		$tried = [];

		foreach($model_input as $key => $overrides) {
			$key = strval($key);
			$overrides = is_array($overrides) ? $overrides : [];

			// `<name>/<alias>` mounts the same record more than once with different auth/endpoint/knobs. The
			// record name is ALWAYS the first segment, so an alias can never point at a different record.
			$name = DevblocksPlatform::services()->string()->strBefore($key, '/') ?: $key;

			if(!($record = \DAO_AgentModel::getByName($name))) {
				$tried[] = sprintf('%s (not found)', $key);
				continue;
			}

			// Only `disabled` is refused. An explicit `model:` IS the manual path an unlisted model exists for.
			if(!$record->isUsable()) {
				$tried[] = sprintf('%s (disabled)', $key);
				continue;
			}

			return $this->resolveAgentModelBlock($record, $overrides, $error);
		}

		$error = sprintf("`model:` matched no enabled agent model (tried: %s).", implode(', ', $tried));
		return null;
	}

	/**
	 * An `agent_model` record + inline overrides → the effective `[provider, params]` block. The record's own
	 * columns + `params_kata` are the base (getProviderParams merges those); the inline overrides win on top,
	 * nested-aware so a `thinking:`/`compaction:` sub-block deep-merges rather than clobbers. Pure (no DB) so
	 * it's unit-testable with a synthetic model.
	 *
	 * @return ?array [provider_id, params], or null with $error set
	 */
	function resolveAgentModelBlock(\Model_AgentModel $record, array $overrides, ?string &$error=null) : ?array {
		list($provider, $params) = $record->getProviderParams($error);

		if('' === trim(strval($provider))) {
			$error = $error ?: "The agent model has no provider.";
			return null;
		}

		if($overrides)
			$params = array_replace_recursive($params, $overrides);

		return [$provider, $params];
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

	// Prepare one inbound message for storage/send: THROWS when the message carries images and the provider's
	// model lacks vision, else keeps the descriptors AS-IS — cerb: resource uris, NOT base64 (we store uris in history/continuations
	// and expand to base64 only at send, in expandMessageImages). Normalizes a map (image/0, …) to a list.
	// Shared by `llm.agent` (before appendMessage) and `llm.chat` (before chatCompletion).
	function normalizeMessageImages(array $message, Extension_DevblocksLlmProvider $provider) : array {
		// Empty/invalid `images` → strip the key (a stray `images: []` must never reach the provider API).
		if(!is_array($message['images'] ?? null) || !$message['images']) {
			unset($message['images']);
			return $message;
		}

		// Vision is a HARD requirement, so a model that lacks it must FAIL rather than answer as though no
		// image were sent. Dropping the images silently produced a confident answer about nothing: no throw,
		// no log, and the composer's paperclip gate only fires after the model is already chosen.
		if(!$provider->supportsVision()) {
			throw new Exception_DevblocksAutomationError(sprintf(
				"The `%s` model can't accept images. Choose a model with vision, or remove the images.",
				$provider->getParam('model') ?: 'selected'
			));
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
			'openrouter' => new Cerb\LLM\Providers\OpenRouter($params, $validate),
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

	/**
	 * Build the (one) context-management strategy from a model's `compaction:` config. Compaction is
	 * per target-model, so this config lives inside the `llm:<provider>:` block (carried on the session's
	 * provider_params and resumed with it), not as a sibling input. The ONLY knobs are RATIOS — fractions
	 * of the model's `context_window` — so a config is turnkey across models and never needs absolute
	 * token math (that's what a from-scratch harness is for). Omitted entirely → summarize at 90%, keep a
	 * ~5% verbatim tail.
	 *
	 *   llm:
	 *     anthropic:
	 *       model: claude-sonnet-5
	 *       context_window: 200000
	 *       compaction:
	 *         summarize@bool: yes      # no → truncate to the tail instead of summarizing
	 *         context_ratio: 0.9       # summarize when the window passes 90% of context_window
	 *         tail_ratio: 0.05         # keep the last ~5% of context_window verbatim after the summary (0 = none)
	 */
	function getCompaction(array $config, int $context_window=0) : \Cerb\LLM\History\Compaction {
		$summarize = !array_key_exists('summarize', $config) || !empty($config['summarize']);

		$context_window = $context_window > 0 ? $context_window : 150000;

		// context_ratio in [0,1]; 0 = always compact (threshold floored to 1 token below). Omitted or
		// out-of-range → the 0.9 default.
		$context_ratio = isset($config['context_ratio']) ? floatval($config['context_ratio']) : 0.9;
		if($context_ratio < 0 || $context_ratio > 1) $context_ratio = 0.9;

		// tail_ratio in [0,1]; 0 = no verbatim tail (pure summary handoff).
		$tail_ratio = isset($config['tail_ratio']) ? floatval($config['tail_ratio']) : 0.05;
		if($tail_ratio < 0 || $tail_ratio > 1) $tail_ratio = 0.05;

		$threshold = intval(round($context_ratio * $context_window));
		$keep_tail = intval(round($tail_ratio * $context_window));

		return new \Cerb\LLM\History\Compaction(max(1, $threshold), max(0, $keep_tail), $summarize);
	}
	
	/**
	 * Branch a session, optionally onto a different provider. Same-provider forks copy
	 * the message prefix verbatim (DAO fork). A cross-provider fork rewrites every
	 * message from the source's native format into the target's via the neutral
	 * projection (convertToGenericMessage -> toNativeMessage), yielding a homogeneous
	 * target-format session so later turns replay without further conversion.
	 */
	function forkSession(string $session_uuid, ?string $target_provider_id=null, array $target_params=[], ?string $into_uuid=null) : ?\Model_LlmAgentSession {
		if(!($source = \DAO_LlmAgentSession::get($session_uuid)))
			return null;

		$target_provider_id = $target_provider_id ?: $source->provider;

		// Same provider: verbatim copy-on-fork, no message rewriting needed. `$target_params` is the
		// whole provider bag (model, authentication, knobs); empty keeps the source's.
		if($target_provider_id === $source->provider) {
			$overrides = $target_params ? ['provider_params' => $target_params] : [];
			return \DAO_LlmAgentSession::fork($session_uuid, 0, $overrides, $into_uuid);
		}

		$source_provider = $this->getProvider($source->provider, [], false);
		$target_provider = $this->getProvider($target_provider_id, $target_params, false);

		if(
			!($source_provider instanceof \Cerb\LLM\Providers\Interfaces\Chat)
			|| !($target_provider instanceof \Cerb\LLM\Providers\Interfaces\Chat)
		)
			return null;

		$models = \DAO_LlmAgentMessage::getMessagesBySession($session_uuid, last_n: 0);

		$fork = new \Model_LlmAgentSession($into_uuid);
		$fork->provider = $target_provider_id;
		$fork->provider_params = $target_params;
		$fork->automation_id = $source->automation_id;
		$fork->automation_node = $source->automation_node;
		$fork->user_type = $source->user_type;
		$fork->user_id = $source->user_id;
		$fork->user_ip = $source->user_ip;

		if(!($fork = \DAO_LlmAgentSession::create($fork)))
			return null;

		$store = $this->getMemoryStore($fork->uuid);

		foreach($models as $model) {
			// Prior summaries are provider-agnostic text but re-derive under the new
			// provider's budget; skip them and let compaction rebuild as needed.
			if('summary' === $model->kind)
				continue;

			$canonical = $source_provider->convertToGenericMessage($model->data, $model->uuid);

			foreach($target_provider->toNativeMessage($canonical) as $native)
				$store->appendMessage($native);
		}

		return $fork;
	}

	/**
	 * Compact a session NOW, regardless of how full its context is — what `/compact` runs.
	 *
	 * Not a new mechanism: auto-compaction already happens at a turn boundary, so this is the same
	 * `Compaction::selectMessages()` with the threshold forced to 0 (always over budget). It mints the WORM
	 * boundary exchange, re-appends the verbatim tail, and advances the head — so the session keeps the same
	 * model/system_prompt/tools (and their cached prefix) and only its MESSAGES are replaced by the summary.
	 *
	 * Returns false with `$error` when it can't run; `$compacted` reports whether anything actually folded (a
	 * conversation whose tail already covers the whole window has nothing older to fold — a no-op, not a
	 * failure).
	 */
	function compactSession(string $session_id, ?string &$error=null, ?bool &$compacted=null, string $mode='hard') : bool {
		$compacted = false;

		if(!($session = DAO_LlmAgentSession::get($session_id)) || !$session->provider) {
			$error = 'The LLM session is unknown or has no provider.';
			return false;
		}

		$params = is_array($session->provider_params) ? $session->provider_params : [];

		if(!array_key_exists('cache', $params))
			$params['cache'] = true;

		if(!($provider = $this->getProvider($session->provider, $params, false))) {
			$error = 'The LLM session has no usable provider.';
			return false;
		}

		// The session's OWN compaction policy (summarize on/off), with the WHEN forced — and, for `hard`, the
		// verbatim tail dropped too.
		//
		// HARD is the default because that's what reaching for this on purpose means. A verbatim tail keeps
		// exactly what makes a conversation expensive: in a measured session, tool_use + tool_result were
		// **93%** of 20,783 tokens (text was 1,381). Keeping "the last few turns" therefore keeps the bulk, and
		// the fold barely moves the number. `keep_tail=0` is an established mode, not a special case — a
		// provider switch is the same primitive with no tail.
		//
		// SOFT is the automatic policy applied on demand: the session's configured `tail_ratio`. But that ratio
		// is a fraction of the MODEL's window, not the conversation's, so on a roomy model it routinely swallows
		// the whole chat (0.05 × 500K = a 25K tail vs a 20.7K conversation → nothing is "older"). Right for
		// automatic compaction, which only fires near the ceiling; useless as an answer to an explicit request.
		// So `soft` falls back to a tail sized to the CURRENT window — keep the most recent quarter.
		$context_window = intval($params['context_window'] ?? 0);
		$config = is_array($params['compaction'] ?? null) ? $params['compaction'] : [];
		$config['context_ratio'] = 0;

		if('soft' !== $mode)
			$config['tail_ratio'] = 0;

		$compaction = $this->getCompaction($config, $context_window);
		$memory_store = $this->getMemoryStore($session_id);

		// Ask first so we can report a no-op honestly rather than claiming a compaction that didn't happen.
		$plan = $compaction->plan($memory_store);

		if('soft' === $mode && empty($plan['compacted']) && 'tail_covers_all' === ($plan['reason'] ?? '')) {
			$tokens = intval($plan['tokens'] ?? 0);
			$window = $context_window > 0 ? $context_window : 150000;

			if($tokens > 0) {
				$config['tail_ratio'] = max(0.0, min(1.0, ($tokens / 4) / $window));

				$compaction = $this->getCompaction($config, $window);
				$plan = $compaction->plan($memory_store);
			}
		}

		if(empty($plan['compacted']))
			return true;

		$compaction->selectMessages($memory_store, $provider);
		$compacted = true;

		return true;
	}

	// The live-compaction directive. "Do not use tools" is load-bearing: the tool schemas MUST stay in the
	// request (they're part of the cached prefix — removing them is what would force a full-price miss), so the
	// model is told not to call them rather than having them taken away.
	const SUMMARIZE_INLINE_PROMPT = "Summarize this conversation so far into a compact briefing that preserves: the user's goals and constraints, key decisions and their rationale, established facts, tool results that still matter, and any open threads or next steps.\nOmit greetings and redundant chatter. Write in the third person. Do not invent information.\nDo not use tools. Reply with the briefing text and nothing else.";

	/**
	 * LIVE compaction: summarize a session's active window through its OWN WARM PREFIX — same system prompt,
	 * same tool schemas, same native messages — plus one appended instruction turn, run through `NoHistory` so
	 * it persists nothing.
	 *
	 * Why not summarizeMessages() here: that one FLATTENS the span to text under its own system prompt with no
	 * tools, so the serialized prefix matches nothing the agent sent and the provider cache cannot hit. Live
	 * compaction fires at ~90% of the context window — the moment the conversation is longest — so a miss there
	 * re-reads the whole thing at full input price. Sending the real prefix bills the bulk at the cache-read
	 * rate instead. (summarizeMessages() remains correct for the ARCHIVE path — a provider/model switch, where
	 * the cache is cold anyway and the point is a neutral briefing that outlives the native format.)
	 *
	 * Returns '' when it can't run or produced nothing, so the caller can fall back.
	 *
	 * @param array $native_messages The window in provider-native shape — exactly what the agent sends.
	 * @param array|null $usage Out-param: the turn's neutral usage vector, so a caller can SHOW whether the
	 *                          cache actually hit (`cache_read` carrying the bulk vs a fat `input`). A prefix
	 *                          mismatch is otherwise silent and expensive.
	 */
	function summarizeSessionWindow(Extension_DevblocksLlmProvider $provider, string $session_id, array $native_messages, string $instructions='', ?array &$usage=null) : string {
		$usage = null;

		if('' === $session_id || !$native_messages || !($session = DAO_LlmAgentSession::get($session_id)))
			return '';

		// `cerb_*` markers are ours, not the provider's — they'd be unknown fields on the wire. The live
		// compaction caller already strips them; do it here too so every caller is safe.
		$native_messages = array_map(
			fn($m) => is_array($m) ? array_filter($m, fn($k) => !str_starts_with($k, 'cerb_'), ARRAY_FILTER_USE_KEY) : $m,
			$native_messages
		);

		// Trim back to the last ASSISTANT turn. Two reasons, one mechanism: the cached prefix ends at the last
		// assistant turn (the rolling breakpoint is written there), and appending our user instruction after a
		// trailing user message would put two user turns back to back — which Anthropic rejects with a 400.
		while($native_messages && 'assistant' !== ($native_messages[array_key_last($native_messages)]['role'] ?? ''))
			array_pop($native_messages);

		if(!$native_messages)
			return '';

		$native_messages[] = [
			'role' => 'user',
			'content' => ('' !== trim($instructions)) ? $instructions : self::SUMMARIZE_INLINE_PROMPT,
		];

		// Re-resolve the provider with the rolling cache breakpoint moved back ONE message, so our appended
		// instruction stays outside the cached region. Same credentials and knobs otherwise.
		//
		// Why one and not zero: caching THROUGH the instruction mints `tools + system + conversation +
		// instruction`, which nothing can reuse (compaction replaces the history; the preview is one-shot) —
		// measured as a 32K 1h write. Why not drop the breakpoint entirely: measured at **14% cached**, because
		// hit lookback runs backward from a breakpoint and the system marker renders before every message, so it
		// can't reach a message-level entry.
		//
		// One back lands on the window's last real message — the same position an ordinary agent turn caches, so
		// the read uses the mechanism that already works turn to turn. On a warm session that's a read plus a
		// small delta; on a cold one it writes the window, which the next real turn can still read.
		//
		// Falls back to the passed-in provider if re-resolution fails, so a summary is never lost to this.
		$sidecar_params = is_array($session->provider_params) ? $session->provider_params : [];
		$sidecar_params['cache'] = true;
		$sidecar_params['cache_tail_skip'] = 1;

		// ...and place that message breakpoint ONLY when a read is plausible. On a session whose cache has
		// certainly lapsed there is nothing to read, so a breakpoint buys nothing and costs a full-window write
		// (measured: 28,023 tokens at 1h on a >1h-old transcript — pure waste, since compaction is about to
		// replace this history anyway). The stable tools+system marker still reads either way.
		//
		// `updated_at` is stamped every turn, so elapsed-since-last-turn vs the model's own cache TTL is the
		// cheapest honest predictor we have. Erring toward OFF is the right bias: a missed read costs one
		// full-price prompt we were going to pay on a cold session regardless, while a needless write is a
		// strict surcharge on top of it.
		// Gate on the SESSION's own TTL — that's the lifetime the agent's entry was written at, so it decides
		// whether there's anything left to read. Must be read before the write-TTL override below.
		$cache_ttl_secs = intval($provider->getCacheHintSeconds($sidecar_params) ?? 0);
		$idle_secs = max(0, time() - intval($session->updated_at));

		$sidecar_params['cache_tail'] = ($cache_ttl_secs > 0 && $idle_secs < $cache_ttl_secs);

		// Whatever we DO write, write it cheap. A 1h write bills ~2x base vs ~1.25x for 5m, and neither caller
		// needs an hour:
		//   - true compaction ORPHANS it immediately — the next turn sends `[summary exchange] + tail`, so the
		//     prefix diverges at the first message and nothing ever reads this entry;
		//   - the dev preview persists nothing, so the session continues and the NEXT real turn can read it —
		//     but that turn is minutes away, not an hour.
		// Reading a 1h entry while writing a 5m one is fine, and already how this provider behaves: the stable
		// tools+system breakpoint is hardcoded 1h while the rolling tail follows `cache_ttl`.
		$sidecar_params['cache_ttl'] = '5m';

		$provider = $this->getProvider($session->provider, $sidecar_params, false) ?: $provider;

		try {
			$response = $provider->chatCompletion(
				$native_messages,
				strval($session->system_prompt),
				array_values($this->getSessionToolSchemas($session)),
				new \Cerb\LLM\MemoryStore\NoHistory()
			);

			// A tool call in the reply is ignored on purpose — the schemas are only present to keep the prefix
			// identical, and we asked for text. No text at all → let the caller fall back.
			$summary = '';
			foreach($response->getMessages() as $block)
				$summary .= ($block['content'] ?? '');

			if('' !== trim($summary))
				$usage = $response->getUsage();

			return trim($summary);

		} catch(\Throwable $e) {
			return '';
		}
	}

	/**
	 * ARCHIVE summarization: flatten a span of native messages into provider-NEUTRAL briefing text via an
	 * isolated NoHistory sub-call (so the summarization turn doesn't pollute the session). Renders through the
	 * provider's neutral projection first so tool-call/result pairing can't break the sub-call; falls
	 * back to a truncated raw transcript if the call fails. Used to mint summary roots on a provider
	 * switch — where the cache is cold regardless and a NEUTRAL briefing is the point, because it has to
	 * outlive the native format. `$instructions` overrides the default summarization directive (per-workflow
	 * steering / the dev preview's editable prompt).
	 *
	 * For live/chat-time compaction use summarizeSessionWindow() instead — see the note there.
	 *
	 * @param Model_LlmAgentMessage[] $models
	 */
	function summarizeMessages(Extension_DevblocksLlmProvider $provider, array $models, string $instructions='') : string {
		if(!$models)
			return '';

		$lines = [];
		foreach($models as $model) {
			$response = $provider->convertToGenericMessage($model->data, $model->uuid);
			$role = $response->getRole() ?: ($model->role ?: 'message');

			foreach($response->getMessages() as $block) {
				if('' !== trim($block['content'] ?? ''))
					$lines[] = sprintf('[%s] %s', $role, $block['content']);
			}
			foreach($response->getToolCalls() as $tool)
				$lines[] = sprintf('[tool_call] %s(%s)', $tool->getName(), json_encode($tool->getParameters()));
			foreach($response->getToolResults() as $tool_id => $result) {
				$result = is_array($result) ? json_encode($result) : strval($result);
				$lines[] = sprintf('[tool_result:%s] %s', $tool_id, $result);
			}
		}
		$transcript = implode("\n", $lines);

		$system_prompt = ('' !== trim($instructions)) ? $instructions : implode("\n", [
			"You compact a conversation transcript to preserve context across a long agent session.",
			"Summarize the transcript below into a compact briefing that retains: the user's goals and constraints, key decisions and their rationale, established facts, tool results that still matter, and any open threads or next steps.",
			"Omit greetings and redundant chatter. Write in the third person. Do not invent information.",
		]);

		try {
			$response = $provider->chatCompletion(
				[['role' => 'user', 'content' => "Transcript to summarize:\n\n" . $transcript]],
				$system_prompt,
				[],
				new \Cerb\LLM\MemoryStore\NoHistory()
			);

			$summary = '';
			foreach($response->getMessages() as $block)
				$summary .= ($block['content'] ?? '');

			if('' !== trim($summary))
				return $summary;
		} catch(\Throwable $e) {
			// fall through to the truncated transcript
		}

		return mb_substr($transcript, 0, 4000);
	}

	/**
	 * Switch a session to a new provider IN PLACE, keeping the SAME `$session_id` (so every reference to it
	 * stays valid — the foolproof-resume design). Same provider → just refresh `provider_params`. Different
	 * provider → mint a provider-NEUTRAL summary node as a new branch root on the append-only tree (the old
	 * provider summarizes its own active path), advance the cursor onto it, and set the new provider. NO
	 * clone/rename/rewrite: the old-format branch stays in the tree (off the active path) for audit/resume,
	 * and the new provider resumes from the summary — it never has to consume the old native format.
	 */
	// A deterministic fingerprint of the capability-relevant params. Two sessions with the same signature
	// reason identically over the same native transcript, so a change between them is a plain param refresh;
	// a different signature is a capability boundary (plant a summary head). Non-capability knobs
	// (authentication, context_window, compaction, vision, cache) are deliberately excluded — they don't
	// change how the model handles history.
	private function _capabilitySignature(string $provider, array $params) : string {
		$thinking = $params['thinking'] ?? null;
		if(is_array($thinking))
			$thinking = $this->_ksortRecursive($thinking);

		// The RESOLVED wire format, not the raw `api:` param -- which is empty in the common case, since the
		// surface is normally inferred from the endpoint. An edit to `model:` or `api_endpoint_url:` that flips
		// the surface changes the dialect of every future turn, so it has to read as a capability change.
		//
		// NOTE this does NOT catch a change in how the surface is RESOLVED (a new default): both sides of every
		// comparison are computed by the same running code, and the signature is never persisted, so they still
		// match. A session that spans such a change replays its old-dialect history into the new surface, which
		// is why Responses' _toResponsesInput() reads the chat dialect. That tolerance is load-bearing.
		$api = '';

		try {
			$api = strval($this->getProvider($provider, $params, false)?->getApiSurface());
		} catch(\Throwable) {
		}

		return json_encode([
			'provider' => $provider,
			'api' => $api,
			'model' => strval($params['model'] ?? ''),
			'effort' => DevblocksPlatform::strLower(trim(strval($params['effort'] ?? ''))),
			'thinking' => $thinking,
		]);
	}

	// Recursively key-sort an array so json_encode is order-independent (for a stable capability signature).
	private function _ksortRecursive(array $arr) : array {
		ksort($arr);
		foreach($arr as $k => $v) {
			if(is_array($v))
				$arr[$k] = $this->_ksortRecursive($v);
		}
		return $arr;
	}

	function switchSessionProvider(string $session_id, string $new_provider_id, array $new_params=[]) : ?\Model_LlmAgentSession {
		if(!($source = \DAO_LlmAgentSession::get($session_id)))
			return null;

		// Same capability signature (provider + model + effort + thinking) → messages already in this format
		// and the target reasons the same way; just refresh the params bag (auth/context_window/compaction).
		if($this->_capabilitySignature($source->provider, $source->provider_params) === $this->_capabilitySignature($new_provider_id, $new_params)) {
			\DAO_LlmAgentSession::setProviderParams($session_id, $new_provider_id, $new_params);
			return \DAO_LlmAgentSession::get($session_id);
		}

		$store = $this->getMemoryStore($session_id);
		$active_path = $store->getMessageModels();

		if($active_path) {
			$old_provider = $this->getProvider($source->provider, $source->provider_params, false);

			$summary_text = ($old_provider instanceof \Cerb\LLM\Providers\Interfaces\Chat)
				? $this->summarizeMessages($old_provider, $active_path)
				: '';

			if('' !== trim($summary_text)) {
				// A boundary EXCHANGE, not a lone user turn: a synthetic user instruction (terminal ancestor)
				// + the assistant's summary. A single user node isn't resumable — the new provider's first
				// real user turn would be two consecutive user messages (Anthropic 400). keep_tail=0 here, so
				// no verbatim tail; the new provider resumes cleanly from the exchange.
				$store->appendMessage(['role' => 'user', 'content' => \Cerb\LLM\History\Compaction::SUMMARY_PROMPT], 'summary');
				$store->appendMessage(['role' => 'assistant', 'content' => $summary_text]);
			}
		}

		\DAO_LlmAgentSession::setProviderParams($session_id, $new_provider_id, $new_params);

		return \DAO_LlmAgentSession::get($session_id);
	}

	/**
	 * Prime/reconcile a session's LLM block against a selection, keeping a stable id. Missing → create it
	 * (adopting `$session_id`, `$create_fields` = ownership/automation columns). Exists, same capability
	 * signature (provider+model+effort+thinking) → refresh params. Exists, changed signature → in-place
	 * `switchSessionProvider` (plant a summary head). This is the ONE place a session's provider block is
	 * set — called by the agentPrompt (on submit) and by `llm.agent` (when given `inputs.llm`).
	 */
	function reconcileSession(string $session_id, string $provider, array $params, array $create_fields=[]) : ?\Model_LlmAgentSession {
		if('' === $provider)
			return null;

		$session = ('' !== $session_id) ? \DAO_LlmAgentSession::get($session_id) : null;

		if($session) {
			// A boundary (summarize → new head) fires on ANY capability change — provider, model, effort, or
			// thinking — since a mid-conversation reasoning-config change can make prior native blocks
			// unreplayable. Same signature → a plain param refresh (auth/context_window/compaction knobs).
			if($this->_capabilitySignature($session->provider, $session->provider_params) === $this->_capabilitySignature($provider, $params)) {
				\DAO_LlmAgentSession::setProviderParams($session->uuid, $provider, $params);
			} elseif(!$this->switchSessionProvider($session->uuid, $provider, $params)) {
				return null;
			}
			return \DAO_LlmAgentSession::get($session->uuid);
		}

		$new = new \Model_LlmAgentSession($session_id ?: null);
		$new->provider = $provider;
		$new->provider_params = $params;

		foreach($create_fields as $k => $v) {
			if(property_exists($new, $k))
				$new->$k = $v;
		}

		return \DAO_LlmAgentSession::create($new);
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
		
		// The tool automation describes itself, and that's the right default -- it's the thing that knows what it
		// does. But a caller may override it: the same automation reads differently to an agent drafting a reply
		// than to one editing an icon, and the alternative is a second automation that only differs by a
		// sentence. Blank falls through, so an override is opt-in per call site.
		$tool_description = trim(strval($tool['description'] ?? ''));

		$tool_schema = [
			'type' => 'function',
			'function' => [
				'name' => $tool_name,
				'description' => ('' !== $tool_description) ? $tool_description : ($tool_automation->description ?? ''),
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

	/**
	 * Advance an LLM agent session by ONE provider turn: append `$messages` to the managed history, run
	 * compaction, and call the provider — which appends the assistant turn back to the session. Everything is
	 * reconstructed from the SESSION (provider, params, system_prompt, tools, mounts, compaction), so a caller
	 * needs only a `session_id` + the new messages. This is the session-managed wrapper `llm.agent` uses (and,
	 * later, what a queue worker runs headless); one-off callers (summarization, classification) still call a
	 * provider's chatCompletion() directly. Errors are returned via `$error`, never thrown.
	 *
	 * @param array $messages neutral message dicts to append before the turn (e.g. the user's message)
	 * @return DevblocksLlmChatResponse|null null on an unknown/unprimed session or unknown provider
	 */
	function nextSessionTurn(string $session_id, array $messages = [], ?string &$error = null, int $request_timeout = 0, bool $stream = false, int $stall_secs = 0, ?bool &$was_interrupted = null) : ?DevblocksLlmChatResponse {
		if(!($session = DAO_LlmAgentSession::get($session_id)) || !$session->provider) {
			$error = 'The LLM session is unknown or has no provider.';
			return null;
		}

		// Provider from the session. Default prompt caching ON for the multi-turn agent (mirrors
		// LlmAgentNode::_defaultCache — Anthropic reads a cache only when we send cache_control; OpenAI-family
		// auto-caches and ignores it).
		$params = is_array($session->provider_params) ? $session->provider_params : [];

		if(!array_key_exists('cache', $params))
			$params['cache'] = true;

		// Agentic turns need real output headroom. Anthropic REQUIRES `max_tokens` and defaults it low (2048),
		// which truncates long agent replies / tool-call JSON mid-stream (`stop_reason: max_tokens`). `max_tokens`
		// is a hard CEILING, not a target — it's not sent to the model and you bill only on tokens generated — so
		// a high cap costs nothing on a normal turn. Default it high HERE (the agent seam) so `llm.chat` one-offs
		// keep the provider's conservative default. No-op for OpenAI-family (it omits max_tokens and uses the
		// model's own max); a model whose output ceiling is below this needs an explicit `max_tokens:` in its
		// `models:` block, the same per-model override as `context_window`.
		if(!array_key_exists('max_tokens', $params))
			$params['max_tokens'] = 32000;

		// A big-context / extended-thinking turn (esp. with 32000 max_tokens, non-streamed) can exceed the HTTP
		// service's default 30s. The ASYNC WORKER isn't request-bound (its FPM pool has request_terminate_timeout=0
		// and finishes even if the client hangs up), so it passes a longer per-turn timeout the provider maps onto
		// the request. 0 (the default, used by the synchronous simulator / one-off callers) leaves the 30s default,
		// keeping those bounded.
		if($request_timeout > 0)
			$params['request_timeout'] = $request_timeout;

		// Read by the provider when it streams, so it has to be in place before construction.
		if($stall_secs > 0)
			$params['stream_stall_secs'] = $stall_secs;

		if(!($provider = $this->getProvider($session->provider, $params))) {
			$error = sprintf("Unknown LLM provider `%s`.", $session->provider);
			return null;
		}

		$memory_store = $this->getMemoryStore($session_id);

		// FIRST, before anything is appended: a previous attempt may have died mid-stream and left a half-written
		// head. It has to be resolved while it's still the newest thing in the session — append first and the new
		// user message becomes its CHILD, so discarding the dangling row would orphan the message that was just
		// added, and finalizing it would bury a truncated turn under a newer one.
		$memory_store->resolveDanglingStream();

		// Append the inbound messages to the managed history first, so the send-list build sees them. A message
		// carrying images for a model without vision fails the turn rather than being quietly stripped.
		foreach($messages as $new_message) {
			// nextSessionTurn() reports failures through $error rather than by throwing, so an unsupported
			// image is converted here instead of escaping to callers that only check the return value.
			try {
				if(is_array($new_message))
					$new_message = $this->normalizeMessageImages($new_message, $provider);

			} catch(Exception_DevblocksAutomationError $e) {
				$error = $e->getMessage();
				return null;
			}

			$memory_store->appendMessage($new_message);
		}

		// Context management (sliding window by default); compaction may summarize + persist a summary here.
		$compaction_config = is_array($params['compaction'] ?? null) ? $params['compaction'] : [];
		$history = $this->getCompaction($compaction_config, intval($params['context_window'] ?? 0));
		$memory_messages = $history->selectMessages($memory_store, $provider);

		$was_interrupted = false;

		// Stream when the caller asked, this provider can, and the author hasn't opted out with
		// `stream@bool: no`. A provider that doesn't implement ChatStreaming simply takes the blocking
		// path — no branch anywhere else, and no provider has to be changed to keep working.
		if($stream && $provider instanceof \Cerb\LLM\Providers\Interfaces\ChatStreaming && $provider->isStreamingEnabled()) {
			// Opened AFTER the inbound messages so the user turn is its parent, and BEFORE the call so it has a
			// real uuid from the first delta — that uuid is what a reader addresses while the turn is running.
			if(null !== ($streaming_uuid = $memory_store->beginStreamingMessage())) {
				$last_flush = 0.0;

				$provider->enableStreaming(function(array $message, array $usage) use ($memory_store, $streaming_uuid, $session_id, &$last_flush, &$was_interrupted) : bool {
					// Deltas arrive many times per second; nothing reads faster than the poll. Throttling is the
					// caller's job precisely because the provider has no idea who's watching or how often — and
					// the same tick is the natural place to notice a Stop, since it's the only moment we're
					// guaranteed to hold control during a call that may run for minutes.
					$now = microtime(true);

					if(($now - $last_flush) < 0.5)
						return true;

					$last_flush = $now;
					$memory_store->updateStreamingMessage($streaming_uuid, $message, $usage);

					if($this->_isTurnInterrupted($session_id)) {
						$was_interrupted = true;
						return false;
					}

					return true;
				});
			}
		}

		try {
			// The call appends the assistant turn to the session store — which, when a streamed row is open,
			// CLOSES that row instead of inserting a second one.
			return $provider->chatCompletion(
				$memory_messages,
				strval($session->system_prompt),
				array_values($this->getSessionToolSchemas($session)),
				$memory_store
			);

		} catch (\Throwable $e) {
			// The turn died after we'd already been billed for whatever it generated. Keep what's usable rather
			// than discarding it, and leave nothing half-written for the next turn to trip over.
			$this->_salvageStreamedTurn($provider, $memory_store);
			throw $e;
		}
	}

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
	 * Drain the `cerb.llm.agent.requests` queue: each message is ONE agent turn `{session_id, messages}`, run
	 * via nextSessionTurn() (which appends the assistant turn back onto the session). Messages only — no jobs.
	 *
	 * Same consumer signature as the other platform services (search/metrics/records), so QueueConsumer_Internal
	 * delegates here. It's ALSO what the interactive `await:queue:` poll calls with its remaining request budget,
	 * so a watching client advances the queue itself (single-claim keeps two workers off the same turn).
	 *
	 * `$max_messages` caps how many messages ONE call processes (0 = unlimited within the budget). The cron
	 * drains fully (0); the interactive `await:queue:` poll passes 1 so it can re-check its own gate after each
	 * turn and stop the moment its work is done, rather than draining the whole queue on someone's request.
	 *
	 * @return int turns advanced this pass
	 */
	function processQueue(Model_Queue $queue, int $stop_time, int $count_hint, ?Model_QueueJob $queue_job = null, int $max_messages = 0) : int {
		$queue_service = DevblocksPlatform::services()->queue();

		$processed = 0;
		$count = 0;
		$claim_id = null;

		// TWO budgets, because a single total timeout can't tell a LONG turn from a STUCK one — and getting that
		// wrong is what killed healthy 5-minute generations after we'd already paid for their tokens.
		//
		//   $turn_stall_secs — the real control. Abort only when the stream goes QUIET. A measured worst-case gap
		//     between chunks during extended thinking is ~6.5s, so this leaves a wide margin. (curl treats it as
		//     an average-speed window rather than a silence timer, so the effective cutoff lands somewhat later —
		//     it's a floor, not a deadline.)
		//   $turn_timeout — an absolute backstop for the pathological case of a stream that drips forever, and
		//     the only budget a non-streaming provider gets. Also caps PHP itself via set_time_limit().
		//
		// This worker isn't request-bound (its FPM pool has request_terminate_timeout=0 and finishes even if the
		// client hangs up), so the ceiling can be generous where a web request's couldn't be.
		$turn_stall_secs = 60;
		$turn_timeout = 900;

		// One turn per message; loop so concurrent workers interleave within the $stop_time budget.
		while($stop_time > time()) {
			if($max_messages > 0 && $count >= $max_messages)
				break;

			if(!($messages = $queue_service->dequeue($queue->name, 1, $claim_id)))
				break;

			foreach($messages as $queue_message) { /* @var $queue_message Model_QueueMessage */
				$session_id = strval($queue_message->message['session_id'] ?? '');
				$new_messages = is_array($queue_message->message['messages'] ?? null) ? $queue_message->message['messages'] : [];
				// A control command (`/compact`) rides the SAME queue as a turn: it mutates the session, calls a
				// provider, and must not interleave with a real turn — exactly what the queue already guarantees.
				$command = strval($queue_message->message['command'] ?? '');

				if('' === $session_id) {
					// A malformed payload will never succeed → force terminal (don't burn retries on it).
					$queue_message->retry_count = self::RETRY_COUNT_TERMINAL;
					$queue_message->reportStatus(QueueMessageStatus::FAILED, 'The queue message has no session_id.');
					continue;
				}

				// `/compact` replaces the turn rather than preceding it — no provider ANSWER, just the fold. It's
				// naturally idempotent on retry: a second run finds the tail already covering the window and no-ops,
				// so it needs none of the already-landed guards below.
				if('compact' === $command) {
					@set_time_limit($turn_timeout + 30);

					$error = null;
					$compacted = false;
					$mode = \Cerb\AutomationBuilder\Node\LlmAgentNode::compactModeFor(strval($queue_message->message['args'] ?? ''));

					if($this->compactSession($session_id, $error, $compacted, $mode)) {
						$queue_message->reportStatus(QueueMessageStatus::DONE, $compacted ? 'Compacted.' : 'Nothing to compact.', [
							'command' => 'compact',
							'compacted' => $compacted,
						]);
					} else {
						$queue_message->retry_count = self::RETRY_COUNT_TERMINAL;
						$queue_message->reportStatus(QueueMessageStatus::FAILED, $error ?: 'The compaction failed.');
					}

					$processed += $queue_message->cardinality;
					$count++;
					continue;
				}

				// IDEMPOTENCY on retry: the inbound user messages were already appended to the history on the FIRST
				// attempt (nextSessionTurn appends BEFORE the provider call, so even a failed attempt persisted them).
				// Re-appending on a retry would DUPLICATE the user turn — so a retry sends NO new messages and just
				// re-attempts the provider call against the existing history.
				$turn_messages = ($queue_message->retry_count > 0) ? [] : $new_messages;

				// Fresh PHP budget for THIS turn (the loop may run several within $stop_time).
				@set_time_limit($turn_timeout + 30);

				$error = null;

				// CHECK BEFORE CALL — the assistant turn is persisted (inside chatCompletion, via the memory store)
				// BEFORE this message is acked, so a failure anywhere in that window leaves the session ADVANCED with
				// the message un-acked. Calling the provider again would append a SECOND assistant turn to a history
				// that already has one — a duplicate turn, billed twice, on top of a session the gate can never clear.
				// Observed live (session e49f40ba…, seq 2691: 19,881 output tokens landed; queue_message still
				// retry_count=4/AVAILABLE). Only meaningful on a RETRY: at retry_count 0 this attempt hasn't run yet,
				// so an assistant head can't be ours.
				if($queue_message->retry_count > 0 && $this->_sessionTurnAlreadyLanded($session_id)) {
					$queue_message->reportStatus(QueueMessageStatus::DONE, 'A prior attempt already advanced the session; acked without re-calling the provider.', [
						'already_landed' => true,
						'retry_count' => $queue_message->retry_count,
					]);
					$processed += $queue_message->cardinality;
					$count++;
					continue;
				}

				// INSTRUMENTATION: the provider call is unbounded from our side until $turn_timeout fires, and the
				// Anthropic Console reports no duration — so this is the only place a turn's real wall-clock exists.
				// Recorded on the queue log entry (success AND failure) so the timeout can be sized from a real
				// distribution instead of a guess, and so a slow-but-succeeding turn is distinguishable from a stall.
				$turn_started_at = microtime(true);
				$elapsed_ms = fn() => intval(round((microtime(true) - $turn_started_at) * 1000));

				// A provider call can THROW — a network/timeout/HTTP failure surfaces as Exception_DevblocksLlmApiError
				// (carrying the status), any other Throwable is possible. CATCH it so the message is always finalized:
				// an uncaught throw would leave it CLAIMED/IN_FLIGHT and the interaction's gate would poll forever.
				// ERROR-CLASS aware: a transient failure (network/timeout, 429/503, 5xx) is left to the queue's retry
				// policy (same-uuid re-enqueue with backoff, so the interaction's gate keeps waiting); a futile one
				// (401/400/bad payload) is forced terminal so it surfaces at once instead of burning retries.
				$was_interrupted = false;

				try {
					// STREAM the turn. This is the queue worker — the one place a turn runs off-request with
					// somewhere to park — so it's exactly where the inactivity budget and the observable partial
					// are worth having. A provider without streaming support falls back to the blocking call.
					if(!($response = $this->nextSessionTurn($session_id, $turn_messages, $error, $turn_timeout, stream: true, stall_secs: $turn_stall_secs, was_interrupted: $was_interrupted))) {
						// A null return is a setup/config failure (unknown session/provider), never transient.
						$queue_message->retry_count = self::RETRY_COUNT_TERMINAL;
						$this->_stashTurnError($session_id, $error ?: 'The LLM turn could not run.', 0);
						$queue_message->reportStatus(QueueMessageStatus::FAILED, $error ?: 'The LLM turn could not run.', [
							'duration_ms' => $elapsed_ms(),
						]);
						continue;
					}
				} catch (\Throwable $e) {
					// A turn WE stopped is not a failure — it did exactly what was asked. Reporting FAILED here
					// would be actively harmful: awaitGate() turns a failed message into an interaction-level
					// error, so pressing Stop would blow up the very interaction the user was steering. The
					// partial has already been salvaged onto the session, so the turn is genuinely complete.
					if($was_interrupted) {
						$queue_message->reportStatus(QueueMessageStatus::DONE, 'The turn was stopped; the partial response was kept.', [
							'duration_ms' => $elapsed_ms(),
							'interrupted' => true,
						]);

						$processed += $queue_message->cardinality;
						$count++;
						continue;
					}

					$status_code = ($e instanceof \Exception_DevblocksLlmApiError) ? $e->statusCode : 0;
					$retry_after = ($e instanceof \Exception_DevblocksLlmApiError) ? $e->retryAfter : null;

					// `landed_despite_error` is the forensic signal for the class of bug the check above now absorbs:
					// the turn reached the session but this attempt still reported failure. If it shows up, the throw is
					// happening AFTER the provider call returned — not a provider timeout at all.
					// Snapshot the elapsed time once so `timed_out` can't disagree with `duration_ms`.
					$failed_after_ms = $elapsed_ms();
					$timed_out = $failed_after_ms >= ($turn_timeout * 1000);
					// A STALL and a ceiling overrun both surface as cURL 28, so elapsed time alone can no longer
					// tell them apart now that the two budgets differ by an order of magnitude. The distinction is
					// the whole point of the split — "went quiet" is a provider/network problem, "ran the full
					// ceiling while still producing" means the ceiling is too low — so record it explicitly rather
					// than leaving it to be inferred from a duration.
					$stalled = !$timed_out && str_contains($e->getMessage(), 'Operation too slow');
					$landed = $this->_sessionTurnAlreadyLanded($session_id);

					// Retry, or hand it to the human? The whole decision is in one pure function, and it can only
					// ever say yes to a failure that cost nothing — see getTurnRetryDelaySecs().
					$retry_delay = self::getTurnRetryDelaySecs($status_code, $queue_message->retry_count, $retry_after, $landed);

					// Straight to the error log, NOT just reportStatus() metadata: `_bufferLogEntry()` drops anything on
					// a job-less message ("fire-and-forget messages (job_id=0) skip logging"), and every LLM turn is
					// job-less — so the metadata alone would silently go nowhere. A failed turn is rare and expensive
					// enough to deserve a line; this is the only durable record of how long we actually waited.
					// `retry_after` is logged even when we didn't act on it: it's the only place the provider's real
					// numbers accumulate, and it's what a longer blind backoff would have to be sized from. A REQUEUED
					// attempt is logged too, or a turn that silently succeeded on its third try looks like it succeeded
					// on its first.
					DevblocksPlatform::logError(sprintf(
						'[llm.turn] session=%s failed after %dms (timed_out=%s, stalled=%s, landed_despite_error=%s, status=%d, retry_after=%s, attempt=%d/%d): %s -- %s',
						$session_id,
						$failed_after_ms,
						$timed_out ? 'yes' : 'no',
						$stalled ? 'yes' : 'no',
						$landed ? 'yes' : 'no',
						$status_code,
						is_null($retry_after) ? 'n/a' : $retry_after . 's',
						$queue_message->retry_count + 1,
						self::TURN_RETRY_MAX_ATTEMPTS,
						is_null($retry_delay) ? 'surfacing to the interaction' : sprintf('requeued in %ds', $retry_delay),
						$e->getMessage()
					));

					// Leave the reason where the INTERACTION can read it. The awaiting `llm.agent` node resumes in a
					// different request and reports this to the worker (rewinding the session and routing to
					// `on_error:`), and there is nowhere on the message itself to put it — see turnErrorCacheKey().
					// Written on EVERY attempt, overwriting: a requeue that later succeeds leaves a stale slot, but the
					// node clears it at enqueue, and if the retries do run out this holds the final attempt's reason.
					$this->_stashTurnError($session_id, $e->getMessage(), $status_code, $retry_after);

					// A cheap rejection goes back on the queue under its OWN uuid — which is what keeps the
					// interaction's `await:queue:` gate (it holds those uuids) waiting instead of erroring, and reads
					// as `pending` so the composer stays in its working state. Deliberately NOT reportStatus(): a
					// requeued message has not failed, and routing it through reportFailure() would hand the decision
					// to the queue's `retry_max` — which is 0 here precisely so an expensive turn can never retry.
					if(!is_null($retry_delay)) {
						// Say why, where the interaction's await marker can find it. Without this the wait is a
						// silent gap in the chat, indistinguishable from a slow model. Reason only — the marker
						// states the timing itself off `available_at`, so this can't go stale.
						$queue_service->setRetryNotice(
							$queue_message->uuid,
							self::turnRetryReason($status_code),
							$retry_delay + 60
						);

						$queue_service->requeueMessage($queue_message, $retry_delay);
						continue;
					}

					// Surfacing. Force terminal on the way out so this consumer is the SOLE authority on whether an
					// agent turn re-runs: even if an admin raises `retry_max` on this queue in Setup, reportFailure()
					// still can't resurrect a turn we decided not to pay for twice.
					// MUST be set before reportStatus() — reportFailure() buffers a clone of this model to make the
					// retry decision, so a flag set afterwards is never seen.
					$queue_message->retry_count = self::RETRY_COUNT_TERMINAL;

					$queue_message->reportStatus(QueueMessageStatus::FAILED, $e->getMessage(), [
						'duration_ms' => $failed_after_ms,
						'timed_out' => $timed_out,
						'stalled' => $stalled,
						'landed_despite_error' => $landed,
					]);

					continue;
				}

				$queue_message->reportStatus(QueueMessageStatus::DONE, 'Advanced the session by one turn.', [
					'duration_ms' => $elapsed_ms(),
					'usage' => $response->getUsage(),
				]);
				$processed += $queue_message->cardinality;
				$count++;
			}
		}

		return $processed;
	}

	/**
	 * Record WHY a turn failed, in the one place the interaction can read it: a session-keyed shared-cache slot
	 * (see LlmAgentNode::turnErrorCacheKey). The worker and the interaction are different requests, `queue_message`
	 * has no column for a reason, and reportStatus() metadata is dropped for job-less messages — which every LLM
	 * turn is. Without this the interaction can only say "a queued agent turn failed", which is what it used to.
	 *
	 * Overwritten by each attempt (a retry's reason is the current one) and consumed by the node on read, so it
	 * can't resurface against a later, unrelated failure. TTL is generous but finite: a cold slot degrades to a
	 * generic sentence, never to a wrong one.
	 */
	private function _stashTurnError(string $session_id, string $message, int $status, ?int $retry_after = null) : void {
		if('' === $session_id)
			return;

		DevblocksPlatform::services()->cache()->save(
			['message' => $message, 'status' => $status, 'retry_after' => $retry_after, 'at' => time()],
			\Cerb\AutomationBuilder\Node\LlmAgentNode::turnErrorCacheKey($session_id),
			[],
			600
		);
	}

	// A retry_count set at/above any sane retry_max, so DAO_QueueMessage::reportFailure()'s getRetryDisposition()
	// returns "no retry" and the message goes terminal at once — used to force-fail an error class that can never
	// succeed on retry (auth/bad-request/malformed payload), independent of the queue's retry_max. Never persisted
	// (the terminal path writes status only, not retry_count); it only drives the disposition.
	const RETRY_COUNT_TERMINAL = 255;

	/**
	 * The ONLY statuses an agent turn may be silently re-run for: a REJECTION, where the request never reached
	 * a model, so nothing was generated and nothing was billed.
	 *
	 * The question is not "will the condition clear?" but "what did the failed attempt COST?" — because a
	 * retry is not a re-run of deterministic work, it's a fresh dice roll at full price. The same turn has been
	 * measured producing 19,881 output tokens on one attempt and running to the 32000 cap on another.
	 *
	 * So NOT 0 (network/timeout — which is OUR OWN hang-up as often as a refused connection, on a turn the
	 * provider generated and charged for in full: the >5m and >10m timeouts that got queue retries switched off
	 * in patch rev 1549), NOT 408/425, NOT 500/502/504 (a gateway fault can land mid-generation), and NOT 503
	 * (vaguer than it looks; gateways emit it for reasons other than "we didn't take your request").
	 */
	const TURN_RETRY_STATUSES = [429, 529];

	// Total provider calls for one turn, INCLUDING the first. 3 = up to two silent requeues.
	const TURN_RETRY_MAX_ATTEMPTS = 3;

	// Waits used ONLY when the provider sent no `Retry-After`, indexed by attempts already made. A guess, and
	// labelled as one — a provider that tells us the answer always wins over this.
	//
	// TO LENGTHEN: raise THESE and RETRY_AFTER_MAX_SECS, not TURN_RETRY_MAX_ATTEMPTS. Microsoft Foundry
	// enforces per-MINUTE windows, so a blind 5s retry can be too eager for it; more attempts at the same
	// spacing just burns the budget inside one window, whereas longer spacing actually waits for the bucket.
	// Before pushing the total much past ~20s, give the worker something to look at — an invisible minute
	// behind a bare spinner is a worse experience than a visible failure (see the plan's follow-up note).
	const TURN_RETRY_BLIND_BACKOFF_SECS = [5, 15];

	// The longest provider-supplied wait we'll hold an interaction open for. Beyond it, hand the human the
	// number and let them decide, rather than parking them behind a spinner for minutes.
	const RETRY_AFTER_MAX_SECS = 60;

	/**
	 * How long to wait before re-running this turn, or NULL to surface the failure to the interaction.
	 *
	 * The whole retry decision for an agent turn, in one pure function — deliberately NOT a queue `retry_max`.
	 * `cerb.llm.agent.requests` is pinned at `retry_max = 0` (patch rev 1549), which means an expensive failure
	 * is STRUCTURALLY unable to retry: `getRetryDisposition($n, 0, …)` returns `will_retry = false` whatever
	 * this function does, so a bug here can only ever fail to retry something cheap. A queue-wide policy would
	 * invert that — the expensive classes would retry by default and stay safe only while the classifier
	 * remained correct — and it would apply to `compact` messages and anything an automation's `queue.push`
	 * dropped on the same queue, none of which asked for it.
	 *
	 * Pure and public so the DB-less platform suite can test it; the DB-bound half is only the write.
	 *
	 * @param int $status HTTP status from Exception_DevblocksLlmApiError (0 = no response at all)
	 * @param int $attempts_made This message's persisted `retry_count` — 0 on the first failure
	 * @param ?int $retry_after The provider's own `Retry-After` in seconds, or null if it didn't say
	 * @param bool $landed Whether the turn reached the session anyway, despite reporting failure
	 */
	public static function getTurnRetryDelaySecs(int $status, int $attempts_made, ?int $retry_after, bool $landed) : ?int {
		if(!in_array($status, self::TURN_RETRY_STATUSES, true))
			return null;

		// The turn IS on the session — an in-stream rejection that arrived after real generation, salvaged by
		// _salvageStreamedTurn(). We've been billed for it and the node will consume it as the answer, so
		// re-running would pay twice for a turn we already have.
		if($landed)
			return null;

		if($attempts_made + 1 >= self::TURN_RETRY_MAX_ATTEMPTS)
			return null;

		// It answered, but with a wait too long to sit through. Surface it WITH the number.
		if(!is_null($retry_after) && $retry_after > self::RETRY_AFTER_MAX_SECS)
			return null;

		// Its number beats our guess: the party enforcing the limit is the only one that knows when the bucket
		// refills. Floored at 1s so a `Retry-After: 0` can't spin.
		if(!is_null($retry_after))
			return max(1, $retry_after);

		$blind = self::TURN_RETRY_BLIND_BACKOFF_SECS;

		return $blind[$attempts_made] ?? end($blind);
	}

	/**
	 * Why a turn is waiting, for the interaction to show while it waits — present tense, and NOT the same
	 * sentence as a failure. "Wait a moment and send again" is wrong here: nobody has to do anything, it's
	 * already handled. Deliberately says nothing about timing; the marker owns that (it reads `available_at`,
	 * so its number stays true however long the notice sits in cache).
	 */
	public static function turnRetryReason(int $status) : string {
		return match($status) {
			429 => 'The model provider is rate limiting requests.',
			529 => 'The model provider is overloaded.',
			default => 'The model provider could not take the request.',
		};
	}

	/**
	 * Has this session's turn ALREADY been produced? True when the session's active head is an `assistant`
	 * message — i.e. the provider replied and the memory store appended it (which also moved the head, see
	 * MemoryStore\DatabaseHistory::appendMessage → DAO_LlmAgentSession::setHead).
	 *
	 * A queue message's unit of work is "advance this session by one assistant turn", and every enqueue happens
	 * with the head on a `user` row (the user's message, or the tool results the node just appended). So an
	 * assistant head means the work is done — whoever did it — and re-calling the provider would append a
	 * DUPLICATE assistant turn. Reads the session fresh (never a cached model): the whole point is to observe a
	 * write made by a previous, failed attempt.
	 *
	 * Uses the session HEAD rather than MAX(seq) so a branched/forked session can't be judged by a message that
	 * isn't on the active path.
	 *
	 * ⚠️ Both DAO reads go through `GetRowReader` (a replica, where one is configured). This guard exists to
	 * observe a write made by a PREVIOUS attempt, so replica lag would produce a FALSE NEGATIVE — we'd miss the
	 * landed turn and duplicate it, i.e. degrade to today's behavior rather than break anything new. The gap only
	 * matters on a replicated install with lag exceeding the retry backoff; the durable fix is the request-uuid
	 * stamp (which can be read from master on the message's own row) rather than a role check on the head.
	 */
	private function _sessionTurnAlreadyLanded(string $session_id) : bool {
		if(!($session = DAO_LlmAgentSession::get($session_id)) || !$session->head_uuid)
			return false;

		if(!($head = DAO_LlmAgentMessage::get($session->head_uuid)))
			return false;

		// A STREAMING head is a turn still being written, not one that landed. Counting it as landed would
		// invert this guard's meaning: an attempt that died mid-stream would look like a completed turn, so the
		// retry would ack without ever calling the provider and the session would sit on a truncated answer
		// forever. The half-written row is resolved separately (resolveDanglingStream), not treated as done.
		return 'assistant' === $head->role && !$head->is_streaming;
	}

	/**
	 * The provider tool schemas for a session, built from its stored `tools` (the authored map) + `mounts`
	 * (resolved agent-filesystem specs → the synthesized `agent_terminal` tool). Session-only: `llm.agent` persists
	 * both onto the session before a turn, so this matches what the node used to build inline from `inputs`.
	 */
	function getSessionToolSchemas(Model_LlmAgentSession $session) : array {
		$schemas = [];

		foreach($this->_sessionToolMap($session) as $tool_name => $tool) {
			$schema = match($tool['type'] ?? null) {
				'automation' => $this->getToolSchemaForAutomation($tool_name, $tool),
				'tool' => $this->_toolSchemaCustom($tool_name, $tool),
				// Contributed by the trigger rather than authored: `ui_command` is answered by the host editor
				// in the browser, `ui_server` by the trigger itself. Both are identical on the wire to a custom
				// tool -- a name, a description, and string params -- since WHERE a result comes from is a
				// dispatch concern the provider never sees.
				'ui_command', 'ui_server' => $this->_toolSchemaCustom($tool_name, $tool),
				// An `agent_tool` reference was flattened into exactly this shape when the session froze its
				// tool set (LlmAgentNode::_resolveAgentToolEntry), so there is nothing left to look up here --
				// which is the point. Re-reading the record per turn would move the tool set, and the tool set
				// is in the cached prompt prefix.
				'agent_tool' => $this->_toolSchemaCustom($tool_name, $tool),
				'agent_terminal' => $this->_toolSchemaAgentTerminal($tool_name, $tool),
				default => null,
			};

			if($schema)
				$schemas[$tool_name] = $schema;
		}

		return $schemas;
	}

	// Normalize the session's stored `tools` (`<type>/<name>` keys, skip disabled) into a name→descriptor map,
	// then synthesize the shared `agent_terminal` tool from the stored mounts (an author tool of that name wins).
	//
	// `$session->mounts`: `[]` is enabled-with-no-volumes (a /tmp-only filesystem); only NULL means the session
	// never had one.
	private function _sessionToolMap(Model_LlmAgentSession $session) : array {
		return \Cerb\AutomationBuilder\Node\LlmAgentNode::normalizeToolMap(
			$session->tools ?? [],
			$session->mounts
		);
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

		// PHP can't tell an empty map from an empty list, and `json_encode([])` is `[]` -- which a provider
		// rejects outright ("[] is not of type 'object'"). A tool that takes no arguments is the ordinary case,
		// and so is one whose every parameter was dropped above, so normalize both back to an object.
		if(!$tool_schema['function']['parameters']['properties'])
			$tool_schema['function']['parameters']['properties'] = (object) [];

		// An empty `required` is legal but says nothing; drop it rather than ship it.
		if(empty($tool_schema['function']['parameters']['required']))
			unset($tool_schema['function']['parameters']['required']);

		return $tool_schema;
	}

	/**
	 * The synthesized agent terminal tool: a `command` line + an optional out-of-band `script`. The
	 * description carries the command vocabulary + the mount overview (a shallow `ls` per volume), so it is
	 * BUILT ONCE PER TURN and must be byte-identical across turns for a fixed mount set (or the cached prompt
	 * prefix breaks). Lists only the mounted VOLUMES — never `/tmp`, whose contents change. Ported verbatim
	 * from LlmAgentNode.
	 */
	private function _toolSchemaAgentTerminal(string $tool_name, array $tool) : ?array {
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
				// Name, mountpoint, mode, description -- and deliberately NOT a listing.
				//
				// This used to `ls` each mountpoint into the description. Two problems. It made the description a
				// function of the volume's CONTENTS, so a file appearing anywhere in a mounted volume rewrote the
				// cached prompt prefix on the next turn -- an agent with an `rw` mount could invalidate its own
				// 50K-token prefix just by writing a note. And one level is the least useful depth there is:
				// `references/` tells a reader almost nothing, while going deeper is a judgment call that depends
				// on the volume and belongs to whoever curates it, not to this function.
				//
				// A volume orients a reader through its `description` (which can name its own index file) and
				// through `search`/`find`/`ls`, which are one call away and always current. If a baked tree is
				// ever wanted, it should be an option ON the filesystem, chosen per volume with a depth.
				$overview[] = sprintf("%s  (%s, %s)%s",
					$mount['at'],
					$mount['fs']->name,
					$mount['mode'],
					$mount['fs']->description ? ' -- ' . $mount['fs']->description : ''
				);
			}

			$lead = [
				"Run ONE command line in the agent terminal, exactly as you would type it at a prompt.",
				// Models otherwise read the command list below as a menu of tools and call `search` or `read`
				// directly, with the documented flags as tool arguments. Say what the list IS before showing it.
				"Everything in the command list below is a COMMAND, not a tool of its own -- there is no separate",
				"`search` tool. Call THIS tool and put the whole line (verb, arguments and flags) in `command`:",
				"command: \"search refunds --path /docs --lines\".",
				"There is no working directory: use absolute paths (`/skills/cerb-dev/SKILL.md`) or `@<filesystem>/path`.",
				"Their contents are NOT listed below -- `ls` a mountpoint to look, or go straight to `search`/`find`",
				"to locate a file, then `read` only what you need. `/tmp` is a scratch area you can write to; a",
				"command whose output is too large to return is saved there and referenced by path.",
			];

		} else {
			// No volumes: `/tmp` alone, which is still worth having — it's a scratch pad plus the `|` pipeline,
			// so the agent can hold and transform arbitrary text without spending context on it.
			$overview = ["No volumes are mounted. `/tmp` is your whole filesystem: write text there, then read,"
				. "\nlist, or transform it with a `|` pipeline."];

			$lead = [
				"Run ONE command line in the agent terminal, exactly as you would type it at a prompt.",
				"Everything in the command list below is a COMMAND, not a tool of its own -- there is no separate",
				"`read` tool. Call THIS tool and put the whole line (verb, arguments and flags) in `command`:",
				"command: \"read /tmp/notes.md --limit 40\".",
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

		// The `cerb` CLI is opt-in, and this gate is why. help() is embedded verbatim below, so listing the
		// verb unconditionally would change the description — and therefore bust the cached prompt prefix —
		// for every agent that never asked for it.
		if($fs->hasCli()) {
			$verbs[] = 'cerb';

			$lead[] = "`cerb` is a command line into this Cerb installation itself — run `cerb help` to see what it can answer.";
		}

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
							'description' => "The ENTIRE command line as one string -- verb, arguments and flags together, written as you would type them. Flags belong in here, never as separate arguments to this tool. E.g. `ls /skills`, `search prompt caching --ext md`, `find *.md --fields title`, `read @cerb-dev/SKILL.md --offset 40 --limit 60`, `write /me/notes.md`, or `edit /me/notes.md`. May end with a `| <twig filters>` pipeline.",
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
