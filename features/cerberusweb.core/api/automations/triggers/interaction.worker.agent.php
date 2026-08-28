<?php
/*
 * A worker interaction paired with a host editor: the agent panes (CerbUI.AgentPane) astride the Icon Builder,
 * the Data Query Tester, the mail Reply editor, the automation editor, and the scripting tester. It's an
 * ordinary `interaction.worker` in every respect except one -- it's the only trigger that advertises the
 * `uiCommand` await, the browser round-trip that reads and writes the live editor through the caller's
 * cerbBotTrigger command bridge.
 *
 * That capability used to ride on `interaction.internal`, which was expedient and wrong: internal exists to
 * hide Cerb's own plumbing (choosers, autocomplete helpers, the builders' inner dialogs) so a worker can
 * filter it out of a worklist. Agent panes are the opposite -- they're the worker's own conversations, they
 * park as resumable continuations, and they appear in History. Separating them means a caller without an
 * editor bridge is never handed a capability it can't fulfill, and the two populations can be told apart.
 */
class AutomationTrigger_InteractionWorkerAgent extends AutomationTrigger_InteractionWorker {
	const ID = 'cerb.trigger.interaction.worker.agent';

	function getInputsMeta() {
		// Same scope as any worker interaction, PLUS `agent_*`: the AI worker this chat was launched for. The
		// launcher knows which agent it is -- an agent pane lists one tile per agent -- so a script reads it
		// from scope (`agent@key: agent_id`) instead of naming an id or an `@mention`. That is what lets ONE
		// interaction serve every agent rather than being copied per agent.
		$inputs = array_merge(parent::getInputsMeta(), [
			[
				'key' => 'agent_*',
				'type' => 'record',
				'params' => [
					'record_type' => 'worker',
					// An agent IS a worker, but only an AI one -- so the simulator's chooser offers only those.
					'query' => 'isAi:y',
				],
				'notes' => "The AI [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) this "
					. "chat runs as, set by the launcher. Supports key expansion, so `agent_name` and "
					. "`agent__image_url` are the agent's own name and avatar. Pass `agent@key: agent_id` to "
					. "`llm.agent:` and `agentPrompt:` rather than hardcoding one -- that is what makes this "
					. "interaction reusable by every agent. Unset when no agent launched it.",
			],
		]);

		// `caller_params` is where the pane puts the two keys that matter here, and `component` in particular is
		// crucial rather than informational -- it's what `llm.agent:` resolves this host's UI-command tools
		// from, and which per-surface overrides an agent applies.
		return array_map(
			fn($input) => ('caller_params' === ($input['key'] ?? '')) ? array_merge($input, [
				'notes' => "Built-in parameters based on the caller type. An agent pane supplies `component`, the "
					. "surface it's mounted on (`automation`, `automation_scripting`, `data_query`, `icon`, `mail_reply`, "
					. "`worklist`, or `commandbar`), and `ui_capabilities`, the commands that surface answers. An "
					. "`llm.agent:` in this automation is given those commands as tools automatically, so a chat "
					. "can drive the surface it opens beside without declaring any `tools:` of its own. It's also "
					. "given a system prompt describing that surface, so `system_prompt:` is for what YOU want to "
					. "add -- it's appended to ours, not a replacement for it.\n\n"
					. "Deliberately NOT here: which page the worker is on. It would be a snapshot from launch, and "
					. "would go stale the moment they navigate -- the command bar's `cerb_get_page` tool reads it live "
					. "instead. Don't interpolate a changing value into `system_prompt:` either. The composed "
					. "prompt is built on the first turn and reused unchanged after that; it is rebuilt only when "
					. "you EDIT this automation, which takes effect on the worker's next message. A value that "
					. "changes on its own rebuilds it every turn, and the prompt prefix is the most cacheable part "
					. "of the request.",
			]) : $input,
			$inputs
		);
	}

	/**
	 * The host editor's UI commands as `llm.agent:` tools, resolved from the live caller.
	 *
	 * `llm.agent:` calls this through duck-typing (`method_exists`) off the automation's trigger extension, so
	 * a chat running beside an editor gets that editor's tools with nothing in its script -- the same way
	 * `mounts:` provisions `agent_terminal`. A caller that isn't an agent pane, or one on a host we have no
	 * catalog entry for, gets `[]`: a standalone chat is a legitimate answer, not an error.
	 *
	 * The component is stable for the life of a conversation. It's recorded in the continuation's `state_data`
	 * at start and a resume whose caller names a different one is rejected outright
	 * (`DAO_AutomationContinuation::resumeScopeFor()`), so the tool set can't shift under a session.
	 */
	function getLlmAgentTools(DevblocksDictionaryDelegate $dict) : array {
		$caller_params = $dict->get('caller_params', []);

		if(!is_array($caller_params))
			return [];

		return \Cerb\Agent\Pane\Components::getToolsFor(strval($caller_params['component'] ?? ''));
	}

	/**
	 * WHICH SURFACE this pane is, for an agent record's per-surface config.
	 *
	 * `llm.agent:` calls this through duck-typing off the automation's trigger, like `getLlmAgentTools()`. The
	 * answer is the same `component` those tools already resolve from -- one vocabulary, so an agent enabled on
	 * `mail_reply` gets the reply editor's tools AND the overrides someone wrote for `mail_reply`, and there is
	 * no second list of surface names to keep in step with the catalog.
	 *
	 * A caller that isn't an agent pane gets `''`: the agent still contributes its defaults, which is right for
	 * an `llm.agent:` running outside any pane.
	 */
	function getAgentSurfaceKey(DevblocksDictionaryDelegate $dict) : string {
		$caller_params = $dict->get('caller_params', []);

		if(!is_array($caller_params))
			return '';

		return strval($caller_params['component'] ?? '');
	}

	/**
	 * The agent's ROLE as the opening of its system prompt, resolved from the live caller.
	 *
	 * `llm.agent:` calls this through duck-typing (`method_exists`) off the automation's trigger extension,
	 * the same way it collects `getLlmAgentTools()` -- so a chat beside an editor is told what it is, and
	 * what that editor can do, with nothing in its script. The composed text is frozen onto the session on
	 * the first turn, so it's stable for the life of a conversation even though it's assembled here.
	 *
	 * This is deliberately NOT generated into each chat's `system_prompt:` any more. That copy was frozen at
	 * authoring time: a role improved in a release never reached an automation someone had already deployed,
	 * and fixing that would have meant upgrade patches editing people's scripts. Contributing at runtime is
	 * the same move we made for the built-in tools, for the same reason.
	 *
	 * `$mount_names` says which volumes the turn actually has, so the catalog only points at reference skills
	 * when they're really reachable -- a chat can be authored with no filesystem at all, and sending an agent
	 * after files it can't read is worse than saying nothing.
	 *
	 * A caller that isn't an agent pane, or one on a host we have no catalog entry for, gets `''`: a
	 * standalone chat writes its own prompt, which is a legitimate answer rather than an error.
	 */
	function getLlmAgentSystemPrompt(DevblocksDictionaryDelegate $dict, array $mount_names=[]) : string {
		$caller_params = $dict->get('caller_params', []);

		if(!is_array($caller_params))
			return '';

		// Tell the catalog which of the volumes it knows about are really here. A pointer at a volume the turn
		// didn't mount sends the agent after files it cannot read, which is worse than saying nothing.
		$volumes = [];

		// Case-insensitively, because that's how the mount itself resolves (`Filesystem::describeSpecs()`
		// looks volumes up on a lowercased name). A strict compare here would let a volume mount fine and
		// then silently fail to be pointed at, which is the hardest kind of mismatch to notice.
		$mounted = array_change_key_case(array_flip($mount_names));

		foreach([
			'skills' => \Cerb\Agent\FilesystemAssets::VOLUME_SKILLS,
			'docs' => \Cerb\Agent\FilesystemAssets::VOLUME_DOCS,
		] as $role => $name) {
			if(array_key_exists(DevblocksPlatform::strLower($name), $mounted))
				$volumes[$role] = $name;
		}

		return \Cerb\Agent\Pane\Components::getSystemPromptFor(
			strval($caller_params['component'] ?? ''),
			$volumes
		);
	}

	/**
	 * Answer a SERVER-side component tool (`ui_server/`) -- the ones that need no browser round-trip because
	 * the answer is already here.
	 *
	 * `llm.agent:` calls this the way it calls the terminal: run it, put the result on the tool, done. Kept on
	 * the trigger rather than in the catalog so `Cerb\Agent\Pane\Components` stays dependency-free -- it names
	 * a handler, this runs it.
	 *
	 * Returns null when the handler is unknown, which the caller reports to the model rather than silently
	 * answering with nothing.
	 */
	function runLlmAgentTool(string $handler, array $params) : ?string {
		return match($handler) {
			\Cerb\Agent\Pane\Components::HANDLER_ICONS_LIST => $this->_runIconsList($params),
			default => null,
		};
	}

	/**
	 * Every icon name in the set, one per line. Straight off the UI service rather than through the
	 * `ui.icons` data provider: that provider is a parser and a paginator wrapped around this same call, and
	 * a tool result wants neither.
	 */
	private function _runIconsList(array $params) : string {
		$filter = trim(strval($params['filter'] ?? ''));

		$icons = DevblocksPlatform::services()->ui()->getCerbIcons(null, 0, $filter ?: null);

		if(!$icons)
			return sprintf('No icon names match `%s`.', $filter);

		// Deliberately just the names. There are hundreds, and the geometry of any one of them is a
		// `cerb_get_icon_geometry` away -- returning them all would spend the context window on shapes nobody asked
		// to see.
		return implode("\n", $icons);
	}

	public static function getFormComponentMeta() : array {
		return array_merge(parent::getFormComponentMeta(), [
			'uiCommand' => ['class' => 'Cerb\Automation\Builder\Trigger\InteractionWorker\Awaits\UiCommandAwait', 'icon' => 'console'],
		]);
	}

	public static function getFormComponentSchema() : array {
		return array_merge(parent::getFormComponentSchema(), [
			'uiCommand' => [
				'title' => 'UI command',
				'has_var' => true,
				'fields' => [
					['key' => 'command', 'label' => 'Command', 'input' => 'text'],
					['key' => 'params', 'label' => 'Params (KATA)', 'input' => 'kata'],
					['key' => 'disabled', 'label' => 'Disabled', 'input' => 'bool'],
				],
				'new' => ['command' => 'getFields'],
			],
		]);
	}

	public function getAutocompleteSuggestions() : array {
		$suggestions = parent::getAutocompleteSuggestions();

		// Advertise the `uiCommand` await (mirrors the getFormComponentMeta merge). All path-regex keys are
		// nested under the top-level '*' scope key. Append to the existing element-type list rather than
		// replacing it, and add its sub-key completions.
		$elements_key = '(.*):await:form:elements:';

		if(isset($suggestions['*'][$elements_key]) && is_array($suggestions['*'][$elements_key])) {
			$suggestions['*'][$elements_key][] = [
				'caption' => 'uiCommand:',
				'snippet' => "uiCommand/\${1:prompt_command}:\n\t\${2:}",
				'description' => "Round-trip a command to the host editor (read/write its live UI)",
			];
		}

		$suggestions['*']['(.*):await:form:elements:uiCommand:'] = [
			[
				'caption' => 'command:',
				'snippet' => "command: \${1:getFields}",
				'description' => "The host command to run (must be in the caller's ui_capabilities)",
			],
			[
				'caption' => 'params:',
				'snippet' => "params:\n\t\${1:}",
				'description' => "Arguments passed to the host command",
			],
			[
				'caption' => 'disabled@bool:',
				'snippet' => "disabled@bool: {{ \${1:__tool.parameters.command != 'setField'} }}",
				'description' => "Render inert (skip the round-trip). Enable one uiCommand per tool in a shared await:form",
			],
		];

		return $suggestions;
	}
}
