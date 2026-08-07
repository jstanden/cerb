<?php
/*
 * The toolbar hosted by an editor's agent pane (CerbUI.AgentPane) — Icon Builder, Data Query Tester, the mail
 * reply/compose editors, and later sheet/map/chart/workflow/draft composers. Its items launch interactions
 * INLINE into the agent pane; a `{{component}}` state variable (which editor the pane is mounted on) lets one
 * shared toolbar offer different interactions per host — gate an item with `hidden@bool: {{ component !=
 * 'icon' }}`. The host supplies `command`/`ui_capabilities` through the toolbar's cerbBotTrigger options, so a
 * launched interaction can read/write the live editor via `uiCommand` awaits.
 */
class Toolbar_AgentPane extends Extension_Toolbar {
	const ID = 'cerb.toolbar.agent.pane';

	// The `caller.name` the pane posts when it launches an interaction (agent-pane.js) — NOT the toolbar id.
	// It's also the first segment of the continuation's `resume_scope`, so History can find its own chats.
	const CALLER_NAME = 'agent.pane';

	function getPlaceholdersMeta() : array {
		return [
			[
				'key' => 'component',
				'notes' => "The editor/component the agent pane is mounted on (e.g. `icon`, `data_query`, `mail_reply`, `mail_compose`). Gate items per host with `hidden@bool: {{ component != 'icon' }}`.",
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion.',
			],
		];
	}

	function getInteractionInputsMeta() : array {
		return [
			[
				'key' => 'component',
				'notes' => 'The editor/component the agent pane is mounted on.',
			],
			[
				'key' => 'worker_*',
				'notes' => 'The active [worker](https://cerb.ai/docs/records/types/) record. Supports key expansion.',
			],
		];
	}

	function getInteractionOutputMeta(): array {
		return [];
	}

	function getInteractionAfterMeta() : array {
		return [
			[
				'key' => 'refresh_toolbar@bool:',
				'notes' => 'Refresh the current [toolbar](https://cerb.ai/docs/toolbars/)',
			],
		];
	}
}
