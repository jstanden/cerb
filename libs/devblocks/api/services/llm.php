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
	abstract function appendMessage(array $message) : bool;
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
	private array $_tool_calls = [];
	private array $_tool_results = [];
	
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
	

	// The cerb-icons name for this provider's mark; concrete providers with a brand logo override it.
	function getIcon() : string {
		return 'bot';
	}

	// A brand background color for this provider's mark (white glyph on top), for avatar chips. Empty
	// means "no brand color" — callers fall back to a hashed/seeded color. Branded providers override.
	function getIconColor() : string {
		return '';
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
}
