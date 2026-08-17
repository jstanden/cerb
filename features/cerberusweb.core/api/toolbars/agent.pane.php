<?php
/*
 * The toolbar hosted by an editor's agent pane (CerbUI.AgentPane) -- the automation editor, the Icon Builder,
 * the Data Query and Scripting testers, the mail reply editor, and every worklist quick-search bar
 * (`component: worklist`, fetched by C4_AbstractView::getAgentToolbar()); later sheet/map/chart/workflow
 * composers. Its items launch interactions INLINE into the agent pane; a `{{component}}` state variable (which
 * editor the pane is mounted on) lets one shared toolbar offer different interactions per host -- gate an item
 * with `hidden@bool: {{ component != 'icon' }}`. The host supplies `command`/`ui_capabilities` through the
 * toolbar's cerbBotTrigger options, so a launched interaction can read/write the live editor via `uiCommand`
 * awaits.
 *
 * What each host will actually answer is catalogued server-side in `Cerb\Agent\Pane\Components` -- that's what
 * the Automation Builder's "AI Agent Chat" template generates a bridge from.
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
				'notes' => "The editor/component the agent pane is mounted on: `automation`, `bot_scripting`, `data_query`, `icon`, `mail_reply`, or `worklist`. Gate items per host with `hidden@bool: {{ component != 'icon' }}`.",
			],
			[
				'key' => 'worklist_record_type',
				'notes' => 'The [record type](https://cerb.ai/docs/records/types/) alias of the worklist (e.g. `ticket`) -- what `data.query` `of:` expects. Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_record_context',
				'notes' => 'The context id of the worklist record type (e.g. `cerberusweb.contexts.ticket`). Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_id',
				'notes' => 'The id of the worklist (e.g. `cust_1234`). Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_query',
				'notes' => 'The [query](https://cerb.ai/docs/search/) of the worklist (e.g. `status:o group:Support`). Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_query_required',
				'notes' => 'The required [query](https://cerb.ai/docs/search/) of the worklist (e.g. `status:o group:Support`). Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_page',
				'notes' => 'The current page of the worklist (e.g. `2`). Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_limit',
				'notes' => 'The number of records per worklist page (e.g. `25`). Set when `component` is `worklist`.',
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
				'key' => 'worklist_id',
				'notes' => 'The id of the displayed worklist. Set when `component` is `worklist`.',
			],
			[
				'key' => 'worklist_record_type',
				'notes' => 'The record type of the displayed worklist. Set when `component` is `worklist`.',
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
