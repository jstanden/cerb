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

	// The AI worker this turn runs AS, from `agent:`. 0 = anonymous (no agent named).
	private int $_agent_worker_id = 0;
	
	// One slot per node. The provider is NOT part of the key anymore — the session id is stable across
	// provider switches (they rewrite in place), so a single slot holds the active session for this node.
	private function _getSessionKey() : string {
		return sprintf('__session::%s', $this->node->getId());
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

			// `agent:` names an AI worker whose record supplies the provider/model/auth defaults. Resolved
			// BEFORE validation because it can satisfy the `llm:` requirement, and re-resolved every turn so a
			// resume still knows which agent it's running as.
			$this->_applyAgentDefaults();
			
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

				// `commands:` — the built-in `/commands` this agent honors, opted in by bare key
				// (`command/compact:`). OFF by default and per-node, so nothing is exposed that an author
				// didn't ask for — notably `interaction.website` (anonymous visitors) simply never declares
				// any, rather than having to opt OUT of something.
				$validation->addField('commands', 'commands:')
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
		
			// Reconcile the session on each fresh turn (create/prime, update the provider block, or switch
			// providers in place — always keeping the same stable id). Skipped on tool-loop re-entries.
			if($is_new_turn) {
				$this->_reconcileSession($automation);

				// Provision any `mounts:` volumes flagged `create@bool: yes` before they're resolved to records
				// (by _persistSessionConfig / _getTools below). Runs once per authoring turn; a resume replays
				// the stored specs and skips this.
				$this->_provisionMounts();
			}

			$llm_provider = $this->_getLlmProvider();

			$state = array_pop($this->_node_memory['stack']);
			
			// Run our next state
			if($state) {
				list($state, $state_params) = array_pad($state, 2, null);
				
				if(in_array($state, ['llm', 'tools_done'])) {
					if(!$this->_activateLLM($state, $error))
						return false;
					
					// A fresh user turn means "go": clear any stale interrupt left from a prior turn that finished
					// before its Stop was consumed, so it can't kill THIS turn at the first tools_done. Only a Stop
					// raised DURING this run (after this point) should count.
					if('llm' == $state)
						$this->_consumeInterrupt();

					// User interrupt (Stop): honored HERE because it's tree-safe — reaching `tools_done` means the
					// prior turn's tool_use blocks all have their tool_results appended (a complete tuple), so the
					// history is valid to leave. We DON'T start the next turn; we yield control back to the
					// interaction like a normal completion, with the last turn's output intact. Only `tools_done`
					// (a continuation turn) is interruptible — the first `llm` turn is the direct answer to the
					// user's message and always runs once.
					if('tools_done' == $state && $this->_consumeInterrupt()) {
						unset($this->_node_memory['stack']);

						if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
							$this->_node_memory['completed'] = true;
							return $event_success->getId();
						}

						return $this->node->getParent()->getId();
					}

					// A Stop raised while the turn was STREAMING, where nothing survived to consume. This is the
					// ordinary case, not an exotic one: a turn can spend minutes thinking, and a thinking block cut
					// before its signature can't be replayed — so the most likely moment to press Stop is also the
					// one that leaves nothing behind. Treat it as a clean stop, exactly as the `tools_done`
					// boundary does; letting a turn the user deliberately cancelled surface as "produced no
					// assistant response" would report their own click back to them as a failure.
					//
					// Checked ONLY when there's no assistant head. With a salvaged partial we fall through, apply
					// it (so its content and tool calls are handled), and the interrupt is honored a beat later at
					// `tools_done` — which keeps the partial in the node's output instead of discarding it here.
					if(!$this->_hasAssistantHead() && $this->_consumeInterrupt()) {
						unset($this->_node_memory['stack']);

						if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
							$this->_node_memory['completed'] = true;
							return $event_success->getId();
						}

						return $this->node->getParent()->getId();
					}

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

				} else if('tool_truncated' == $state) {
					// The turn hit its output ceiling mid-call (marked in _applyTurnResponse). Answer the tool_use so
					// the tuple stays paired, but do NOT execute it — the arguments were cut mid-JSON and can't be
					// trusted. No `on_tool:` branch either: there's nothing to approve, display, or return, and
					// running the branch would let an author's `tool.return:` fabricate a result for a call that
					// never happened.
					$session_key = $this->_getSessionKey();
					$session_id = $this->_dict->getKeyPath($session_key, null, '::');
					$memory_store = DevblocksPlatform::services()->llm()->getMemoryStore($session_id);

					$tool_use = new DevblocksLlmChatResponse_Tool(
						$state_params['name'] ?? '',
						$state_params['parameters'] ?? [],
						$state_params['id'] ?? ''
					);

					// Addressed to the MODEL, not a human — it's read as a tool result on the next turn, so it says
					// what happened and what to do about it.
					$llm_provider->returnTool(
						$tool_use,
						'ERROR: This tool call was not executed. The response reached its output token limit and this '
							. "call's arguments were truncated before they were complete, so they could not be trusted. "
							. 'Reissue the call with complete arguments. If the arguments are large, split the work '
							. 'into several smaller calls.',
						$memory_store
					);

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
	private function _getTools() : array {
		$llm = DevblocksPlatform::services()->llm();
		$llm_id = strval(array_key_first($this->_inputs['llm'] ?? []));
		$llm_params = is_array($this->_inputs['llm'][$llm_id] ?? null) ? $this->_inputs['llm'][$llm_id] : [];

		if($llm_id && ($provider = $llm->getProvider($llm_id, $this->_defaultCache($llm_params))))
			return $provider;
	}

	// `llm.agent` is multi-turn — the prompt prefix is re-sent and read back next turn — so default prompt
	// caching ON (Anthropic only reads a cache when we send `cache_control`; OpenAI-family auto-caches and
	// ignores this). The author can force it off with `cache@bool: no` in the `llm:` block. `llm.chat` never
	// sets this (one-shot, never re-read → no wasted cache write).
	private function _defaultCache(array $params) : array {
		if(!array_key_exists('cache', $params))
			$params['cache'] = true;

		return $params;
	}

	// Compaction policy is per target-model, so it lives in the `llm:<provider>:compaction:` block and
	// rides the session's provider_params (carried on resume — no re-passing per turn). The threshold
	// defaults to a fraction of the model's `context_window` (same source). Mirrors _getLlmProvider()'s
	// sourcing: session first, then `inputs.llm.<provider>` before the session exists.
	private function _getCompaction() : \Cerb\LLM\History\Compaction {
		$llm = DevblocksPlatform::services()->llm();
		$session_id = $this->_dict->getKeyPath($this->_getSessionKey(), null, '::');

		$params = [];
		if($session_id && ($session = \DAO_LlmAgentSession::get($session_id)) && is_array($session->provider_params) && $session->provider_params) {
			$params = $session->provider_params;
		} elseif(is_array($this->_inputs['llm'] ?? null) && $this->_inputs['llm']) {
			$llm_id = strval(array_key_first($this->_inputs['llm']));
			$params = is_array($this->_inputs['llm'][$llm_id] ?? null) ? $this->_inputs['llm'][$llm_id] : [];
		}

		$config = is_array($params['compaction'] ?? null) ? $params['compaction'] : [];

		return $llm->getCompaction($config, intval($params['context_window'] ?? 0));
	}

	// Resolve the node's session on a fresh turn, keeping a stable id. In the agentPrompt flow the session is
	// already primed on submit (`AgentPromptAwait::formatValue`), so `llm.agent` usually just pure-resumes with
	// `session_id:` and no `inputs.llm`. When `inputs.llm` IS given (legacy/standalone, or a redundant belt),
	// reconcile via the shared `llm()->reconcileSession` (create/refresh/in-place-switch) — idempotent.
	private function _reconcileSession(Model_Automation $automation) : void {
		$llm = DevblocksPlatform::services()->llm();
		$session_key = $this->_getSessionKey();

		// The cached slot (within a continuation) wins; otherwise the caller-supplied `session_id:`.
		$session_id = strval($this->_dict->getKeyPath($session_key, null, '::') ?: ($this->_inputs['session_id'] ?? ''));

		// Incoming selection (optional). `inputs.llm` = {<provider>: <params>} is the manual block and always
		// WINS; a `model:` reference to an `agent_model` record is consulted only when `llm:` is omitted — so
		// they never merge (no provider mismatch to reconcile). Either resolves to the same [provider, params].
		$incoming_provider = '';
		$incoming_params = [];

		if(is_array($this->_inputs['llm'] ?? null) && $this->_inputs['llm']) {
			$incoming_provider = strval(array_key_first($this->_inputs['llm']));
			$incoming_params = is_array($this->_inputs['llm'][$incoming_provider] ?? null) ? $this->_inputs['llm'][$incoming_provider] : [];

		} else {
			$model_error = null;

			if(null !== ($resolved = $llm->resolveModelInput($this->_inputs['model'] ?? null, $model_error)))
				list($incoming_provider, $incoming_params) = $resolved;
			elseif($model_error)
				throw new Exception_DevblocksAutomationError($model_error);
		}

		if('' !== $incoming_provider) {
			if(!($reconciled = $llm->reconcileSession($session_id, $incoming_provider, $incoming_params, $this->_sessionCreateFields($automation))))
				throw new Exception_DevblocksAutomationError("Failed to prime the LLM session.");
			$this->_dict->setKeyPath($session_key, $reconciled->uuid, '::');
			\DAO_LlmAgentSession::setAutomationIfEmpty($reconciled->uuid, $automation->id ?? 0, $this->node->getId());
			\DAO_LlmAgentSession::setAgentIfEmpty($reconciled->uuid, $this->_agent_worker_id);
			return;
		}

		// No `inputs.llm` → pure resume; the session must already exist (primed by the agentPrompt / prior turn).
		// Backfill the owning automation + node — an agentPrompt-created or caller-minted session starts without
		// lineage (its creator lacks this context); this is the first place that has it.
		if('' !== $session_id && ($session = \DAO_LlmAgentSession::get($session_id))) {
			$this->_dict->setKeyPath($session_key, $session->uuid, '::');
			\DAO_LlmAgentSession::setAutomationIfEmpty($session->uuid, $automation->id ?? 0, $this->node->getId());
			\DAO_LlmAgentSession::setAgentIfEmpty($session->uuid, $this->_agent_worker_id);
			return;
		}

		// Nothing named ANYWHERE and no session to resume → the AGENT's router if `agent:` named one, else the
		// default. This is the zero-config path: an `llm.agent:` that says nothing about models runs on whatever
		// the environment prefers, so a shipped automation never has to name one.
		//
		// ⚠ ORDER IS LOAD-BEARING: this sits AFTER the pure-resume branch. If it ran first, every turn of an
		// agentPrompt-driven conversation would re-prime to the router's first model and silently override the
		// model a human actually picked.
		if(($default_models = $llm->getAgentRouterModels($this->_agent_worker_id, $this->_dict))) {
			$router_error = null;

			if(null !== ($resolved = $llm->resolveModelInput($default_models, $router_error))) {
				list($incoming_provider, $incoming_params) = $resolved;

				$this->_router_name = $llm->getResolvedRouterName($this->_agent_worker_id);

				if(!($reconciled = $llm->reconcileSession($session_id, $incoming_provider, $incoming_params, $this->_sessionCreateFields($automation))))
					throw new Exception_DevblocksAutomationError("Failed to prime the LLM session.");

				$this->_dict->setKeyPath($session_key, $reconciled->uuid, '::');
				\DAO_LlmAgentSession::setAutomationIfEmpty($reconciled->uuid, $automation->id ?? 0, $this->node->getId());
				\DAO_LlmAgentSession::setAgentIfEmpty($reconciled->uuid, $this->_agent_worker_id);
				return;
			}
		}

		throw new Exception_DevblocksAutomationError("`llm.agent` has no models. Give it a `session_id:` (primed by an agentPrompt or a prior turn), an `llm:` block, or a `model:` reference — or configure a default agent model router.");
	}

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

			// A `length` finish means generation was severed at the output ceiling, so the LAST tool_use block's
			// arguments JSON was very likely cut mid-token — executing it would run a real tool with garbage params
			// (and on an `rw` mount that reaches `write`/`rm`). We can't just DROP it: the assistant message is
			// already persisted, and every tool_use in it must get a matching tool_result or the next request 400s
			// on the unpaired block — which would brick the session, a worse bug than the one being fixed. So it's
			// ANSWERED with an error result instead of executed; the model sees an ordinary tool failure and can
			// reissue the call. Only the final block can be truncated — earlier ones completed and run normally.
			$truncated_at = ('length' === $llm_response->getFinishReason()) ? array_key_last($tool_calls) : null;

			// An INTERRUPTED turn is a different shape of the same problem, and it can't use `$truncated_at`:
			// that logic assumes only the final block was damaged, which holds for an output-ceiling cut but not
			// for a stream stopped at an arbitrary moment. More importantly, the model never finished DECIDING —
			// a user pressed Stop, or the connection died — so even a syntactically complete call is a call it
			// may not have meant to make yet. Running any of them would take a real action nobody asked for, on
			// a turn that was explicitly cancelled. So they're ALL answered with an error instead of executed,
			// which keeps every tool_use paired (the next request 400s on an unpaired block) while doing nothing.
			$is_interrupted = \Extension_DevblocksLlmProvider::FINISH_REASON_INTERRUPTED === $llm_response->getFinishReason();

			// Push into the stack in reverse (so they pop in the order the model emitted them)
			foreach(array_reverse($tool_calls, true) as $idx => $tool_call) { /* @var $tool_call DevblocksLlmChatResponse_Tool */
				$this->_node_memory['stack'][] = [
					($is_interrupted || $idx === $truncated_at) ? 'tool_truncated' : 'tool',
					$tool_call->serialize(),
				];
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
			'finish_reason' => $llm_response->getFinishReason(),

		// A turn that stopped abnormally AND produced nothing usable has no path forward: today it completes
		// silently with `messages: []`, so the automation continues as if the agent had answered with nothing.
		// Report it instead. Deliberately narrow:
		//   - '' (unreported — legacy rows, NoHistory, providers that don't say) keeps today's behavior.
		//   - 'stop' is excluded: a model may legitimately answer with nothing, and that isn't an error.
		//   - a truncated turn WITH text or tool calls still flows; only the empty case is fatal.
		// Known limitation: `length` WITH tool calls means the arguments JSON was cut mid-token, so the tool runs
		// with garbage params. Erroring there is riskier than the bug it would fix, so it's left alone.
		//
		// This runs AFTER the token accounting above on purpose — the truncated turn was billed, and it's usually
		// the largest one in the session.
		$finish_reason = $llm_response->getFinishReason();

		$has_text = false;

		foreach($llm_response->getMessages() as $block)
			if('' !== trim(strval($block['content'] ?? '')))
				$has_text = true;

		if(!$has_text && !$tool_calls && !in_array($finish_reason, ['', 'stop', 'tool_calls'], true)) {
			// The reason has to be in the MESSAGE: the catch in activate() replaces the output var wholesale with
			// ['error' => ...], so an author on the `on_error:` branch can't read `finish_reason` from the output.
			throw new Exception_DevblocksAutomationError(match($finish_reason) {
				'length' => 'The LLM turn was truncated at its output limit (finish_reason: length) before producing a response. Raise `max_tokens:` on the model, or the model may be looping.',
				// Deliberately still an error rather than an allowlisted silent completion. A user-initiated Stop
				// does not arrive here — that's honored at the node's own boundary, which hands control back
				// without an empty turn to apply — so reaching this point means the stream DIED before producing
				// anything, and completing silently would report success for a turn that never happened.
				\Extension_DevblocksLlmProvider::FINISH_REASON_INTERRUPTED => 'The LLM turn was interrupted before producing a response. Nothing was salvageable from the partial stream.',
				default => sprintf('The LLM turn ended without producing a response (finish_reason: %s).', $finish_reason),
			});
		}
	}

	/**
	 * The built-in `/commands` this node honors, keyed by name. Shape mirrors `tools:` — `<type>/<name>:` —
	 * but the body is optional, because opting in IS the configuration:
	 *
	 *   commands:
	 *     command/compact:
	 *
	 * Deliberately NOT linked to the `agentPrompt` element's own `commands:` block. That one is a client-side
	 * concern (what to OFFER, plus text aliases it expands before submitting); this one is what the node will
	 * ACT on. An author can hint a command in the composer without the node honoring it, or honor one without
	 * hinting it. Same intentional decoupling as `references:` vs `mounts:`.
	 *
	 * @return string[] Enabled command names.
	 */
	private function _getCommands() : array {
		$commands = $this->_inputs['commands'] ?? [];

		if(!is_array($commands))
			return [];

		$names = [];

		foreach(array_keys($commands) as $key) {
			list($type, $name) = array_pad(explode('/', strval($key), 2), 2, null);

			// A bare `compact:` is as good as `command/compact:` — the type prefix exists for symmetry with
			// `tools:`, not because there's a second type yet.
			if(!$name) $name = $type;

			$name = trim(strval($name));

			if('' !== $name && in_array($name, self::getBuiltInCommands(), true))
				$names[] = $name;
		}

		return $names;
	}

	// The commands the node itself implements. An author's arbitrary `/text` is NOT one of these — it stays in
	// the message and reaches the model as ordinary prose, which is what makes an un-declared `/whatever` safe.
	static function getBuiltInCommands() : array {
		return ['compact'];
	}

	/**
	 * `/compact` argument → how much verbatim tail to keep.
	 *
	 * HARD (the default, and any unrecognized argument) keeps NONE: everything folds into one summary. That's
	 * what reaching for this deliberately means, and it's the only setting that reliably moves the number —
	 * tool_use + tool_result routinely dominate a conversation (93% of one measured session), so "keep the last
	 * few turns" keeps the bulk.
	 *
	 * SOFT applies the session's own automatic policy on demand.
	 */
	static function compactModeFor(string $args) : string {
		return DevblocksPlatform::strLower(trim($args)) === 'soft' ? 'soft' : 'hard';
	}

	/**
	 * The built-in command a fresh turn's inbound message is invoking, or ''. A command must be the LEADING
	 * token of the message — `/compact` is an instruction to us, whereas "explain /compact" is conversation.
	 *
	 * Only consulted on a fresh `llm` turn, so a mid-tool-loop message can't trigger one.
	 */
	private function _detectCommand(?string &$args=null) : string {
		$args = '';

		if(!($enabled = $this->_getCommands()))
			return '';

		$messages = $this->_inputs['messages'] ?? [];

		if(!is_array($messages) || !$messages)
			return '';

		$last = $messages[array_key_last($messages)];
		$content = is_array($last) ? ($last['content'] ?? '') : $last;

		// Content blocks (Anthropic shape) → the first text block carries the leading token.
		if(is_array($content)) {
			$text = '';

			foreach($content as $block) {
				if(is_array($block) && 'text' === ($block['type'] ?? '')) {
					$text = strval($block['text'] ?? '');
					break;
				}
			}

			$content = $text;
		}

		if(!preg_match('/^\s*\/([a-z][a-z0-9_-]*)\b(.*)$/is', strval($content), $matches))
			return '';

		$name = DevblocksPlatform::strLower($matches[1]);

		if(!in_array($name, $enabled, true))
			return '';

		// Everything after the command word is its argument line (`/compact soft`).
		$args = trim(strval($matches[2] ?? ''));

		return $name;
	}

	/**
	 * Run a built-in command INSTEAD of a provider turn. It replaces the user/assistant exchange rather than
	 * adding one: the `/compact` message is never appended to the history (it's an instruction to us, not
	 * conversation), and no answer is generated.
	 *
	 * Routed through the same queue as a turn wherever the caller can await, because it mutates the session and
	 * calls a provider — so it must not interleave with a real turn. Falls back to running inline where there's
	 * no continuation to resume (the simulator, headless).
	 */
	private function _activateCommand(string $command, string $args, Model_Automation $automation, ?string &$error=null) : bool {
		$session_id = $this->_dict->getKeyPath($this->_getSessionKey(), null, '::');

		$this->_persistSessionConfig($session_id);

		if($this->_shouldRunAsync($automation)) {
			$queue = DevblocksPlatform::services()->queue();

			if(!($uuids = $queue->enqueue('cerb.llm.agent.requests', [
				['session_id' => $session_id, 'command' => $command, 'args' => $args],
			], $error)))
				throw new Exception_DevblocksAutomationError($error ?: 'Failed to enqueue the command.');

			// Remember the head so the resume can tell a real fold from a no-op. The worker knows, but its
			// result isn't readable from here — and a command that silently does nothing is exactly what a
			// roomy context window produces (the tail budget can cover the whole conversation).
			$head_before = ($session = \DAO_LlmAgentSession::get($session_id)) ? strval($session->head_uuid) : '';

			$this->_node_memory['stack'][] = ['command_done', ['command' => $command, 'head' => $head_before]];

			$this->_dict->set('__exit', 'await');
			$this->_dict->set('__return', [
				'queue' => [
					'messages' => $uuids,
				],
			]);

			return true;
		}

		$compacted = false;

		if('compact' === $command && !DevblocksPlatform::services()->llm()->compactSession($session_id, $error, $compacted, self::compactModeFor($args)))
			throw new Exception_DevblocksAutomationError($error ?: 'The compaction failed.');

		$this->_applyCommandResult($command, $session_id, $compacted);

		return true;
	}


	// A command's node output. Deliberately the same envelope a turn produces (so an author's `on_success:`
	// doesn't branch on shape) with `messages: []` — a command generates no answer — plus what ran.
	private function _applyCommandResult(string $command, ?string $session_id, bool $compacted) : void {
		$this->_dict->set($this->_output, array_merge([
			'session_id' => $session_id,
			'messages' => [],
			'finish_reason' => '',
			'command' => $command,
			'compacted' => $compacted,
		], $this->_resolvedModelInfo($session_id)));
	}

	// The shared-cache key for a pending user interrupt of a given agent session. Cache (not the continuation
	// dict) so the `interruptAgent` action can raise it out-of-band while the agent runs, with no read-modify-write
	// race against the gate poll's continuation writes. Public so that action can key the same slot.
	static function interruptCacheKey(string $session_id) : string {
		return 'llm_agent_interrupt_' . $session_id;
	}

	// Is a user interrupt pending for THIS node's session? Consumed (cleared) on read so it fires exactly once —
	// the caller yields control this turn; a later turn starts clean. `nocache:true` reads the shared store, not a
	// stale per-request copy (the flag was set in a different request).
	private function _consumeInterrupt() : bool {
		$session_id = strval($this->_dict->getKeyPath($this->_getSessionKey(), '', '::'));

		if('' === $session_id)
			return false;

		$cache = DevblocksPlatform::services()->cache();
		$key = self::interruptCacheKey($session_id);

		if(!$cache->load($key, true))
			return false;

		$cache->remove($key);
		return true;
		]);
		
		return true;
	}
	
		$llm_response->setUsage(is_array($head->usage) ? $head->usage : []);
		// Neither usage nor the finish reason is recoverable from data_json — both live in their own columns, so
		// the async path has to re-read them here or the turn looks unreported.
		$llm_response->setFinishReason($head->finish_reason);
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