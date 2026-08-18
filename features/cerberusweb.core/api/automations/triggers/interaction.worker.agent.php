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
		// Same scope as any worker interaction; only the note changes. `caller_params` is where the pane puts
		// the two keys that matter here, and `component` in particular is now load-bearing rather than
		// informational -- it's what `llm.agent:` resolves this host's UI-command tools from.
		return array_map(
			fn($input) => ('caller_params' === ($input['key'] ?? '')) ? array_merge($input, [
				'notes' => "Built-in parameters based on the caller type. An agent pane supplies `component`, the "
					. "surface it's mounted on (`automation`, `bot_scripting`, `data_query`, `icon`, `mail_reply`, "
					. "`worklist`, or `commandbar`), and `ui_capabilities`, the commands that surface answers. An "
					. "`llm.agent:` in this automation is given those commands as tools automatically, so a chat "
					. "can drive the surface it opens beside without declaring any `tools:` of its own.\n\n"
					. "Deliberately NOT here: which page the worker is on. It would be a snapshot from launch, and "
					. "would go stale the moment they navigate -- the command bar's `get_page` tool reads it live "
					. "instead. Don't interpolate a changing value into `system_prompt:` either; that's the most "
					. "stable part of the cached prompt prefix and it's rewritten from the input every turn.",
			]) : $input,
			parent::getInputsMeta()
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
