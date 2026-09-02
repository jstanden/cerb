<?php
namespace Cerb\AutomationBuilder\Node;

use DAO_Automation;
use DevblocksDictionaryDelegate;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use DevblocksPlatform;
use Exception_DevblocksAutomationError;
use Exception_DevblocksLlmApiError;
use Model_Automation;

class LlmAgentNode extends AbstractNode {
	const ID = 'llm.agent';

	// The synthesized agent terminal tool. ONE tool regardless of how many volumes are mounted, under a
	// fixed name, so the tool schema (and therefore the cached prompt prefix) is constant. Named for the
	// terminal rather than the filesystem because it also hosts the `cerb` CLI.
	const TOOL_TERMINAL = 'agent_terminal';

	private array $_inputs = [];
	private string $_output = '';

	// Extra keys to merge into the output var when this activation throws. The catch replaces that var wholesale
	// with `['error' => …]`, so a failure with something an author can branch on (a rate limit vs a bad request,
	// the message that didn't send) has nowhere else to put it.
	private array $_output_error_extra = [];

	private DevblocksDictionaryDelegate $_dict;
	private array $_node_memory = [];

	// Whether this activation is the start of a USER turn rather than a tool-loop re-entry. Captured in
	// activate() because it's derived from the absence of `stack` in node memory, and activate() SETS that
	// key before dispatching -- so anything downstream that re-derived it would always read false.
	private bool $_is_new_turn = false;

	// This activation's automation. Held because `_getTools()` needs the TRIGGER — a trigger can contribute
	// tools of its own (an agent pane's host editor supplies one per UI command), and _getTools() is reached
	// from three places that don't all have the automation to hand.
	private ?Model_Automation $_automation = null;

	// The AI worker this turn runs AS, from `agent:`. 0 = anonymous (no agent named).
	private int $_agent_worker_id = 0;

	// That agent's config, already resolved for this SURFACE (its defaults merged with the per-surface
	// overrides). Empty when no agent is named, so every consumer can read it without branching.
	private array $_agent_config = [];

	// The surface this turn is running on, held so the config resolution and the prompt-cache gate can't
	// disagree about which overrides applied.
	private string $_agent_surface = '';
	
	// One slot per node. The provider is NOT part of the key anymore — the session id is stable across
	// provider switches (they rewrite in place), so a single slot holds the active session for this node.
	private function _getSessionKey() : string {
		return sprintf('__session::%s', $this->node->getId());
	}
	
	function activate(Model_Automation $automation, DevblocksDictionaryDelegate $dict, array &$node_memory, ?string &$error=null) : string|false {
		$this->_node_memory =& $node_memory;
		$this->_dict = $dict;
		$this->_automation = $automation;

		// Per-activation: the node instance outlives one activation within a run, and a later failure must not
		// inherit an earlier one's detail.
		$this->_output_error_extra = [];

		// Returning from an on_success/on_error/on_simulate branch (which ran to completion) — hand
		// control back to the parent. Guarded here, before the fresh-activation check, so we don't
		// re-invoke the LLM.
		if(array_key_exists('completed', $this->_node_memory)) {
			unset($this->_node_memory['completed']);
			return $this->node->getParent()->getId();
		}

		@set_time_limit(30);
		
		try {
			$params = $automation->getParams($this->node, $this->_dict);
			$this->_inputs = $params['inputs'] ?? [];
			$this->_output = $params['output'] ?? '';

			// `agent:` names an AI worker whose record supplies the provider/model/auth defaults. Resolved
			// BEFORE validation because it can satisfy the `llm:` requirement, and re-resolved every turn so a
			// resume still knows which agent it's running as.
			$this->_applyAgentDefaults();
			
			// A fresh turn (not a tool-loop re-entry) validates, reconciles the session, and inits the stack.
			$is_new_turn = !array_key_exists('stack', $this->_node_memory);
			$this->_is_new_turn = $is_new_turn;

			if($is_new_turn) {
				// Whatever the last failure handed back to the composer, this turn supersedes it — the worker is
				// sending again. Cleared HERE rather than on success so a draft can't outlive the message it
				// belongs to and re-seed a composer several turns later.
				$this->_dict->unset('__llm_retry');

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
				
				// `llm:` (manual) always WINS: `model:` is consulted only when `llm:` is omitted (see
				// _reconcileSession), so the two never merge and a provider mismatch can't arise.
				//
				// It is no longer REQUIRED. With no `session_id:`, no `llm:` and no `model:`, every AVAILABLE
				// agent model is offered in `priority` order -- the zero-config path. That moves "no models
				// anywhere" from a parse-time error to a runtime one, deliberately: whether any model exists
				// isn't knowable when the script is validated.
				$validation->addField('llm', 'llm:')
					->array();

				// `model: <agent_model name>: <overrides>` — the recommended way to source the provider block:
				// reference a first-class model record instead of hand-authoring `llm:`. Resolved in
				// _reconcileSession; `llm:` overrides it when both are present.
				$validation->addField('model', 'model:')
					->array();

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

				// `terminal:` — configuration for the agent_terminal tool itself, as opposed to `mounts:`,
				// which says what it can reach. Its `cerb:` block names the CLI namespaces this agent has.
				// Nested rather than a top-level `cerb:` because a bare `cerb` key says nothing in a Cerb
				// automation, and `commands:` already means the chat's /slash commands.
				$validation->addField('terminal', 'terminal:')
					->array();

				// `commands:` — the built-in `/commands` this agent honors, opted in by bare key
				// (`command/compact:`). OFF by default and per-node, so nothing is exposed that an author
				// didn't ask for — notably `interaction.website` (anonymous visitors) simply never declares
				// any, rather than having to opt OUT of something.
				$validation->addField('commands', 'commands:')
					->array();

				$validation->addField('agent', 'agent:')
					->string()
					->setMaxLength(255);

				$validation->addField('session_id', 'session_id:')
					->string()
					->setMaxLength(36);
				
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

				// Simulating: run the `on_simulate:` branch instead of calling the LLM (mirrors actions).
				// The `completed` guard at the top of activate() returns to the parent afterward.
				if($this->_dict->get('__simulate', false)
					&& null != ($event_simulate = $this->node->getChild($this->node->getId() . ':on_simulate'))) {
					$this->_node_memory['completed'] = true;
					return $event_simulate->getId();
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

					// A built-in `/command` REPLACES this turn rather than preceding it — the message is an
					// instruction to us, never appended to the history, and no answer is generated. Checked only
					// on a fresh `llm` turn, so a mid-tool-loop message can't trigger one, and only for commands
					// the author opted into via `commands:` (an undeclared `/whatever` stays ordinary prose and
					// reaches the model unchanged).
					if('llm' == $state && ($command = $this->_detectCommand($command_args))) {
						if(!$this->_activateCommand($command, strval($command_args), $automation, $error))
							return false;

						// The async path parked on `await:queue:` and will resume into `command_done`; the inline
						// path is already finished. Either way, re-enter rather than starting a provider turn.
						return $this->node->getId();
					}

					// A provider turn can exceed the FPM terminate window, so in a resumable interaction we run it
					// OFF-request: enqueue the turn, `await:queue:` until a worker lands the assistant response on
					// the session, then resume into `llm_consume`. The simulator (no workers to drain the queue) and
					// headless/one-off runs (no continuation to resume an await) fall back to the inline turn.
					if($this->_shouldRunAsync($automation)) {
						if(!$this->_startLLMAsync($state, $error))
							return false;
					} else {
						if(!$this->_activateLLM($state, $error))
							return false;
					}

					return $this->node->getId();

				} else if('command_done' == $state) {
					// A failed command reaches us instead of ending the automation (see _resumeAwaitGate). Nothing
					// to rewind — a command that failed didn't advance the head — so just report it through the
					// author's `on_error:` rather than ending a chat over housekeeping.
					if(($failure = $this->_getQueueFailure($state_params)))
						$this->_failTurn($failure, []);

					// The awaited command finished in a worker (the queue gate confirmed DONE). Nothing to
					// reconstitute — a command produces no assistant turn. A moved head means the fold really
					// happened; unchanged means it was a legitimate no-op, and the author's `on_success:` can
					// tell the difference via `{{<output>.compacted}}` instead of showing nothing at all.
					$session_id = $this->_dict->getKeyPath($this->_getSessionKey(), null, '::');
					$session = $session_id ? \DAO_LlmAgentSession::get($session_id) : null;

					$changed = $session && strval($session->head_uuid) !== strval($state_params['head'] ?? '');

					$this->_applyCommandResult(strval($state_params['command'] ?? ''), $session_id, $changed);

					return $this->node->getId();

				} else if('llm_consume' == $state) {
					// Did this turn leave an answer? Asked against the head we ENQUEUED on, not "is the head an
					// assistant row" — for a fresh `llm` turn the head at enqueue time is already the PREVIOUS
					// turn's answer (the user message rides the queue payload and is appended by the worker), so
					// a turn that died before appending anything would otherwise read as landed and re-apply the
					// last answer as this one's.
					$turn_landed = $this->_turnLandedSince($state_params['head'] ?? null);

					// A Stop raised while the turn was STREAMING, where nothing survived to consume. This is the
					// ordinary case, not an exotic one: a turn can spend minutes thinking, and a thinking block cut
					// before its signature can't be replayed — so the most likely moment to press Stop is also the
					// one that leaves nothing behind. Treat it as a clean stop, exactly as the `tools_done`
					// boundary does; letting a turn the user deliberately cancelled surface as "produced no
					// assistant response" would report their own click back to them as a failure.
					//
					// Checked ONLY when nothing landed. With a salvaged partial we fall through, apply it (so its
					// content and tool calls are handled), and the interrupt is honored a beat later at
					// `tools_done` — which keeps the partial in the node's output instead of discarding it here.
					if(!$turn_landed && $this->_consumeInterrupt()) {
						unset($this->_node_memory['stack']);

						if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
							$this->_node_memory['completed'] = true;
							return $event_success->getId();
						}

						return $this->node->getParent()->getId();
					}

					// The turn FAILED and left nothing behind (the gate handed control back rather than ending the
					// automation — see _resumeAwaitGate). Put the session back the way it was and report it, so a
					// transient provider failure costs the worker a click instead of their conversation.
					if(!$turn_landed && ($failure = $this->_getQueueFailure($state_params)))
						$this->_failTurn($failure, $state_params);

					// The awaited queue turn landed on the session (the engine's queue gate confirmed DONE before
					// resuming here). Reconstitute the response from the session head and run the shared back half.
					if(!$this->_consumeLLMAsync($state_params ?? [], $error))
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
					
					$session_key = $this->_getSessionKey();
					$session_id = $this->_dict->getKeyPath($session_key, null, '::');
					$memory_store = $llm->getMemoryStore($session_id);
					
					$tool_dict = $this->_dict->get('__tool', []);
					$tool_spec = new DevblocksLlmChatResponse_Tool($tool_dict['name'] ?? '', $tool_dict['parameters'] ?? [], $tool_dict['id'] ?? '');

					$tools = $this->_getTools($session_id);
					$tool = $tools[$tool_spec->getName()] ?? null;

					// Component-contributed tools resolve HERE, after `on_tool:` — so if the branch answered via
					// `tool.return:` that stands and nothing else runs. That is how a DENIED approval works, and
					// it doubles as an author override when testing. (`agent_terminal` runs earlier, before the
					// branch; these deliberately don't, so a gate is possible.)
					if(!array_key_exists('content', $tool_dict)) {
						// Answered by the BROWSER: suspend on the round-trip and resume at `ui_command_return`.
						if('ui_command' == ($tool_dict['type'] ?? '')) {
							$this->_startUiCommand($tool_spec, $tool);

							return $this->node->getId();
						}

						// Answered HERE, by the trigger — no round-trip, so fill the same slot and fall through.
						if('ui_server' == ($tool_dict['type'] ?? '')) {
							$tool_dict['content'] = $this->_runTriggerTool($tool_spec, $tool);
							$this->_dict->set('__tool', $tool_dict);
						}
					}

					if('agent_tool' == $tool_dict['type']) {
						$automator = DevblocksPlatform::services()->automation();

						$record = \DAO_AgentTool::getByName(strval($tool['tool'] ?? ''));

						// `on_tool:` already answered -- an approval gate that declined, or an author overriding
						// a tool while testing. Its answer stands and the automation never runs, which is what
						// makes a human-in-the-loop approval possible without touching the advertised tool set.
						if(array_key_exists('content', $tool_dict)) {
							$tool_response = [
								'content' => $tool_dict['content'],
							];

						// The set was frozen when the conversation started, so a tool deleted or switched off
						// since then is still advertised -- shrinking it would bust the cached prompt prefix for
						// everyone mid-conversation. Refuse the CALL instead and let the model read why.
						} elseif(!$record || !$record->isUsable()) {
							$tool_response = [
								'content' => 'ERROR: This tool is not available.',
							];

						// No automation on the record: the calling script's `on_tool:` was meant to answer it with
						// `tool.return:`. That is all an inline tool ever was -- now with its name, description
						// and parameters authored on a record instead of restated in every script, which is also
						// what leaves room for the cases only a script can answer (pushing an interaction,
						// synthesizing a form for a missing argument, gating on an approval). Reaching here means
						// the branch did not answer, which is an authoring bug worth saying out loud rather than
						// handing the model an empty result.
						} elseif(!$record->hasAutomation()) {
							$tool_response = [
								'content' => 'ERROR: This tool has no automation, and `on_tool:` did not answer it with `tool.return:`.',
							];

						} elseif(!($tool_automation = DAO_Automation::getByUri($record->uri, \AutomationTrigger_AgentTool::ID))) {
							DevblocksPlatform::logError(sprintf(
								"[LLM] Agent tool `%s` references an automation that no longer exists: %s",
								$record->name,
								$record->uri
							));

							$tool_response = [
								'content' => 'ERROR: This tool is no longer available.',
							];

						} else {
							$initial_state = $this->_agentToolState($record, $tool, $tool_spec, $session_id);

							if(false === ($automation_results = $automator->executeScript($tool_automation, $initial_state, $error))) {
								$tool_response = [
									'content' => "ERROR: " . $error,
								];
							} else {
								$tool_response = $automation_results->get('__return', []);
							}
						}

						$llm_provider->returnTool($tool_spec, $tool_response['content'] ?? '', $memory_store);

					} elseif('automation' == $tool_dict['type']) {
						$automator = DevblocksPlatform::services()->automation();
						
						// Renamed or deleted since this turn's schema was built. Answer the model, the way an
						// unknown tool name does -- aborting here strands a resumable conversation on a
						// reference the author can still fix, and returns before `__tool` is cleared below.
						if (!($tool_automation = DAO_Automation::getByUri($tool['uri'] ?? '', \AutomationTrigger_LlmTool::ID))) {
							DevblocksPlatform::logError(sprintf(
								"[LLM] Tool `%s` references an automation that no longer exists: %s",
								$tool_spec->getName(),
								$tool['uri'] ?? ''
							));
							
							$tool_response = [
								'content' => 'ERROR: This tool is no longer available.',
							];
							
						} else {
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
						}
						
						$llm_provider->returnTool($tool_spec, $tool_response['content'] ?? '', $memory_store);
						
					} elseif(in_array($tool_dict['type'] ?? '', ['tool', 'agent_terminal', 'ui_command', 'ui_server'])) {
						// A custom tool's result: prefer a value set dynamically by `tool.return:` in the on_tool
						// branch (stored on `__tool.content` by ToolReturnAction); else the tool's static `content:`
						// from its definition. Without this the dynamic `tool.return` value is silently discarded and
						// the model only ever sees the static content (empty for a browser-round-trip tool).
						// `agent_terminal` rides the same slot: _activateTool() ran the command and stashed its output
						// there before the branch, so an author who doesn't call `tool.return:` still returns it.
						// `ui_command` rides it too, filled a beat later — by `ui_command_return` once the browser
						// answered, or by the author's own `tool.return:` if `on_tool:` declined to let it run.
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

				} else if('ui_command_return' == $state) {
					// The browser answered the `uiCommand` await. Its value arrives as a TOP-LEVEL dict key named
					// for the element (that's how every await form element reports), so take it, drop the key so a
					// whole editor's contents don't linger in the chat dict, and hand it to `tool_return` on the
					// same `__tool.content` slot `tool.return:` writes.
					$var = strval($state_params['var'] ?? '');
					$content = $var ? $this->_dict->get($var, '') : '';

					if($var)
						$this->_dict->unset($var);

					// Providers reject an empty tool result, and '' is genuinely ambiguous here: a host that threw,
					// a command it doesn't implement, and an honestly empty answer (`cerb_get_icon_geometry` on an
					// unknown icon) are indistinguishable by the time the value reaches us. So say the same thing
					// `agent_terminal` says for a command that produced nothing, and don't guess at a cause.
					$content = strval($content) ?: '(no output)';

					$tool_dict = $this->_dict->get('__tool', []);
					$tool_dict['content'] = $content;
					$this->_dict->set('__tool', $tool_dict);

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
				
			// Completed successfully.
			} else {
				unset($this->_node_memory['stack']);

				// Run the `on_success:` branch once (if present); the `completed` guard at the top of
				// activate() then hands control back to the parent when that branch finishes.
				if(null != ($event_success = $this->node->getChild($this->node->getId() . ':on_success'))) {
					$this->_node_memory['completed'] = true;
					return $event_success->getId();
				}

				return $this->node->getParent()->getId();
			}
			
		} catch (Exception_DevblocksAutomationError $e) {
			$message = $e->getMessage();

			// A provider failure carries the provider's -- or Guzzle's -- own text, which routinely names the
			// endpoint URL, an api key riding a query string, request ids, and echoed payload fragments. This
			// string is printed straight into the transcript, and on a website interaction that transcript is
			// an anonymous visitor's screen. Log it where an admin can read it; show only the class.
			if($e instanceof Exception_DevblocksLlmApiError) {
				DevblocksPlatform::logError(sprintf(
					'[llm.agent] session=%s provider turn failed (status=%d): %s',
					strval($this->_dict->getKeyPath($this->_getSessionKey(), '', '::')),
					$e->statusCode,
					$message
				));

				$message = \_DevblocksLlmService::formatTurnFailure($e->statusCode, $e->retryAfter);
			}

			$error = sprintf("[%s] %s", $this->node->getId(), $message);
			
			if (null != ($event_error = $this->node->getChild($this->node->getId() . ':on_error'))) {
				if ($this->_output) {
					$this->_dict->set($this->_output, array_merge([
						'error' => $error,
					], $this->_output_error_extra));
				}

				// Run the on_error branch, then (via the `completed` guard) return to the parent —
				// matching how actions handle on_error. Drop any partial stack first.
				unset($this->_node_memory['stack']);
				$this->_node_memory['completed'] = true;
				return $event_error->getId();
			}
			
			return false;
		}
	}
	
	// The provider comes from the SESSION (its stored provider + provider_params) once reconciled —
	// that's the single source of truth. Falls back to `inputs.llm` only before the session exists.
	// Sourcing keys on `session->provider` (not full priming): a session with a provider but empty params
	// still yields a valid provider instance (auth failures surface at request time, not as a TypeError).
	private function _getLlmProvider() : \Extension_DevblocksLlmProvider {
		$llm = DevblocksPlatform::services()->llm();

		$session_id = $this->_dict->getKeyPath($this->_getSessionKey(), null, '::');

		if($session_id && ($session = \DAO_LlmAgentSession::get($session_id)) && $session->provider) {
			$params = is_array($session->provider_params) ? $session->provider_params : [];

			if(($provider = $llm->getProvider($session->provider, $this->_defaultCache($params))))
				return $provider;
		}

		$llm_id = strval(array_key_first($this->_inputs['llm'] ?? []));
		$llm_params = is_array($this->_inputs['llm'][$llm_id] ?? null) ? $this->_inputs['llm'][$llm_id] : [];

		if($llm_id && ($provider = $llm->getProvider($llm_id, $this->_defaultCache($llm_params))))
			return $provider;

		throw new Exception_DevblocksAutomationError("`llm.agent` has no LLM provider — prime the session (an `llm:` block, or an agentPrompt) or pass `inputs.llm`.");
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

		// The zero-config pool, narrowed by the AGENT's model policy for this surface. That's what makes an
		// agent's Models field reach a turn without the script wiring an `llm.router:` -- the same way its
		// tools and mounts arrive. With no agent, or an agent with no query, this is every available model in
		// the admin's `priority` order, exactly as before.
		$pool_error = null;

		if(($default_models = \Cerb\Agent\Config::resolveModelPool($this->_agent_config, $pool_error))) {
			$router_error = null;

			if(null !== ($resolved = $llm->resolveModelInput($default_models, $router_error))) {
				list($incoming_provider, $incoming_params) = $resolved;

				if(!($reconciled = $llm->reconcileSession($session_id, $incoming_provider, $incoming_params, $this->_sessionCreateFields($automation))))
					throw new Exception_DevblocksAutomationError("Failed to prime the LLM session.");

				$this->_dict->setKeyPath($session_key, $reconciled->uuid, '::');
				\DAO_LlmAgentSession::setAutomationIfEmpty($reconciled->uuid, $automation->id ?? 0, $this->node->getId());
				\DAO_LlmAgentSession::setAgentIfEmpty($reconciled->uuid, $this->_agent_worker_id);
				return;
			}
		}

		// Naming the agent's query when it's the reason nothing matched: "make a model available" is misleading
		// advice when there ARE models and this agent is restricted away from all of them.
		if(($agent_query = trim(strval($this->_agent_config['models_query'] ?? ''))))
			throw new Exception_DevblocksAutomationError(sprintf(
				"`llm.agent` has no models: this agent is restricted to `%s`, which matches no available agent model.",
				$agent_query
			));

		throw new Exception_DevblocksAutomationError("`llm.agent` has no models. Give it a `session_id:` (primed by an agentPrompt or a prior turn), an `llm:` block, or a `model:` reference -- or make at least one agent model available.");
	}

	// Ownership/lineage columns stamped on a session the node creates.
	private function _sessionCreateFields(Model_Automation $automation) : array {
		$fields = [
			'automation_id' => $automation->id ?? 0,
			'automation_node' => $this->node->getId(),
			// The acting AI worker. Paired with the user_* fields below (whom it serves), this is the
			// actor/target pair the memory layer attributes entries with.
			'agent_id' => $this->_agent_worker_id,
		];

		if(in_array($automation->extension_id, [
			\AutomationTrigger_InteractionInternal::ID,
			\AutomationTrigger_InteractionWorker::ID,
			\AutomationTrigger_InteractionWorkerAgent::ID,
			\AutomationTrigger_MailDraftValidate::ID,
			\AutomationTrigger_MailReplyValidate::ID,
		])) {
			$fields['user_type'] = 'worker';
			$fields['user_id'] = $this->_dict->get('worker_id', 0);
		} elseif($automation->extension_id == \AutomationTrigger_InteractionWebsite::ID) {
			$fields['user_type'] = 'portal_visitor';
			$fields['user_ip'] = $this->_dict->get('client_ip', '');
		}

		return $fields;
	}
	
	/**
	 * THE normalizer for a `tools:` config: `<type>/<name>` keys to a `name => entry` map, with `type` set on
	 * each entry, plus the synthesized `agent_terminal` when a filesystem is mounted.
	 *
	 * Shared because this was implemented three times -- here, `_DevblocksLlmService::_sessionToolMap()`, and
	 * `Model_LlmAgentSession::getToolMap()` -- and the three disagreed. Each divergence is resolved toward
	 * whichever was already right, so adding a tool SOURCE now teaches one place instead of four:
	 *
	 *   - `is_array()`, or a `tools@key:` loop that emits a scalar for one entry fatals the turn.
	 *   - `@` annotations stripped from the key. Without it a tool authored as `automation/foo@x` is advertised
	 *     to the provider as `foo@x` while the transcript looks up `foo`, so its `labels` and `icon` silently
	 *     vanish. Adopting the strip here CHANGES what such a tool is called on the wire -- that is the fix.
	 *   - `explode` limit 2, so `automation/foo/bar` is the tool `foo/bar` everywhere rather than `foo` in one
	 *     place and `foo/bar` in another.
	 *
	 * $mounts: NULL = the filesystem was never enabled; [] = enabled with no volumes (/tmp only) -- see
	 * `_isFilesystemEnabled()`.
	 *
	 * $skip_disabled: false for a DISPLAY projection. A transcript's map is a label lookup for calls that
	 * already happened, and dropping a since-disabled tool from it would re-render an old, legitimate call as
	 * "Unknown tool". Dispatch and schema builders must leave it true.
	 */
	static function normalizeToolMap(array $tools_config, ?array $mounts = null, bool $skip_disabled = true) : array {
		$tools = [];

		foreach($tools_config as $tool_key => $tool) {
			if(!is_array($tool))
				continue;

			$clean_key = explode('@', strval($tool_key), 2)[0];

			list($tool_type, $tool_name) = array_pad(explode('/', $clean_key, 2), 2, null);

			if(empty($tool_name))
				$tool_name = $tool_type;

			if($skip_disabled && array_key_exists('disabled', $tool) && $tool['disabled'])
				continue;

			$tool['type'] = $tool_type;
			$tools[$tool_name] = $tool;
		}

		// Mounting a filesystem provisions the one shared `agent_terminal` tool over the composed VFS. An author
		// tool already using that name wins (we never silently replace it).
		if(!is_null($mounts) && !array_key_exists(self::TOOL_TERMINAL, $tools)) {
			$tools[self::TOOL_TERMINAL] = [
				'type' => 'agent_terminal',
				'mounts' => $mounts,
			];
		}

		return $tools;
	}
	
	private function _getTools(?string $session_id = null) : array {
		// Use the inbound `tools:`; on a pure-resume (no `inputs.tools`) fall back to the session's
		// stored tools (the session is the source of truth, like system_prompt / provider_params).
		// Omitting `tools:` on resume therefore inherits the prior turn's tools rather than sending none.
		$tools_config = $this->_inputs['tools'] ?? [];

		if(!$tools_config && $session_id && ($session = \DAO_LlmAgentSession::get($session_id)))
			$tools_config = $session->tools;

		$tools_config = $this->_withResolvedAgentTools(
			$this->_withTriggerTools($this->_withAgentTools($tools_config)),
			$session_id
		);

		return self::normalizeToolMap(
			$tools_config,
			$this->_isFilesystemEnabled($session_id) ? $this->_getMountSpecs($session_id) : null
		);
	}

	/**
	 * The AGENT's `tools:` folded into this turn's, by the same first-wins-by-NAME rule `_withTriggerTools()`
	 * uses -- so precedence reads script > agent > trigger.
	 *
	 * The script wins because it is the most specific statement of intent: an author who names a tool here has
	 * said something about THIS conversation that the agent record can't know. The agent beats the trigger for
	 * the same reason one step down -- the trigger's contribution is structural (the host editor's own
	 * commands), and a person configuring an agent is making a choice.
	 *
	 * FIRST-WINS, not a deep merge, and that's deliberate: what this merge is FOR is the union of tools with
	 * distinct names. Blending two entries that share a name would mean merging the per-node properties of an
	 * automation reference, which isn't a meaningful operation -- and the two sides may not even name the same
	 * automation, in which case the survivor would carry a `description` written for a different one.
	 *
	 * Note this differs from how a record merges its OWN layers: there, `Config::resolve()` deep-merges a
	 * surface's entry onto the default's, because a surface block is explicitly an override OF THAT ENTRY.
	 * Across independently authored sources, a shared name is not proof of a shared tool.
	 */
	private function _withAgentTools(array $tools_config) : array {
		if(!($agent_tools = $this->_agent_config['tools'] ?? []) || !is_array($agent_tools))
			return $tools_config;

		$authored_names = [];

		foreach(array_keys($tools_config) as $tool_key) {
			list($tool_type, $tool_name) = array_pad(explode('/', strval($tool_key), 2), 2, null);
			$authored_names[$tool_name ?: $tool_type] = true;
		}

		foreach($agent_tools as $tool_key => $tool) {
			list($tool_type, $tool_name) = array_pad(explode('/', strval($tool_key), 2), 2, null);

			if(!array_key_exists($tool_name ?: $tool_type, $authored_names))
				$tools_config[$tool_key] = is_array($tool) ? $tool : [];
		}

		return $tools_config;
	}

	/**
	 * Overlay the tools contributed by this automation's TRIGGER onto a `tools:` config, keyed `<type>/<name>`
	 * like any authored entry — so everything downstream (the flatten below, the provider schema, the
	 * transcript's labels) reads them without knowing where they came from.
	 *
	 * Today that's an agent pane handing the chat one `ui_command/` tool per UI command its host editor
	 * answers, which is what lets a chat beside an editor read and rewrite it with nothing in its script.
	 *
	 * Same rule as `agent_terminal`: an author tool of the same NAME wins, always — matched on the name rather
	 * than the whole key, since `tool/cerb_get_fields` and `ui_command/cerb_get_fields` are one collision, not two tools.
	 *
	 * Duck-typed rather than an interface: this file is platform code and the only trigger that answers lives
	 * in cerberusweb.core — the same arrangement `_renderFormElements()` uses for `getFormComponentMeta()`.
	 */
	private function _withTriggerTools(array $tools_config) : array {
		if(!$this->_automation)
			return $tools_config;

		$trigger = $this->_automation->getTriggerExtension();

		if(!$trigger || !method_exists($trigger, 'getLlmAgentTools'))
			return $tools_config;

		if(!is_array($trigger_tools = $trigger->getLlmAgentTools($this->_dict)) || !$trigger_tools)
			return $tools_config;

		$authored_names = [];

		foreach(array_keys($tools_config) as $tool_key) {
			list($tool_type, $tool_name) = array_pad(explode('/', strval($tool_key), 2), 2, null);
			$authored_names[$tool_name ?: $tool_type] = true;
		}

		foreach($trigger_tools as $tool_key => $tool) {
			list($tool_type, $tool_name) = array_pad(explode('/', strval($tool_key), 2), 2, null);

			if(!array_key_exists($tool_name ?: $tool_type, $authored_names))
				$tools_config[$tool_key] = $tool;
		}

		return $tools_config;
	}

	/**
	 * The `tools:` key prefixes that are NOT a tool record. Everything else IS one: the key is simply the
	 * record's name, which is also the name the model calls.
	 *
	 * That the key is the record name is what lets the KATA editor autocomplete inside the entry -- the path
	 * alone says which record it is. `DAO_AgentTool` refuses these words as record names, so the two can never
	 * be confused.
	 */
	const TOOL_RESERVED_PREFIXES = ['agent_terminal', 'agent_tool', 'automation', 'tool', 'ui_command', 'ui_server'];

	/**
	 * The scope an `agent.tool` automation runs with: the call's arguments, and the environment it was called
	 * in -- which tool, which agent, whom that agent serves, and where the conversation is happening.
	 *
	 * The model's arguments over the declared defaults, using `+` (left wins), arriving as `inputs.*` -- the
	 * automation declares them, so they validate against its own `inputs:` block exactly as any other caller's
	 * would. A script that wants to force one rewrites `__tool.parameters` in `on_tool:`, which runs first and
	 * is what `$tool_spec` is rebuilt from here.
	 */
	private function _agentToolState(\Model_AgentTool $record, array $tool, DevblocksLlmChatResponse_Tool $tool_spec, ?string $session_id) : array {
		// The declared `default:` per argument, read off the FROZEN schema rather than the automation, so a
		// running conversation keeps the defaults it started with -- the same reason the schema is frozen.
		$defaults = [];

		foreach((is_array($tool['parameters'] ?? null) ? $tool['parameters'] : []) as $param_key => $param) {
			if(is_array($param) && array_key_exists('default', $param))
				$defaults[strval(explode('/', strval($param_key), 2)[1] ?? $param_key)] = $param['default'];
		}

		$state = [
			'inputs' => ($tool_spec->getParameters() ?? []) + $defaults,
			'tool__context' => \Context_AgentTool::ID,
			'tool_id' => $record->id,
			'transcript_uuid' => strval($session_id),
			'transcript_surface' => $this->_agent_surface,
		];

		if($this->_agent_worker_id) {
			$state['agent__context'] = \CerberusContexts::CONTEXT_WORKER;
			$state['agent_id'] = $this->_agent_worker_id;
		}

		if($session_id && ($session = \DAO_LlmAgentSession::get($session_id))) {
			// Which KIND of conversation this is, so a tool can refuse to run somewhere it doesn't belong.
			$state['transcript_trigger'] = strval($session->getAutomation()?->extension_id ?? '');

			if('worker' === $session->user_type && $session->user_id) {
				$state['transcript_user__context'] = \CerberusContexts::CONTEXT_WORKER;
				$state['transcript_user_id'] = intval($session->user_id);
			}

			if($session->agent_id) {
				$state['transcript_agent__context'] = \CerberusContexts::CONTEXT_WORKER;
				$state['transcript_agent_id'] = intval($session->agent_id);
			}
		}

		return $state;
	}

	/**
	 * Bake every tool-record reference into a self-contained descriptor -- ONCE, and never again for the life
	 * of the session.
	 *
	 * The provider schema is built from the session's stored `tools`, and the tool set sits in the CACHED PROMPT
	 * PREFIX beside the system prompt. A tool whose schema was re-read from its record each turn would throw
	 * that cache away the moment an admin touched the record, for every conversation open at the time. So the
	 * record is read once and frozen: an edit reaches NEW conversations, and running ones keep the tools they
	 * started with.
	 *
	 * The same reasoning is why a tool going `disabled` MID-conversation does not shrink the set -- removing it
	 * would bust the prefix just as surely. The call is refused at execution instead, with an error the model
	 * reads back, which is also the shape a guardrail denial takes.
	 *
	 * A conversation starting NOW is the opposite case and is handled below: a disabled or missing tool is
	 * never admitted, so the model is never shown something it will only be refused.
	 *
	 * The authored key is the record's name; what gets STORED is the canonical `agent_tool/<name>` with the
	 * name in the body too. Resolving the family on first parse and freezing it is what keeps every later
	 * reader -- dispatch, provider schema, transcript -- off the record entirely.
	 */
	private function _withResolvedAgentTools(array $tools_config, ?string $session_id) : array {
		$frozen = [];

		if($session_id && ($session = \DAO_LlmAgentSession::get($session_id)))
			$frozen = is_array($session->tools ?? null) ? $session->tools : [];

		$out = [];

		foreach($tools_config as $tool_key => $tool) {
			$tool_key = strval($tool_key);
			$base = strval(explode('/', explode('@', $tool_key, 2)[0], 2)[0]);

			// Another family, or already canonical: pass it through untouched.
			if(!is_array($tool) || in_array($base, self::TOOL_RESERVED_PREFIXES, true)) {
				$out[$tool_key] = $tool;
				continue;
			}

			$canonical = 'agent_tool/' . $base;

			// Already frozen onto this session: reuse verbatim. `parameters` is the marker, because every
			// resolved entry has one -- even a tool that takes no arguments.
			if(is_array($frozen[$canonical] ?? null) && array_key_exists('parameters', $frozen[$canonical])) {
				$out[$canonical] = $frozen[$canonical];
				continue;
			}

			// FIRST resolve. A tool that is switched off, or whose record is gone, must never enter the set at
			// all -- the freeze is about not SHRINKING a conversation that already has one, and says nothing
			// about admitting one to a conversation starting now. Advertising it and refusing the call is the
			// mid-conversation compromise, not the intended experience: the model would see the tool, spend a
			// turn calling it, and be told no.
			if(!($record = \DAO_AgentTool::getByName($base)) || !$record->isUsable()) {
				DevblocksPlatform::logError(sprintf(
					"[LLM] Skipping agent tool `%s`: %s",
					$base,
					$record ? 'it is disabled' : 'no such tool record'
				));

				continue;
			}

			$out[$canonical] = $this->_resolveAgentToolEntry(strval($base), $tool);
		}

		return $out;
	}

	/**
	 * One tool-record reference plus its record, flattened into the shape every other tool already speaks --
	 * `description` + `parameters` -- so the provider schema builder reads it with no special case.
	 *
	 * The arguments come from the ANSWERING AUTOMATION's own `inputs:` block. That block is the tool's contract:
	 * it already carries a description, `required`, `allowed_values` and a `default` per argument, and it is
	 * what the script reads back as `inputs.<name>`. Declaring them a second time on the record would mean two
	 * places to change and one of them silently wrong -- the automation would be made to conform to a contract
	 * written somewhere else, which is the opposite of what these records are for.
	 *
	 * The record supplies what an automation has nowhere to put: the name the model calls, the description it
	 * reads, the transcript's icon and wording. The REFERENCE may override that presentation, nothing more --
	 * a mount that has to force an argument rewrites `__tool.parameters` in the calling script's `on_tool:`,
	 * which runs before the tool and is what `$tool_spec` is rebuilt from.
	 *
	 * A missing record or automation still yields an entry. The reference is kept so execution can name what
	 * broke, and the tool advertises no arguments rather than vanishing from a prefix that is already cached.
	 */
	private function _resolveAgentToolEntry(string $record_name, array $entry) : array {
		$resolved = $entry;
		$resolved['tool'] = $record_name;

		if(!($record = \DAO_AgentTool::getByName($record_name))) {
			$resolved['description'] = trim(strval($entry['description'] ?? ''));
			$resolved['parameters'] = [];
			return $resolved;
		}

		$labels = is_array($entry['labels'] ?? null) ? $entry['labels'] : [];

		$resolved['uri'] = strval($record->uri);
		$resolved['description'] = trim(strval($entry['description'] ?? '')) ?: strval($record->description);
		$resolved['icon'] = trim(strval($entry['icon'] ?? '')) ?: $record->getDisplayIcon();
		$resolved['labels'] = [
			'active' => trim(strval($labels['active'] ?? '')) ?: strval($record->label_active),
			'summary' => trim(strval($labels['summary'] ?? '')) ?: strval($record->label_summary),
		];
		$resolved['parameters'] = self::_toolParametersFromAutomation($record);

		return $resolved;
	}

	/**
	 * The answering automation's `inputs:` as the model-facing parameter map.
	 *
	 * Every input is advertised as a `string` regardless of its declared type, which is what
	 * `getToolSchemaForAutomation()` always did: a provider takes JSON scalars, and a `record/` input is a name
	 * or an id to the model either way.
	 */
	private static function _toolParametersFromAutomation(\Model_AgentTool $record) : array {
		if(!$record->hasAutomation())
			return [];

		if(!($automation = DAO_Automation::getByUri($record->uri, \AutomationTrigger_AgentTool::ID)))
			return [];

		$parameters = [];

		foreach($automation->getInputsMeta() as $input) {
			if(!is_array($input) || '' === ($key = strval($input['key'] ?? '')))
				continue;

			$parameter = ['description' => strval($input['description'] ?? '')];

			if($input['required'] ?? false)
				$parameter['required'] = true;

			if(is_array($input['allowed_values'] ?? null) && $input['allowed_values'])
				$parameter['enum'] = array_values(array_map('strval', $input['allowed_values']));

			if(array_key_exists('default', $input) && is_scalar($input['default']))
				$parameter['default'] = $input['default'];

			$parameters['string/' . $key] = $parameter;
		}

		return $parameters;
	}

	/**
	 * A hash of the AUTHORED `llm.agent:` inputs that produce the request prefix -- the cheap way to ask
	 * "did anyone actually change this?" without rebuilding the prefix to find out.
	 *
	 * The KATA is where intent lives, so only the KATA is hashed. Everything downstream of it is excluded on
	 * purpose:
	 *
	 *   - `_withTriggerTools()`'s additions -- a host shipping a new command is not somebody deciding that
	 *     THIS conversation should change.
	 *   - `_getMountSpecs()`'s resolution -- the same authored block, normalized.
	 *   - the contents of any mounted volume -- an agent writing a note into its own `rw` mount must not
	 *     invalidate its own prefix.
	 *   - the role assets behind `_triggerSystemPrompt()` -- a release improving a role reaches NEW
	 *     conversations, not open ones.
	 *
	 * The `cerb` CLI needs no term of its own: it is authored under `terminal:`, which is hashed whole.
	 *
	 * Returns NULL when this turn has nothing at all to hash -- no authored inputs AND no agent, which is a
	 * pure resume replaying the session's stored config (see `_getMountSpecs()`). Hashing absent inputs would
	 * read as "the author deleted everything" and recompose on every single resume, the exact opposite of the
	 * point.
	 *
	 * A turn that names an agent but authors nothing else DOES hash, and stably: the value moves only when the
	 * agent record does. That's the point -- it's what carries an edit into a conversation.
	 *
	 * Caveat worth knowing: an input built from a placeholder that changes per turn moves this hash every
	 * turn, and the prefix with it. `getInputsMeta()` on the agent-pane trigger already warns against
	 * interpolating a changing value into `system_prompt:`; this is the mechanism that makes it expensive.
	 */
	private function _prefixInputsGate() : ?string {
		// Fixed key order -- json_encode preserves insertion order, and a hash whose stability depends on
		// array ordering is a hash that silently misses on every turn.
		//
		// The AGENT is in here too, by id + the surface + its row's `updated_at`. Editing an agent is someone's
		// INTENT, unlike the role assets excluded above -- if changing an agent's instructions didn't reach the
		// conversation you were looking at when you changed them, the editor would read as broken. The cost is
		// that an edit recomposes open conversations on their next turn, which is the trade we want.
		$authored = [
			'system_prompt' => $this->_inputs['system_prompt'] ?? null,
			'tools' => $this->_inputs['tools'] ?? null,
			'mounts' => $this->_inputs['mounts'] ?? null,
			'terminal' => $this->_inputs['terminal'] ?? null,
			'agent' => $this->_agent_worker_id
				? [$this->_agent_worker_id, $this->_agent_surface, \DAO_Agent::get($this->_agent_worker_id)['updated_at'] ?? 0]
				: null,
		];

		foreach($authored as $value) {
			if(!is_null($value))
				return hash('sha256', strval(json_encode($authored)));
		}

		return null;
	}

	/**
	 * The system prompt contributed by this automation's TRIGGER, composed ahead of the author's own
	 * `system_prompt:` -- the sibling of `_withTriggerTools()`, and for the same reason.
	 *
	 * Today that's an agent pane handing the chat the role for whichever editor it sits beside, plus the
	 * inventory of that host's tools in prose. Generating it into each chat's script instead meant it was
	 * frozen at authoring time: improving a role never reached a deployed automation, and the only remedy
	 * would have been upgrade patches rewriting people's scripts.
	 *
	 * The trigger is handed the names of the volumes actually mounted, because whether it can honestly point
	 * at a reference volume depends on that, and this file has no business knowing any volume by name.
	 *
	 * Duck-typed rather than an interface, like `getLlmAgentTools()`: this file is platform code and the only
	 * trigger that answers lives in cerberusweb.core.
	 */
	private function _triggerSystemPrompt(?string $session_id) : string {
		if(!$this->_automation)
			return '';

		$trigger = $this->_automation->getTriggerExtension();

		if(!$trigger || !method_exists($trigger, 'getLlmAgentSystemPrompt'))
			return '';

		return trim(strval($trigger->getLlmAgentSystemPrompt($this->_dict, $this->_getMountedFilesystemNames($session_id))));
	}

	/**
	 * The names of the volumes this turn actually mounts -- resolved, so an id or a differently-cased ref
	 * still answers by name, and a spec naming a missing or disabled volume doesn't.
	 *
	 * describeSpecs() rather than reading the specs directly: it already skips the reserved CLI entry and
	 * reports per-spec state, so a mount that reached nothing can't be reported as present.
	 *
	 * @return string[]
	 */
	private function _getMountedFilesystemNames(?string $session_id) : array {
		$names = [];

		foreach(\Cerb\Agent\Filesystem::describeSpecs($this->_getMountSpecs($session_id)) as $described) {
			if(\Cerb\Agent\Filesystem::MOUNT_OK === $described['state'] && $described['filesystem'])
				$names[] = $described['filesystem']->name;
		}

		return $names;
	}

	/**
	 * Resolve `agent:` to the AI worker this turn runs AS — IDENTITY ONLY.
	 *
	 * Models are deliberately NOT sourced here: they're their own `agent_model` records, decoupled from the
	 * persona, so the same model serves a named agent and an anonymous "Agent" alike. What the agent supplies
	 * is who the turn is attributed to — the transcript's name/avatar and, later, memory's `actor`.
	 *
	 * Accepts an `@mention`, a bare worker id, or a `cerb:worker:<id|mention>` URI — the same shapes
	 * `mounts:`' `filesystem:` takes.
	 */
	private function _applyAgentDefaults() : void {
		$this->_agent_worker_id = 0;
		$this->_agent_config = [];
		$this->_agent_surface = '';

		// Normalize a scalar to its string form BEFORE validation runs (this is called ahead of it).
		//
		// `agent:` validates as a string, but the normal way to write it now is `agent@key: agent_id` off the
		// trigger's own scope -- and that yields an INT, which failed with "`agent:` is not a string" before
		// the resolver (which takes a numeric id happily) ever saw it. A non-scalar is left alone so an array
		// still fails as the authoring mistake it is.
		if(array_key_exists('agent', $this->_inputs) && is_scalar($this->_inputs['agent']))
			$this->_inputs['agent'] = strval($this->_inputs['agent']);

		if('' === ($agent_ref = trim(strval($this->_inputs['agent'] ?? ''))))
			return;

		if(!($agent = self::resolveAgentWorker($agent_ref)))
			throw new Exception_DevblocksAutomationError(sprintf("`llm.agent` couldn't resolve `agent: %s` to an AI worker.", $agent_ref));

		$this->_agent_worker_id = $agent->id;

		// Resolved ONCE per activation, for the surface this turn is running on. Every consumer below
		// (system prompt, tools, mounts, terminal, commands) reads this same tree, so they can't disagree
		// about which overrides applied.
		$this->_agent_surface = $this->_agentSurfaceKey();
		$this->_agent_config = \Cerb\Agent\Config::forWorker($agent->id, $this->_agent_surface);
	}

	/**
	 * WHERE this turn is running, as the agent's config names it -- an agent-pane component today
	 * (`mail_reply`, `icon`), an event later.
	 *
	 * Asked of the TRIGGER, duck-typed like `getLlmAgentTools()`, because only the trigger knows what its
	 * callers mean. That's what keeps the pane's `component` vocabulary out of this file: a trigger with no
	 * surfaces answers nothing and the agent contributes its defaults, which is the right answer for an
	 * `llm.agent:` that isn't in a pane at all.
	 */
	private function _agentSurfaceKey() : string {
		if(!$this->_automation)
			return '';

		$trigger = $this->_automation->getTriggerExtension();

		if(!$trigger || !method_exists($trigger, 'getAgentSurfaceKey'))
			return '';

		return trim(strval($trigger->getAgentSurfaceKey($this->_dict)));
	}

	/**
	 * `@mention` | worker id | `cerb:worker:<id>` | `cerb:worker:<mention>` → the AI worker, or null. A HUMAN
	 * worker is rejected: a person isn't a model config, and silently running as one would attribute memory to them.
	 *
	 * PUBLIC because the `llmTranscript` await's `agent:` block resolves the same reference for DISPLAY (byline
	 * + avatar). One resolver so the two can't drift: an author who writes `agent: '@cerb'` in both places must
	 * get the same worker, and a form accepted by one must be accepted by the other.
	 */
	public static function resolveAgentWorker(string $value) : ?\Model_Worker {
		$value = trim($value);
		$worker = null;

		if(str_starts_with($value, '@')) {
			$worker = \DAO_Worker::getByAtMention(DevblocksPlatform::strLower(ltrim($value, '@')));

		} else if(str_starts_with($value, 'cerb:')) {
			// Parsed as a string rather than through parseURI(), for the same reason _resolveFilesystemRef()
			// does: no context-registry dependency, so it stays pure and headless-testable.
			$parts = explode(':', $value);

			if(3 === count($parts) && 'worker' === ($parts[1] ?? '')) {
				// An id OR an @mention handle. The mention form is what makes a SHIPPED package portable: a
				// worker id differs per environment, so `cerb:worker:cerb` is the only form we can hardcode.
				// The framework's generic resolver already accepts aliases here
				// (Context_Worker::getContextIdFromAlias) -- this local parser just hadn't kept up.
				//
				// The `@` is trimmed here too: autocomplete offers `@handle`, so `cerb:worker:@handle` is what
				// you get by pasting a suggestion into the URI form. Accepting only the bare handle there would
				// fail for a reason nobody could see.
				$handle = ltrim($parts[2], '@');

				$worker = is_numeric($handle)
					? \DAO_Worker::get(intval($handle))
					: \DAO_Worker::getByAtMention(DevblocksPlatform::strLower($handle))
					;
			}

		} else if(is_numeric($value)) {
			$worker = \DAO_Worker::get(intval($value));

		} else {
			// A bare handle is the @mention without its sigil -- the common typo, and unambiguous
			$worker = \DAO_Worker::getByAtMention(DevblocksPlatform::strLower($value));
		}

		if(!$worker || !$worker->is_ai || $worker->is_disabled)
			return null;

		return $worker;
	}

	/**
	 * Is the agent filesystem enabled for this turn? Authoring `mounts:` AT ALL enables it — an EMPTY block
	 * is a real configuration, not an absence: it mounts nothing but `/tmp`, which gives the agent a scratch
	 * pad plus the `|` scripting pipeline over arbitrary text. So this asks whether the key was written, not
	 * whether it resolved to any volumes.
	 *
	 * On resume the session answers, where `null` (never enabled) and `[]` (enabled, /tmp only) differ.
	 */
	private function _isFilesystemEnabled(?string $session_id = null) : bool {
		// `terminal:` alone is a real configuration too: the CLI plus /tmp and the `|` pipeline, no volumes.
		if(array_key_exists('mounts', $this->_inputs) || array_key_exists('terminal', $this->_inputs))
			return true;

		// An agent that mounts anything enables it too -- otherwise its volumes resolve and then reach no tool,
		// which looks exactly like a broken mount.
		if(($this->_agent_config['mounts'] ?? []) || ($this->_agent_config['terminal'] ?? []))
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
		// The agent contributes mounts and a terminal the same way it contributes tools. Merged by NORMALIZED
		// volume name so an authored `docs:` overrides an agent's `docs:` instead of mounting it twice, and so
		// an annotated key (`docs@optional:`) is the same volume as a bare one.
		$mounts_config = [];

		foreach([$this->_agent_config['mounts'] ?? [], $this->_inputs['mounts'] ?? []] as $source) {
			if(!is_array($source))
				continue;

			foreach($source as $key => $mount) {
				$key = DevblocksPlatform::services()->string()->strBefore(strval($key), '@');

				if('' !== $key)
					$mounts_config[$key] = is_array($mount) ? $mount : [];
			}
		}

		// One level deep, so a script adding a namespace keeps the agent's -- and `<name>@bool: no` still turns
		// one off, which _getCliSpec() honors.
		$terminal_config = array_replace_recursive(
			is_array($this->_agent_config['terminal'] ?? null) ? $this->_agent_config['terminal'] : [],
			is_array($this->_inputs['terminal'] ?? null) ? $this->_inputs['terminal'] : []
		);

		$has_terminal = array_key_exists('terminal', $this->_inputs) || (bool) $terminal_config;

		// Pure resume: the session stores the RESOLVED specs (an indexed list, not the authored map), so
		// they're already in fromSpecs() shape — return them verbatim rather than re-normalizing. They carry the
		// resolved `mode`, so a rw mount stays rw across the async await/resume. The reserved `cerb` entry
		// rides along in that same stored list, so the CLI survives a resume with no extra plumbing.
		if((!is_array($mounts_config) || !$mounts_config) && !$has_terminal) {
			if($session_id && ($session = \DAO_LlmAgentSession::get($session_id)))
				return $session->mounts ?? [];

			return [];
		}

		$specs = [];

		foreach($mounts_config as $key => $mount) {
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

		if($has_terminal && ($cli_spec = self::_getCliSpec($terminal_config['cerb'] ?? null)))
			$specs[] = $cli_spec;

		return $specs;
	}

	/**
	 * The authored `terminal: cerb:` block as a reserved spec entry, or null when it enables nothing.
	 *
	 * Each key is a CLI namespace (`records:`), its value that namespace's options. A namespace absent from
	 * the block is not reachable -- this is the whole boundary in v1, so it's an allowlist, never a filter.
	 * `<name>@bool: no` turns one off without deleting its config.
	 */
	private static function _getCliSpec($config) : ?array {
		$namespaces = [];

		if(is_array($config))
		foreach($config as $key => $value) {
			$key = DevblocksPlatform::services()->string()->strBefore(strval($key), '@');

			if('' === $key)
				continue;

			if(is_bool($value) && !$value)
				continue;

			$namespaces[$key] = is_array($value) ? $value : [];
		}

		// An empty `cerb:` still enables the filesystem (see _isFilesystemEnabled) but adds no CLI, so don't
		// persist a spec that would advertise a verb with nothing behind it.
		return $namespaces ? ['kind' => \Cerb\Agent\Filesystem::KIND_CLI, 'cerb' => $namespaces] : null;
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

	// The continuation-scoped /tmp scratch store for this node's agent_terminal tool. Keyed like the session
	// slot (`::` delimiter keeps a dotted node id whole) so two llm.agent nodes don't share a scratch area. It
	// rides the automation dict, so it survives awaits + the tool loop within a run; a fresh run starts empty.
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

		$session_key = $this->_getSessionKey();
		$session_id = $this->_dict->getKeyPath($session_key, null, '::');
		$memory_store = $llm->getMemoryStore($session_id);

		$this->_persistSessionConfig($session_id);

		// LLM — advance the session by one provider turn. On a fresh turn (`llm`) the inbound user messages are
		// appended to the managed history; a tool-loop re-entry (`tools_done`) appends nothing (the tool results
		// are already in the store). History-append + compaction + the provider call all happen inside
		// nextSessionTurn, which appends the assistant turn back onto the session.
		$new_messages = ('llm' == $state) ? ($this->_inputs['messages'] ?? []) : [];

		if(!($llm_response = $llm->nextSessionTurn($session_id, $new_messages, $error)))
			throw new Exception_DevblocksAutomationError($error ?: 'The LLM turn could not run.');

		$this->_applyTurnResponse($llm_response, $session_id, $memory_store, $llm_provider);

		return true;
	}

	/**
	 * Persist this turn's config onto the SESSION *before* the turn runs — `nextSessionTurn()` (and the async
	 * worker) rebuild the send-list (system prompt, tools, mounts, compaction, history) purely from the session,
	 * so the session must be the source of truth by the time it's called. (This is also what lets the turn run
	 * headless from just a session_id.) Shared by the sync (_activateLLM) and async (_startLLMAsync) paths.
	 */
	private function _persistSessionConfig(?string $session_id) : void {
		if(!$session_id)
			return;

		// System prompt: composed ONCE, then frozen for the life of the session -- the same rule as the
		// provider block (see _getLlmProvider: the session is the source of truth once set, and the input is
		// only consulted to establish it). It used to be rewritten from `inputs.system_prompt` every turn,
		// which meant a trigger's contribution could never be stable, the most cacheable part of the prompt
		// prefix churned, and the stored value was only the author's fragment rather than what was sent.
		//
		// Safe to key on emptiness: nothing else writes `system_prompt`. A session minted by an agentPrompt
		// submit or a bare caller starts without one (_reconcileSession), so the first llm.agent turn is
		// always the one that sets it. A fork with no prompt legitimately composes fresh.
		$session = \DAO_LlmAgentSession::get($session_id);

		// Compose when there's nothing to reuse...
		$compose = !$session || '' === strval($session->system_prompt);

		// ...or when the author changed the KATA that produces it. Only intent may spend a prompt cache: a
		// prefix can run to tens of thousands of tokens, and re-composing it because a host shipped a command
		// or an agent wrote a file into an `rw` mount would throw that away for something nobody decided.
		//
		// Evaluated at the start of a USER turn only. A tool-loop re-entry re-reads the same KATA, so
		// comparing there would be work with no possible answer but "unchanged".
		if($this->_is_new_turn) {
			$gate = $this->_prefixInputsGate();
			$stored_gate = $this->_node_memory['prefix_gate'] ?? null;

			// A first sighting is not a change. Sessions that predate the gate (and any whose first turn
			// authored nothing) have no stored value, and recomposing on that would rewrite a live
			// conversation's prompt once, out of nowhere, the first time it took a turn after an upgrade.
			if(!is_null($gate)) {
				if(!is_null($stored_gate) && $gate !== $stored_gate)
					$compose = true;

				$this->_node_memory['prefix_gate'] = $gate;
			}
		}

		if($compose) {
			// Ours first, then the agent's, then the author's. Each layer is more specific than the last: the
			// trigger describes the SURFACE, the agent record is who is running there, and the script speaks for
			// this one conversation. Someone adding a line about their own tool appends a line rather than
			// reimplementing the prompt.
			$composed = trim(implode("\n\n", array_filter([
				$this->_triggerSystemPrompt($session_id),
				trim(strval($this->_agent_config['system_prompt'] ?? '')),
				trim(strval($this->_inputs['system_prompt'] ?? '')),
			])));

			if('' !== $composed)
				\DAO_LlmAgentSession::setSystemPrompt($session_id, $composed);
		}

		// Tools: persist the EVALUATED `tools:` config — the same map `_getTools()` runs, so it holds the real
		// resolved keys + static `cerb:automation:` URIs regardless of how the block was authored. This matters
		// because the common pattern builds the tools map dynamically (`tools@key: <var>` fed by a `@kata` loop
		// over a tool chooser) — pre-evaluation there is just a variable name, useless. The URIs stay stable
		// even when built from `{{tool.name}}`; only `{{placeholder}}` INPUTS (a tool's description/params) bake
		// in this-turn's value (accepted — dynamic inputs aren't reproducible anyway). Session is the source of
		// truth for the name→URI map the dev transcript uses to trace a tool call back to its `llm.tool`.
		// On-change write.
		$tools_config = $this->_inputs['tools'] ?? [];

		// A pure resume (no authored `tools:`) inherits the session's stored map rather than clobbering it with
		// the trigger's contribution alone — the same fallback _getTools() makes.
		if(!$tools_config && ($session = \DAO_LlmAgentSession::get($session_id)))
			$tools_config = $session->tools ?? [];

		// The trigger's tools ride the STORED map, not just the dispatch one, because the provider schema is
		// built from the session alone (_DevblocksLlmService::getSessionToolSchemas) and an async turn runs in a
		// queue worker with no dict and no continuation to resolve a host from. Re-derived every turn, so a host
		// that gains a command reaches an existing conversation on its next turn -- note this is the tool MAP
		// only. It deliberately does NOT feed the prefix gate (_prefixInputsGate), so a host gaining a command
		// never rewrites an open conversation's system prompt.
		$tools_config = $this->_withResolvedAgentTools(
			$this->_withTriggerTools($this->_withAgentTools(is_array($tools_config) ? $tools_config : [])),
			$session_id
		);

		if($tools_config)
			\DAO_LlmAgentSession::setTools($session_id, $tools_config);

		// Same on-change persistence for `mounts:` — but the RESOLVED specs (the shape fromSpecs() takes),
		// since that's what a resume replays. Only when this turn actually authored them; otherwise
		// _getMountSpecs() would just write back what it read.
		// An EMPTY authored block persists `[]`, which is how a /tmp-only filesystem survives a resume — the
		// column being NULL is what means "never enabled".
		// `terminal:` rides the same stored list as a reserved entry, so authoring EITHER block is a reason to write.
		// The agent's mounts count as authored for this purpose: if only IT contributes them, the session must
		// still store the resolved specs, or the next resume reads null and the volumes silently disappear.
		if(
			array_key_exists('mounts', $this->_inputs)
			|| array_key_exists('terminal', $this->_inputs)
			|| ($this->_agent_config['mounts'] ?? [])
			|| ($this->_agent_config['terminal'] ?? [])
		)
			\DAO_LlmAgentSession::setMounts($session_id, $this->_getMountSpecs());
	}

	/**
	 * The back half of a provider turn, shared by the sync path (_activateLLM, response in hand) and the async
	 * path (_consumeLLMAsync, response reconstituted from the landed session head): stack any tool calls,
	 * denormalize the running context-window estimate, and set the node's abstract-message output.
	 */
	private function _applyTurnResponse(DevblocksLlmChatResponse $llm_response, ?string $session_id, $memory_store, \Extension_DevblocksLlmProvider $llm_provider) : void {
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
		$this->_dict->set($this->_output, array_merge([
			'session_id' => $session_id,
			'messages' => $llm_response->getMessages(),
			'finish_reason' => $llm_response->getFinishReason(),
		], $this->_resolvedModelInfo($session_id)));

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
			$commands = [];

		// The agent's commands union the script's. A command is a capability rather than a configuration, so
		// there's nothing to override -- either side naming one enables it.
		if(($agent_commands = $this->_agent_config['commands'] ?? []) && is_array($agent_commands))
			$commands = array_replace($agent_commands, $commands);

		if(!$commands)
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

		// Deduped on the NAME, because the key isn't the identity: `command/compact:` and a bare `compact:` are
		// the same command, so an agent writing one and a script writing the other would otherwise enable it
		// twice.
		return array_values(array_unique($names));
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

			// See _startLLMAsync(): this command reports only its own failure, never a leftover one.
			$this->_clearTurnError($session_id);

			$this->_node_memory['stack'][] = ['command_done', [
				'command' => $command,
				'head' => $head_before,
				// The uuids let the resume re-ask the gate whether this failed — see _getQueueFailure().
				'queue_uuids' => $uuids,
			]];

			$this->_dict->set('__exit', 'await');
			$this->_dict->set('__return', [
				'queue' => [
					'messages' => $uuids,
					// See _startLLMAsync(): lets the gate poll back off as the wait runs long.
					'started_at' => time(),
					// See _startLLMAsync(): a failed `/compact` resumes into `command_done`, which reports it
					// through `on_error:` rather than ending the chat over a housekeeping command.
					'on_error' => 'resume',
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

	private function _resolvedModelInfo(?string $session_id) : array {
		$out = [
			'provider' => '',
			'model' => '',
		];

		if($session_id && ($session = \DAO_LlmAgentSession::get($session_id))) {
			$out['provider'] = strval($session->provider);
			$out['model'] = $session->getModel();
		}

		return $out;
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

	/**
	 * Run a SERVER-side trigger tool (`ui_server/`) and return what the model should see.
	 *
	 * The trigger owns the vocabulary — the node only knows that a tool carries a `handler` and that the
	 * trigger can run one. Mirrors how `agent_terminal` delegates to `Cerb\Agent\Filesystem`, except the
	 * handler set is per-host rather than global.
	 */
	private function _runTriggerTool(DevblocksLlmChatResponse_Tool $tool_spec, ?array $tool) : string {
		$handler = is_array($tool) ? strval($tool['handler'] ?? '') : '';

		$trigger = $this->_automation?->getTriggerExtension();

		if('' === $handler || !$trigger || !method_exists($trigger, 'runLlmAgentTool'))
			return sprintf('ERROR: The `%s` tool is not available here.', $tool_spec->getName());

		$content = $trigger->runLlmAgentTool($handler, $tool_spec->getParameters() ?? []);

		// null means the trigger didn't recognize the handler — a catalog/trigger mismatch. Say so rather than
		// answering with nothing, which the model reads as a real (empty) result.
		if(is_null($content))
			return sprintf('ERROR: The `%s` tool is not implemented.', $tool_spec->getName());

		// Providers reject an empty tool result; same substitute `agent_terminal` uses.
		return strval($content) ?: '(no output)';
	}

	/**
	 * Suspend on a `uiCommand` await so the browser can run a host editor's command and hand back its result.
	 *
	 * The mechanism is the same one `_startLLMAsync()` uses for the queue gate — push the resume state, set
	 * `__exit`/`__return`, let the engine unwind — and the form it emits is indistinguishable from an authored
	 * `await:form:`, because nothing in the render or submit path consults the AST: the renderer reads element
	 * CONFIG straight back out of `__return.form.elements`.
	 *
	 * Two states are pushed, so the round-trip slots INTO the existing flow rather than duplicating its tail:
	 * `tool_return` (re-entered afterwards, where the result is handed to the provider and `__tool` is cleaned
	 * up) and, on top of it, `ui_command_return` (which lifts the browser's answer onto `__tool.content`).
	 *
	 * The transcript rides along because this await REPLACES the rendered panel. Without it the chat would go
	 * blank for the length of the round-trip on every editor call.
	 */
	private function _startUiCommand(DevblocksLlmChatResponse_Tool $tool_spec, ?array $tool) : void {
		// `command:` is the host's bridge name and the only thing that can't be inferred. It's always present on
		// a trigger-contributed tool; a hand-authored `ui_command/` entry is the one way to get here without it,
		// and there's nothing to round-trip TO, so answer the model instead of hanging the turn.
		if(!is_array($tool) || '' === ($command = strval($tool['command'] ?? ''))) {
			$tool_dict = $this->_dict->get('__tool', []);
			$tool_dict['content'] = 'ERROR: This tool is not wired to a host editor command.';
			$this->_dict->set('__tool', $tool_dict);

			$this->_node_memory['stack'][] = ['tool_return', []];
			return;
		}

		// Only the parameters this tool actually declares, and only those the model sent. An absent optional
		// must be OMITTED rather than passed as '': the host distinguishes "no limit given" from "limit is
		// empty", which is what the generated KATA's `@key,optional` was for.
		$sent = $tool_spec->getParameters() ?? [];
		$params = [];

		foreach(array_keys($tool['parameters'] ?? []) as $param_key) {
			list($param_type, $param_name) = array_pad(explode('/', strval($param_key), 2), 2, null);

			if(empty($param_name))
				$param_name = $param_type;

			if(array_key_exists($param_name, $sent))
				$params[$param_name] = $sent[$param_name];
		}

		// Constants the tool pins on the bridge and never advertises — a generic host command with one sensible
		// use here (a worklist's only writable field is its query). Applied LAST so a model that guesses at the
		// name can't override one.
		if(is_array($command_params = $tool['command_params'] ?? []))
			$params = array_merge($params, $command_params);

		// Named for the node so two agents in one interaction can't collide on the return var, and prefixed to
		// stay clear of an author's own prompt names. Must be a legal Twig identifier or the submit handler
		// drops the value silently (_applyAwaitFormPromptValues gates on isVariableName).
		$var = '__ui_command_' . preg_replace('/[^a-z0-9_]+/', '_', DevblocksPlatform::strLower($this->node->getId()));

		$elements = [];

		// Same shape the generated `&transcript_summary` uses: this is a status repaint between tool calls, not
		// the composer, so tool calls and thinking collapse to a line apiece.
		if(($session_id = $this->_dict->getKeyPath($this->_getSessionKey(), '', '::'))) {
			$elements['llmTranscript/' . $var . '_transcript'] = [
				'session_id' => $session_id,
				'thinking' => 'summary',
				'tools' => 'summary',
			];
		}

		$elements['uiCommand/' . $var] = [
			'command' => $command,
			'params' => $params,
		];

		$elements['submit'] = [
			'is_automatic' => true,
		];

		// LIFO: `ui_command_return` pops first (lifting the answer), then `tool_return` finishes the call.
		$this->_node_memory['stack'][] = ['tool_return', []];
		$this->_node_memory['stack'][] = ['ui_command_return', ['var' => $var]];

		$this->_dict->set('__exit', 'await');
		$this->_dict->set('__return', [
			'form' => [
				'elements' => $elements,
			],
		]);
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
	}

	/**
	 * Whether this turn runs OFF-request (enqueue + `await:queue:`) rather than inline. Only a continuation-backed
	 * interaction can suspend on an await and be resumed by the client's poll/worker, and only there is the >30s
	 * FPM terminate a real risk. The simulator (no real workers to drain the queue) and headless/one-off runs
	 * (`llm.tool` sub-automations, functions, tests — no continuation to resume) stay synchronous.
	 */
	private function _shouldRunAsync(Model_Automation $automation) : bool {
		if($this->_dict->get('__simulate', false))
			return false;

		return in_array($automation->extension_id, [
			\AutomationTrigger_InteractionWorker::ID,
			\AutomationTrigger_InteractionWorkerAgent::ID,
			\AutomationTrigger_InteractionInternal::ID,
		], true);
	}

	/**
	 * Start half of an async turn: persist the config onto the session, enqueue the provider turn as a
	 * `cerb.llm.agent.requests` message `{session_id, messages}`, push an `llm_consume` resume state, and suspend
	 * on `await:queue:` for the enqueued uuid. A worker (the client's poll sidecar, or the cron) runs
	 * nextSessionTurn() off-request and appends the assistant turn to the session; the engine's queue gate then
	 * clears and resumes into `llm_consume`.
	 */
	private function _startLLMAsync(string $state, ?string &$error=null) : bool {
		$session_key = $this->_getSessionKey();
		$session_id = $this->_dict->getKeyPath($session_key, null, '::');

		$this->_persistSessionConfig($session_id);

		// The inbound user messages ride in the queue payload rather than being pre-written to the transcript, so
		// an abandoned turn leaves no orphaned message; the worker appends them to the managed history when it runs.
		$new_messages = ('llm' == $state) ? ($this->_inputs['messages'] ?? []) : [];

		$queue = DevblocksPlatform::services()->queue();

		if(!($uuids = $queue->enqueue('cerb.llm.agent.requests', [
			['session_id' => $session_id, 'messages' => $new_messages],
		], $error)))
			throw new Exception_DevblocksAutomationError($error ?: 'Failed to enqueue the LLM turn.');

		// Resume into the consume half once the turn lands. The uuids ride the resume state for tracing; the gate
		// on __return.queue.messages is what actually clears the await.
		//
		// `head` and `prompt` are what a FAILED turn needs and only this side knows. The head lets the resume tell
		// "this turn landed" from "the head is the PREVIOUS turn's answer" — for a fresh `llm` turn they look
		// identical, since the user message rides the payload and is appended by the worker. The prompt is handed
		// back to the composer so a rate-limited turn costs the worker a click, not their paragraph.
		$head_before = ($session = \DAO_LlmAgentSession::get($session_id)) ? strval($session->head_uuid) : '';

		// Start clean, so this turn can only ever report ITS own failure. The worker writes that slot after we
		// enqueue; a leftover from an earlier failure would otherwise be the one thing read if this turn's worker
		// is hard-killed before it can write its own.
		$this->_clearTurnError($session_id);

		$this->_node_memory['stack'][] = ['llm_consume', [
			'queue_uuids' => $uuids,
			'head' => $head_before,
			'prompt' => $this->_pendingPromptText($new_messages),
		]];

		// Suspend on the queue gate: the engine re-emits this await until every uuid is terminal-DONE, then
		// resumes into `llm_consume`. The client keeps the transcript + its dots spinner up and polls in the
		// background (no "waiting" screen); an author `on_wait:` branch could customize the UX later.
		$this->_dict->set('__exit', 'await');
		$this->_dict->set('__return', [
			'queue' => [
				'messages' => $uuids,
				// When the wait began. The gate poll re-emits its marker every cycle and the client re-reads
				// it every cycle, so the server paces the poll from this — backing off as a turn runs long,
				// because the pending state is a pure read and a faster poll discovers nothing.
				'started_at' => time(),
				// WE handle a failed message, rather than the engine ending the automation at the gate. Without
				// this the resume never happens and `on_error:` — which exists precisely so a chat doesn't
				// dead-end — can never fire on an async turn. See _resumeAwaitGate().
				'on_error' => 'resume',
			],
		]);

		return true;
	}

	/**
	 * Consume half of an async turn: the worker appended the assistant turn to the session and the engine's queue
	 * gate confirmed it's DONE, so reconstitute the response from the session's now-current head message — the
	 * provider's convertToGenericMessage() is the exact inverse of the chatCompletion parse (tool_use → tool
	 * calls, text blocks → messages), and usage_json carries the token vector — then run the shared back half.
	 */
	/**
	 * Did the awaited turn actually leave an answer on the session? False means it produced nothing usable —
	 * it failed, or it was stopped and its partial was too incomplete to keep.
	 *
	 * Measured against the head we ENQUEUED on, not merely "the head is an assistant row". On a fresh `llm`
	 * turn the head is ALREADY an assistant row when we enqueue (the previous turn's answer; this turn's user
	 * message rides the queue payload and is appended by the worker). So a turn that died before the worker
	 * appended anything leaves an assistant head that isn't ours, and treating it as landed would re-apply the
	 * PREVIOUS answer as this turn's — a silent wrong answer rather than a visible failure.
	 *
	 * A null/absent `$head_before` (a resume state written before this was recorded) degrades to the old test.
	 */
	private function _turnLandedSince(?string $head_before) : bool {
		$session_id = strval($this->_dict->getKeyPath($this->_getSessionKey(), '', '::'));

		if('' === $session_id)
			return false;

		if(!($session = \DAO_LlmAgentSession::get($session_id)) || !$session->head_uuid)
			return false;

		$head_uuid = strval($session->head_uuid);

		// Nothing was appended since we enqueued, so whatever the head is, it isn't this turn's answer.
		if(!is_null($head_before) && $head_uuid === strval($head_before))
			return false;

		$head = \DAO_LlmAgentMessage::get($head_uuid);

		// A STREAMING head is a turn still being written (a worker died mid-stream and the gate went terminal
		// before anything closed it), not one that landed. resolveDanglingStream() settles it on the next turn.
		return $head && 'assistant' === $head->role && !$head->is_streaming;
	}

	/**
	 * The shared-cache slot where the async worker leaves WHY a turn failed. Out-of-band for the same reason the
	 * interrupt flag is: the failure happens in a different request, and there is nowhere on the queue message to
	 * put it — `queue_message` has no message column, and `reportStatus()` metadata is dropped for job-less
	 * messages ("fire-and-forget messages (job_id=0) skip logging"), which every LLM turn is.
	 */
	static function turnErrorCacheKey(string $session_id) : string {
		return 'llm_agent_turn_error_' . $session_id;
	}

	private function _clearTurnError(?string $session_id) : void {
		if(strlen(strval($session_id)))
			DevblocksPlatform::services()->cache()->remove(self::turnErrorCacheKey(strval($session_id)));
	}

	/**
	 * Did the awaited queue message(s) fail? Returns `{message, status}` (the worker's stash, consumed) or null.
	 *
	 * The gate already decided this — it only hands control back on a terminal failure when the descriptor says
	 * we own it — but the node re-asks so a resume driven by anything else can't mistake a failure for a clear.
	 */
	private function _getQueueFailure(array $state_params) : ?array {
		$uuids = (array) ($state_params['queue_uuids'] ?? []);

		if(!$uuids || 'error' !== DevblocksPlatform::services()->queue()->awaitGate($uuids))
			return null;

		$session_id = strval($this->_dict->getKeyPath($this->_getSessionKey(), '', '::'));
		$cache = DevblocksPlatform::services()->cache();
		$key = self::turnErrorCacheKey($session_id);

		// Consumed on read: one failure, one report. A stale stash surfacing on a later, unrelated failure would
		// classify it against the wrong status, which is worse than the generic sentence below.
		$stash = ('' !== $session_id) ? $cache->load($key, true) : null;

		if($session_id && $stash)
			$cache->remove($key);

		return [
			// Set ONLY for a setup/validation failure, where the text is Cerb's own and safe to print. A provider
			// failure stashes no message at all -- see _DevblocksLlmService::_stashTurnError().
			'message' => is_array($stash) ? strval($stash['message'] ?? '') : '',
			// 0 = no HTTP response at all (network/timeout), which is also the fallback when the stash is cold.
			'status' => is_array($stash) ? intval($stash['status'] ?? 0) : 0,
			// The provider's own `Retry-After`, in seconds. NULL means it didn't say — kept distinct from 0 so
			// the composer can stay vague instead of inventing a number.
			'retry_after' => (is_array($stash) && !is_null($stash['retry_after'] ?? null)) ? intval($stash['retry_after']) : null,
		];
	}

	/**
	 * Hand a failed turn back to the chat: undo what it appended, keep the worker's message for the composer,
	 * then throw so the author's `on_error:` runs (or, with no branch, so it surfaces exactly as an inline turn's
	 * failure does — the async path matching the sync one, not being quietly more forgiving than it).
	 *
	 * @throws Exception_DevblocksAutomationError
	 */
	private function _failTurn(array $failure, array $state_params) : void {
		$session_id = strval($this->_dict->getKeyPath($this->_getSessionKey(), '', '::'));
		$retry_after = is_null($failure['retry_after'] ?? null) ? null : intval($failure['retry_after']);
		$message = \_DevblocksLlmService::formatTurnFailure(intval($failure['status'] ?? 0), $retry_after, strval($failure['message'] ?? ''));

		// Put the branch back where it was before Send. The worker appends the user message (and, if it ran, this
		// turn's compaction fold) BEFORE calling the provider, so a failed turn otherwise leaves the session
		// carrying a message the model never answered — and resending the same text from the composer would
		// duplicate it. Rewinding the head is enough: getActivePath() anchors there, so the abandoned rows become
		// a dead branch that nothing reads (the same cursor-handback discardStreamingMessage() does).
		//
		// Only when the head actually MOVED, and only to a head we recorded — never blindly to ''.
		if('' !== $session_id && array_key_exists('head', $state_params)) {
			$head_before = strval($state_params['head']);

			if(($session = \DAO_LlmAgentSession::get($session_id)) && strval($session->head_uuid) !== $head_before)
				\DAO_LlmAgentSession::setHead($session_id, $head_before ?: null);
		}

		// The composer reads this on the next render (AgentPromptAwait), so the worker gets their message back
		// instead of retyping it. A dict key rather than the cache: it belongs to THIS interaction, and it has to
		// survive an eviction that would otherwise silently eat someone's paragraph.
		$this->_dict->set('__llm_retry', [
			'prompt' => strval($state_params['prompt'] ?? ''),
			'error' => $message,
			'at' => time(),
		]);

		// activate()'s catch replaces the output var wholesale with `['error' => …]`, so anything an author needs
		// to branch on has to be handed to it here.
		$this->_output_error_extra = [
			'error_status' => intval($failure['status'] ?? 0),
			// DELIBERATELY broader than _DevblocksLlmService::TURN_RETRY_STATUSES, which answers a different
			// question: whether we may silently re-run a turn nobody asked us to re-run, and so admits only the
			// classes that cost nothing. This one answers "would sending this again plausibly work?" for a human
			// who has seen the error and is choosing to pay for another attempt. A timeout qualifies here and not
			// there, and that gap is the point.
			'retryable' => in_array(intval($failure['status'] ?? 0), [0, 408, 425, 429, 500, 502, 503, 504, 529], true),
			'retry_prompt' => strval($state_params['prompt'] ?? ''),
			// Seconds the provider asked us to wait, or 0 when it didn't say. Any automatic attempts this class
			// was entitled to are already spent by the time an author sees this.
			'retry_after' => intval($retry_after),
		];

		throw new Exception_DevblocksAutomationError($message);
	}

	// The text of the message this turn was sending, for the composer to hold onto if the turn fails. Only the
	// prose: images are already linked to the session, and a restored model/effort pick would fight the picker's
	// own persistence. Empty for a tool-loop continuation, which has no pending message.
	private function _pendingPromptText(array $messages) : string {
		foreach(array_reverse($messages) as $message) {
			if(!is_array($message) || 'user' !== ($message['role'] ?? ''))
				continue;

			// A multimodal turn's content is a block list; only a plain-prose message is restorable.
			if(is_string($message['content'] ?? null))
				return $message['content'];
		}

		return '';
	}

	private function _consumeLLMAsync(array $state_params, ?string &$error=null) : bool {
		$llm = DevblocksPlatform::services()->llm();

		$session_key = $this->_getSessionKey();
		$session_id = $this->_dict->getKeyPath($session_key, null, '::');
		$memory_store = $llm->getMemoryStore($session_id);

		$llm_provider = $this->_getLlmProvider();

		if(!($session = \DAO_LlmAgentSession::get($session_id)) || !$session->provider)
			throw new Exception_DevblocksAutomationError('The LLM session vanished before its turn could be consumed.');

		$head = strval($session->head_uuid) ? \DAO_LlmAgentMessage::get($session->head_uuid) : null;

		if(!$head || 'assistant' !== $head->role)
			throw new Exception_DevblocksAutomationError('The LLM turn produced no assistant response.');

		$llm_response = $llm_provider->convertToGenericMessage($head->data, $head->uuid);
		$llm_response->setUsage(is_array($head->usage) ? $head->usage : []);
		// Neither usage nor the finish reason is recoverable from data_json — both live in their own columns, so
		// the async path has to re-read them here or the turn looks unreported.
		$llm_response->setFinishReason($head->finish_reason);

		$this->_applyTurnResponse($llm_response, $session_id, $memory_store, $llm_provider);

		return true;
	}

	/**
	 * @param DevblocksLlmChatResponse_Tool $tool_spec
	 * @param string|null $error
	 * @return bool
	 */
	private function _activateTool(DevblocksLlmChatResponse_Tool $tool_spec, ?string &$error=null) : bool {
		$llm = DevblocksPlatform::services()->llm();
		
		$llm_provider = $this->_getLlmProvider();
		
		$session_key = $this->_getSessionKey();
		$session_id = $this->_dict->getKeyPath($session_key, null, '::');
		$memory_store = $llm->getMemoryStore($session_id);

		$tools = $this->_getTools($session_id);
		$tool =	$tools[$tool_spec->getName()] ?? null;
		
		$tool_response = [
			'content' => 'ERROR: Unknown tool type.'
		];

		if($tool) {
			$tool_type = $tool['type'] ?? null;

			$this->_dict->set('__tool', [
				'id' => $tool_spec->getId(),
				'name' => $tool_spec->getName(),
				'parameters' => $tool_spec->getParameters(),
				'type' => $tool_type,
			]);

			if(in_array($tool_type, ['agent_tool', 'automation', 'tool'])) {
				// Run the custom `on_tool:` branch
				if (null != ($this->node->getChild($this->node->getId() . ':on_tool'))) {
					$this->_node_memory['stack'][] = ['tool_branch', []];
					return true;
				}

			} elseif(in_array($tool_type, ['ui_command', 'ui_server'])) {
				// A tool the TRIGGER contributed — a host editor's command, or one the trigger answers itself.
				// NOTHING has run yet: unlike `agent_terminal`, whose command executes server-side right here,
				// these wait until after `on_tool:`, at `tool_return`. That ordering is the point. `on_tool:` is
				// where an approval gate, a confirmation, or a policy check belongs, and running the tool first
				// would leave nothing left to approve.
				//
				// Queued unconditionally, branch or no branch: `tool_branch` handles a missing `on_tool:` by
				// falling straight through to `tool_return`, which is where both kinds are resolved.
				$this->_node_memory['stack'][] = ['tool_branch', []];
				return true;

			} elseif('agent_terminal' == $tool_type) {
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
				'content' => self::_unknownToolError($tool_spec->getName(), $tools)
			];
		}
		
		$llm_provider->returnTool($tool_spec, $tool_response['content'] ?? '', $memory_store);
		return true;
	}

	/**
	 * The reply to a tool call naming something that isn't a tool.
	 *
	 * A bare "this tool does not exist" costs the model a whole turn to recover from, and the most common way
	 * to get here isn't a typo: the agent terminal documents a vocabulary of COMMANDS (`search`, `read`, `ls`,
	 * `cerb`) inside its tool DESCRIPTION, which reads like a menu of callable things -- so a model calls
	 * `search` as a tool, with the flags from the help text as its arguments. Naming the mistake and handing
	 * back the shape that works turns a dead turn into a recovered one.
	 *
	 * The hint is deliberately generic rather than a list of known verbs: it stays correct for `cerb records`
	 * and for any command added later, with nothing here to drift out of sync with Filesystem's dispatch.
	 */
	private static function _unknownToolError(string $name, array $tools) : string {
		$lines = [sprintf('ERROR: There is no tool named `%s`.', $name)];

		$terminal_name = '';

		foreach($tools as $tool_name => $tool) {
			if('agent_terminal' == ($tool['type'] ?? ''))
				$terminal_name = strval($tool_name);
		}

		if('' !== $terminal_name) {
			$lines[] = sprintf(
				'If you meant to run a terminal command, that is the `%s` tool: pass the WHOLE command line as its `command` argument, with flags written as they would be typed -- command: "%s ..." -- not as separate tool arguments.',
				$terminal_name,
				DevblocksPlatform::strLower($name)
			);
		}

		if($tools)
			$lines[] = 'Available tools: ' . implode(', ', array_keys($tools)) . '.';

		return implode("\n", $lines);
	}
}