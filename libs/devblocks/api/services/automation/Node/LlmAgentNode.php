<?php
namespace Cerb\AutomationBuilder\Node;

use CerberusContexts as CerberusContextsAlias;
use DAO_Automation;
use DevblocksDictionaryDelegate;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Model_Automation;

class LlmAgentNode extends AbstractNode {
	const ID = 'llm.agent';

	// The synthesized agent-filesystem tool. ONE tool regardless of how many volumes are mounted, under a
	// fixed name, so the tool schema (and therefore the cached prompt prefix) is constant.
	const TOOL_FS = 'agent_fs';

	private array $_inputs = [];
	private string $_output = '';
	private DevblocksDictionaryDelegate $_dict;
	private array $_node_memory = [];
	
	private function _getSessionKey(\Extension_DevblocksLlmProvider $provider) : string {
		return sprintf('__session::%s::%s', $this->node->getId(), $provider::ID);
	}
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$this->_node_memory =& $node_memory;
		$this->_dict = $dict;
		
		@set_time_limit(30);
		
		try {
			$params = $automation->getParams($this->node, $this->_dict);
			$this->_inputs = $params['inputs'] ?? [];
			$this->_output = $params['output'] ?? '';
			
			// If this is a new activation, invoke the LLM first
			if(!array_key_exists('stack', $this->_node_memory)) {
				$validation = DevblocksPlatform::services()->validation();
				
				// Params validation
				
				$validation->addField('inputs', 'inputs:')
					->array();
				
				$validation->addField('output', 'output:')
					->string()
					->setRequired(true);
				
				if (false === ($validation->validateAll($params, $error)))
					throw new Exception_DevblocksAutomationError($error);
				
				$validation->reset();
				
				// Inputs validation
				
				$validation->addField('llm', 'llm:')
					->array()
					->setRequired(true);
				
				$validation->addField('messages', 'messages:')
					->array()
					->setRequired(true);
				
				$validation->addField('system_prompt', 'system_prompt:')
					->string()
					->setMaxLength(200_000)
				;
				
				$validation->addField('tools', 'tools:')
					->array();

				$validation->addField('mounts', 'mounts:')
					->array();
				
				if (false === ($validation->validateAll($this->_inputs, $error)))
					throw new Exception_DevblocksAutomationError($error);
				
				// Policy
				
				$policy = $automation->getPolicy();
				
				$action_dict = DevblocksDictionaryDelegate::instance([
					'node' => [
						'id' => $this->node->getId(),
						'type' => self::ID,
					],
					'inputs' => $this->_inputs,
					'output' => $this->_output,
				]);
				
				if (!$policy->isCommandAllowed(self::ID, $action_dict)) {
					$error = "The automation policy does not allow the `llm.agent:` command.";
					throw new Exception_DevblocksAutomationError($error);
				}
				
				$this->_node_memory['stack'] = [
					['llm', []],
				];
			}
		

				// Provision any `mounts:` volumes flagged `create@bool: yes` before they're resolved to records
				// (by _persistSessionConfig / _getTools below). Runs once per authoring turn; a resume replays
				// the stored specs and skips this.
				$this->_provisionMounts();
			$llm_provider = $this->_getLlmProvider();
			$session_key = $this->_getSessionKey($llm_provider);
			
			if(!($this->_dict->getKeyPath($session_key, null, '::'))) {
				$llm_session = new \Model_LlmAgentSession();
				$llm_session->provider = $llm_provider::ID;
				$llm_session->automation_id = $automation->id ?? 0;
				$llm_session->automation_node = $this->node->getId();
				
				if(in_array($automation->extension_id, [
					\AutomationTrigger_InteractionInternal::ID,
					\AutomationTrigger_InteractionWorker::ID,
					\AutomationTrigger_MailDraftValidate::ID,
					\AutomationTrigger_MailReplyValidate::ID,
				])) {
					$llm_session->user_type = 'worker';
					$llm_session->user_id = $this->_dict->get('worker_id', 0);
					
				} elseif($automation->extension_id == \AutomationTrigger_InteractionWebsite::ID) {
					$llm_session->user_type = 'portal_visitor';
					$llm_session->user_ip = $this->_dict->get('client_ip', '');
				}
				
				if(!($llm_session = \DAO_LlmAgentSession::create($llm_session)))
					throw new Exception_DevblocksAutomationError("Failed to create an LLM session");
				
				$this->_dict->setKeyPath($session_key, $llm_session->uuid, '::');
			}
			
			$state = array_pop($this->_node_memory['stack']);
			
			// Run our next state
			if($state) {
				list($state, $state_params) = array_pad($state, 2, null);
				
				if(in_array($state, ['llm', 'tools_done'])) {
					if(!$this->_activateLLM($state, $error))
						return false;
					
					return $this->node->getId();
				
				} else if('tool_branch' == $state) {
					$this->_node_memory['stack'][] = ['tool_return', []];
					
					if (null != ($event_tool = $this->node->getChild($this->node->getId() . ':on_tool'))) {
						return $event_tool->getId();
					} else {
						return $this->node->getId();
					}
					
				} else if('tool_return' == $state) {
					$llm = DevblocksPlatform::services()->llm();
					
					$session_key = $this->_getSessionKey($llm_provider);
					$session_id = $this->_dict->getKeyPath($session_key, null, '::');
					$memory_store = $llm->getMemoryStore($session_id);
					
					$tool_dict = $this->_dict->get('__tool', []);
					$tool_spec = new DevblocksLlmChatResponse_Tool($tool_dict['name'] ?? '', $tool_dict['parameters'] ?? [], $tool_dict['id'] ?? '');
					
					$tools = $this->_getTools();
					$tool = $tools[$tool_spec->getName()] ?? null;
					
					if('automation' == $tool_dict['type']) {
						$automator = DevblocksPlatform::services()->automation();
						
						if (!($tool_automation = DAO_Automation::getByUri($tool['uri'] ?? '', \AutomationTrigger_LlmTool::ID)))
							return false;
						
						$initial_state = [
							'inputs' => $tool_spec->getParameters() ?? [],
						];
						
						if (false === ($automation_results = $automator->executeScript($tool_automation, $initial_state, $error))) {
							$tool_response = [
								'content' => "ERROR: " . $error,
							];
						} else {
							// [TODO] Validate the return contains `content`
							$tool_response = $automation_results->get('__return', []);
						}
						
						$llm_provider->returnTool($tool_spec, $tool_response['content'] ?? '', $memory_store);
						
					} elseif(in_array($tool_dict['type'] ?? '', ['tool', 'agent_fs'])) {
						// A custom tool's result: prefer a value set dynamically by `tool.return:` in the on_tool
						// branch (stored on `__tool.content` by ToolReturnAction); else the tool's static `content:`
						// from its definition. Without this the dynamic `tool.return` value is silently discarded and
						// the model only ever sees the static content (empty for a browser-round-trip tool).
						// `agent_fs` rides the same slot: _activateTool() ran the command and stashed its output
						// there before the branch, so an author who doesn't call `tool.return:` still returns it.
						$content = array_key_exists('content', $tool_dict)
							? $tool_dict['content']
							: (is_array($tool) ? ($tool['content'] ?? '') : '');

						// `content@key:` can resolve to null (an empty/absent editor value, an unset var) — `array_key_exists`
						// keeps the explicit null, but the provider's returnTool() requires a string. Coerce to '' like the
						// `automation` branch does, so a null tool result is an empty result, not a fatal.
						$llm_provider->returnTool($tool_spec, $content ?? '', $memory_store);
					}
					
					$this->_dict->unset('__tool');
					$this->_dict->scrubKeyPathPrefix('__state|memory', $this->node->getId() . ':on_tool', '|');
					
					return $this->node->getId();
					
				} else if('tool' == $state) {
					// [TODO] We can be given hallucinated tools
					
					$tool_use = new DevblocksLlmChatResponse_Tool(
						$state_params['name'] ?? '',
							$state_params['parameters'] ?? [],
							$state_params['id'] ?? ''
					);
					
					if(!$this->_activateTool($tool_use, $error))
						return false;
					
					return $this->node->getId();
					
				} else {
					// [TODO] Unknown state
					return false;
				}
				
			// If we're done, recurse back
			} else {
				unset($this->_node_memory['stack']);
				
				// [TODO] on_success, on_error ? (set activated when recursing down)
				
				/*
				if (null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
					return $event_success->getId();
				}
				*/
				
				return $this->node->getParent()->getId();
			}
			
		} catch (Exception_DevblocksAutomationError $e) {
			$error = sprintf("[%s] %s", $this->node->getId(), $e->getMessage());
			
			if (null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if ($this->_output) {
					$this->_dict->set($this->_output, [
						'error' => $error,
					]);
				}
				
				return $event_error->getId();
			}
			
			return false;
		}
	}
	
	private function _getLlmProvider() : \Extension_DevblocksLlmProvider {
		$llm_id = array_key_first($this->_inputs['llm']);
		$llm_params = $this->_inputs['llm'][$llm_id] ?? [];
		return DevblocksPlatform::services()->llm()->getProvider($llm_id, $llm_params);
	}
	
	private function _getTools() : array {
		$tools = [];
		
		foreach(($this->_inputs['tools'] ?? []) as $tool_key => $tool) {
			list($tool_type, $tool_name) = explode('/', $tool_key);
			if (empty($tool_name)) $tool_name = $tool_type;
			
			// Conditionally disable tools
			if(array_key_exists('disabled', $tool) && $tool['disabled'])
				continue;
			
			$tool['type'] = $tool_type;
			$tools[$tool_name] = $tool;
		}

		// Mounting a filesystem provisions the one shared `agent_fs` tool over the composed VFS. An author tool
		// already using that name wins (we never silently replace it).
		if($this->_isFilesystemEnabled($session_id) && !array_key_exists(self::TOOL_FS, $tools)) {
			$tools[self::TOOL_FS] = [
				'type' => 'agent_fs',
				'mounts' => $this->_getMountSpecs($session_id),
			];
		}

		return $tools;
	}
	
	private function _getToolSchemaAutomation(string $tool_name, array $tool) : ?array {
		$llm = DevblocksPlatform::services()->llm();
		return $llm->getToolSchemaForAutomation($tool_name, $tool);
	}
	
	private function _getToolSchemaCustom(string $tool_name, array $tool) : ?array {
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
				list($param_type, $param_name) = array_pad(
					explode('/', $param_key),
					2,
					null
				);
				
				if(!$param_name)
					$param_name = $param_type;
				
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
	
	// [TODO] Cache these by signatures
	private function _getToolSchemas() : array {
		$tools = [];
		
		foreach($this->_getTools() as $tool_name => $tool) {
			$tool_type = $tool['type'] ?? null;
			
			$tool_schema = match($tool_type) {
				'automation' => $this->_getToolSchemaAutomation($tool_name, $tool),
				'tool' => $this->_getToolSchemaCustom($tool_name, $tool),
				default => null,
			};
			
			if($tool_schema)
				$tools[$tool_name] = $tool_schema;

	/**
	 * Is the agent filesystem enabled for this turn? Authoring `mounts:` AT ALL enables it — an EMPTY block
	 * is a real configuration, not an absence: it mounts nothing but `/tmp`, which gives the agent a scratch
	 * pad plus the `|` scripting pipeline over arbitrary text. So this asks whether the key was written, not
	 * whether it resolved to any volumes.
	 *
	 * On resume the session answers, where `null` (never enabled) and `[]` (enabled, /tmp only) differ.
	 */
	private function _isFilesystemEnabled(?string $session_id = null) : bool {
		if(array_key_exists('mounts', $this->_inputs))
			return true;

		if($session_id && ($session = \DAO_LlmAgentSession::get($session_id)))
			return !is_null($session->mounts);

		return false;
	}
	
	/**
	 * The resolved `mounts:` specs for this turn, in the shape `Cerb\Agent\Filesystem::fromSpecs()` takes.
	 *
	 * Mirrors `_getTools()`: the inbound `mounts:` wins, and on a pure resume (a turn that omits the block)
	 * we fall back to the session's stored mounts — the session is the source of truth, so a resume inherits
	 * its volumes instead of losing them.
	 *
	 * Mounts are SERVER-resolved from the node config and never model-controlled: the model can only name
	 * paths inside what's already mounted.
	 *
	 * Per entry `<mountpoint-key>: { … }`:
	 *   - the KEY is the mountpoint label (so `at:` defaults to `/<key>`);
	 *   - `filesystem:` names the SOURCE volume (a name, id, or `cerb:agent_filesystem:<name>` URI) and, when
	 *     given, decouples the source from the key — memory mounts a per-chat volume at a fixed `/memory-user`.
	 *     Omitted, the key doubles as the source (the common one-line-per-volume case, back-compatible);
	 *   - `mode:` is `read-only` (default) or `read-write` — writes stay gated to rw mounts in the VFS.
	 * Provisioning (`create@bool`) is handled separately in _provisionMounts() before this resolver runs.
	 */
	private function _getMountSpecs(?string $session_id = null) : array {
		$mounts_config = $this->_inputs['mounts'] ?? [];

		// Pure resume: the session stores the RESOLVED specs (an indexed list, not the authored map), so
		// they're already in fromSpecs() shape — return them verbatim rather than re-normalizing. They carry the
		// resolved `mode`, so a rw mount stays rw across the async await/resume.
		if(!is_array($mounts_config) || !$mounts_config) {
			if($session_id && ($session = \DAO_LlmAgentSession::get($session_id)))
				return $session->mounts ?? [];

			return [];
		}

		$specs = [];

		foreach($mounts_config as $key => $mount) {
			$key = DevblocksPlatform::services()->string()->strBefore(strval($key), '@');

			if('' === $key)
				continue;

			if(!is_array($mount))
				$mount = [];

			// Source: an explicit `filesystem:` (name/id/URI) wins; otherwise the mountpoint key is the source
			// too (a `cerb:` URI for the wrong context resolves to '' and falls back to the key).
			$source = '';

			if('' !== ($fs_ref = trim(strval($mount['filesystem'] ?? ''))))
				$source = self::_resolveFilesystemRef($fs_ref);

			if('' === $source)
				$source = $key;

			$mode_raw = DevblocksPlatform::strLower(trim(strval($mount['mode'] ?? '')));
			$mode = in_array($mode_raw, ['rw', 'read-write', 'readwrite'], true)
				? \Cerb\Agent\Filesystem::MODE_RW
				: \Cerb\Agent\Filesystem::MODE_RO;

			$specs[] = [
				'filesystem' => $source,
				'mode' => $mode,
				'at' => ('' !== ($at = strval($mount['at'] ?? ''))) ? $at : ('/' . $key),
			];
		}
		
		return $tools;

		return $specs;
	}

	/**
	 * Normalize a `filesystem:` value to the bare identifier fromSpecs() resolves (a name or id). Accepts a
	 * `cerb:agent_filesystem:<id-or-name>` URI and strips it to the trailing identifier; a bare name/id passes
	 * through. A `cerb:` URI for a DIFFERENT context returns '' (the caller then falls back / errors). Pure
	 * string work — no context registry — so it stays headless-testable.
	 */
	private static function _resolveFilesystemRef(string $value) : string {
		$value = trim($value);

		if(!str_starts_with($value, 'cerb:'))
			return $value;

		$parts = explode(':', $value);

		// cerb:agent_filesystem:<identifier> — the middle segment is Context_AgentFilesystem::URI (the context
		// alias, kept as a literal so this helper carries no class dependency and stays pure/testable).
		if(3 !== count($parts) || 'agent_filesystem' !== ($parts[1] ?? ''))
			return '';

		return trim(strval($parts[2] ?? ''));
	}

	/**
	 * Opt-in provisioning: for each `mounts:` entry with `create@bool: yes` whose SOURCE volume doesn't exist
	 * yet, create it (by name) so a per-user/per-agent memory volume is minted on first mount. Idempotent
	 * (create-if-missing). Runs ONCE on a fresh authoring turn, before the mounts are resolved to real records —
	 * never on resume (already provisioned + stored) and never while simulating (a sim must not create real
	 * volumes; an unresolved mount simply doesn't mount there).
	 */
	private function _provisionMounts() : void {
		$mounts_config = $this->_inputs['mounts'] ?? [];

		if(!is_array($mounts_config) || !$mounts_config)
			return;

		if($this->_dict->get('__simulate', false))
			return;

		$strings = DevblocksPlatform::services()->string();

		foreach($mounts_config as $key => $mount) {
			if(!is_array($mount))
				continue;

			if(!$strings->toBool($mount['create'] ?? false))
				continue;

			$key = $strings->strBefore(strval($key), '@');

			$source = '';
			if('' !== ($fs_ref = trim(strval($mount['filesystem'] ?? ''))))
				$source = self::_resolveFilesystemRef($fs_ref);
			if('' === $source)
				$source = $key;

			if('' === $source)
				continue;

			// A numeric ref is an id — you can't create a volume by id (there's no name to mint it with).
			if(ctype_digit($source)) {
				if(\DAO_AgentFilesystem::get(intval($source)))
					continue;

				throw new Exception_DevblocksAutomationError(sprintf("Cannot create the agent filesystem `%s` — a numeric id references an existing volume; give a name to create one.", $source));
			}

			// Already exists? (Case-insensitive name match, mirroring Filesystem::fromSpecs().)
			foreach(\DAO_AgentFilesystem::getAll() as $fs) {
				if(0 == strcasecmp($fs->name, $source))
					continue 2;
			}

			// Create by name. The DAO's name rule is: start with a letter, then letters/digits/dashes — surface
			// a clear error rather than inserting an invalid handle. (A leading-letter rule also means a name can
			// never collide with the numeric-id lookup handled above.)
			if(!preg_match('/^[A-Za-z][A-Za-z0-9-]*$/', $source))
				throw new Exception_DevblocksAutomationError(sprintf("Cannot create the agent filesystem `%s` — a name must start with a letter and contain only letters (A-Z, a-z), digits (0-9), and dashes.", $source));

			\DAO_AgentFilesystem::create([
				\DAO_AgentFilesystem::NAME => $source,
			]);
		}
	}

	// The continuation-scoped /tmp scratch store for this node's agent_fs tool. Keyed like the session slot
	// (`::` delimiter keeps a dotted node id whole) so two llm.agent nodes don't share a scratch area. It rides
	// the automation dict, so it survives awaits + the tool loop within a run; a fresh run starts empty.
	private function _getTmpKey() : string {
		return sprintf('__agent_fs_tmp::%s', $this->node->getId());
	}

	/**
	 * @param string $state
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateLLM(string $state, ?string &$error=null) : bool {
		$llm = DevblocksPlatform::services()->llm();
		
		$llm_provider = $this->_getLlmProvider();
		
		// Memory
		
		$session_key = $this->_getSessionKey($llm_provider);
		$session_id = $this->_dict->getKeyPath($session_key, null, '::');
		$memory_store = $llm->getMemoryStore($session_id);
		
		// Messages
		
		// [TODO] Configurable history length/strategy
		$memory_messages = $memory_store->getMessages(limit: 10);
		
		// If we're not running after tools, add the next message
		if('llm' == $state) {
			foreach($this->_inputs['messages'] ?? [] as $new_message) {
				$memory_messages[] = $new_message;
				$memory_store->appendMessage($new_message);
			}
		}
		
		// LLM
		
		$llm_response = $llm_provider->chatCompletion(
			$memory_messages,
			$this->_inputs['system_prompt'] ?? '',
			array_values($this->_getToolSchemas()),
			$memory_store
		);
		
		// Tools
		
		if(($tool_calls = $llm_response->getToolCalls())) {
			// After the tools finish we need to invoke the LLM again
			$this->_node_memory['stack'][] = ['tools_done', []];
			
			// Push into the stack in reverse
			foreach(array_reverse($tool_calls) as $tool_call) { /* @var $tool_call DevblocksLlmChatResponse_Tool */
				$this->_node_memory['stack'][] = ['tool', $tool_call->serialize()];
			}
		}

		// Durable running count of the active context window (post-response, post any fold this turn). Cheap for
		// callers to read (e.g. the AgentPrompt progress bar) without re-summing. Prefer the provider's EXACT
		// numbers for this turn: input + cache_read is the whole prompt just sent (system + tools + history, post
		// any compaction), and output is the answer — together, the true current context size. Fall back to the
		// token_est-sum estimate only when the provider reported no usage.
		// Prompt = ALL input components: fresh `input` + `cache_read` + `cache_write` (Anthropic splits the prompt
		// across these three; with our rolling cache breakpoint `input` is ~2 and the bulk is read/write). Context
		// total = prompt + output. Omitting cache_write undercounts by the freshly-cached delta each turn.
		$usage = $llm_response->getUsage();
		$prompt_tokens = intval($usage['input'] ?? 0) + intval($usage['cache_read'] ?? 0) + intval($usage['cache_write'] ?? 0);
		$token_usage = $prompt_tokens > 0
			? $prompt_tokens + intval($usage['output'] ?? 0)
			: $this->_getCompaction()->estimateContextTokens($memory_store, $llm_provider);
		$this->_dict->set('__llm_token_usage', $token_usage);

		// Denormalize the active-branch estimate onto the session so the GUI (transcript list / dev page)
		// shows context usage without re-summing the tree; also stamps last activity for recency sorting.
		if($session_id)
			\DAO_LlmAgentSession::setTokenUsage($session_id, $token_usage);

		// Return an abstract list of messages
		$this->_dict->set($this->_output, [
			'session_id' => $session_id,
			'messages' => $llm_response->getMessages(),
		]);
		
		return true;
	}
	
		$llm_response->setUsage(is_array($head->usage) ? $head->usage : []);
	/**
	 * @param DevblocksLlmChatResponse_Tool $tool_spec
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateTool(DevblocksLlmChatResponse_Tool $tool_spec, ?string &$error=null) : bool {
		$llm = DevblocksPlatform::services()->llm();
		
		$llm_provider = $this->_getLlmProvider();
		
		$tools = $this->_getTools();
		$tool =	$tools[$tool_spec->getName()] ?? null;
		
		$session_key = $this->_getSessionKey($llm_provider);
		$session_id = $this->_dict->getKeyPath($session_key, null, '::');
		$memory_store = $llm->getMemoryStore($session_id);
		
		if($tool) {
			$tool_type = $tool['type'] ?? null;
			
			$this->_dict->set('__tool', [
				'id' => $tool_spec->getId(),
				'name' => $tool_spec->getName(),
				'parameters' => $tool_spec->getParameters(),
				'type' => $tool_type,
			]);

			if(in_array($tool_type, ['automation', 'tool'])) {
				// Run the custom `on_tool:` branch
				if (null != ($this->node->getChild($this->node->getId() . ':on_tool'))) {
					$this->_node_memory['stack'][] = ['tool_branch', []];
					return true;
				}

			} elseif('agent_fs' == $tool_type) {
				// The filesystem command itself runs server-side, right here — there's no browser round trip to
				// produce a result, so a transcript replay never re-runs it. There's no working directory (see
				// the tool description): every command evaluates from the root, so `search` spans all mounts and
				// paths must be absolute.
				$params = $tool_spec->getParameters();

				// The scratch /tmp store rides the automation dict, so it survives awaits and the tool loop
				// across this run (a fresh run starts clean). Passing it in is also what makes `/tmp` exist as a
				// writable mount, and gives large output somewhere to spill instead of being truncated away.
				$tmp_key = $this->_getTmpKey();
				$tmp_store = $this->_dict->getKeyPath($tmp_key, [], '::');

				if(!is_array($tmp_store))
					$tmp_store = [];

				// A `script` is the multi-line alternative to a trailing `|`; the interpreter rejects both at once.
				$script = strlen(strval($params['script'] ?? '')) ? strval($params['script']) : null;

				// Out-of-band write bodies, kept off the command line. `content` (write/append) and `replace`
				// (edit) are the same channel -- both are "the text going into the file" -- so they share the
				// $payload slot; `edit`'s search needle rides its own arg. All writes stay rw-mount gated below.
				$content = strlen(strval($params['content'] ?? '')) ? strval($params['content']) : null;
				$find = array_key_exists('find', $params) ? strval($params['find']) : null;
				$replace = array_key_exists('replace', $params) ? strval($params['replace']) : null;
				$payload = $content ?? $replace;

				$result = \Cerb\Agent\Filesystem::fromSpecs($tool['mounts'] ?? [], ['tmp' => $tmp_store])
					->exec(strval($params['command'] ?? ''), '/', $payload, $script, $find);

				// Persist the store only when a command changed it (write/append/rm, or a spill).
				if(array_key_exists('tmp', $result))
					$this->_dict->setKeyPath($tmp_key, $result['tmp'], '::');

				// Some commands legitimately produce no output; providers reject an empty tool result.
				$content = strval($result['output'] ?? '') ?: '(no output)';

				// Hand the output to the `on_tool:` branch on `__tool.content` (the same slot `tool.return:`
				// writes), so the branch can render a status from `{{__tool.parameters.command}}` +
				// `{{__tool.content}}` — and can still override the result if it wants to.
				$tool_dict = $this->_dict->get('__tool', []);
				$tool_dict['content'] = $content;
				$this->_dict->set('__tool', $tool_dict);

				// Then run `on_tool:` like any other tool type. This is what lets the automation BREATHE: an
				// agent that fires five commands in one turn otherwise does it all in a single request and hits
				// the time limit. The branch's await ends the request (re-rendering the transcript for a status
				// update) and the continuation resumes at `tool_return`.
				if(null != ($this->node->getChild($this->node->getId() . ':on_tool'))) {
					$this->_node_memory['stack'][] = ['tool_branch', []];
					return true;
				}

				// No branch to run — return to the model immediately.
				$tool_response = ['content' => $content];
				$this->_dict->unset('__tool');
			}

		} else {
			$tool_response = [
				'content' => 'ERROR: This tool does not exist.'
			];
		}
		
		$llm_provider->returnTool($tool_spec, $tool_response['content'] ?? '', $memory_store);
		return true;
	}
}