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
			'anthropic', 'aws_bedrock', 'docker', 'gemini', 'groq', 'huggingface',
			'ollama', 'openai', 'pinecone', 'together', 'voyage',
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
