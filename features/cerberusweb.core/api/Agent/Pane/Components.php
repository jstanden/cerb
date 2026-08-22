<?php
namespace Cerb\Agent\Pane;

/**
 * The catalog of agent-pane host components and the UI commands each one answers.
 *
 * A `CerbUI.AgentPane` host declares its own command bridge in JS -- a `capabilities:` string plus a
 * `runCommand(name, params)` switch. Nothing server-side knew about any of that: `ui_capabilities` rides
 * along as caller metadata and is never validated, and the `{{component}}` gate is just a string each page
 * section hardcodes. So an author wiring up a `uiCommand` await had to read the host's JavaScript to learn
 * what it would answer, and the Automation Builder had no way to scaffold one at all.
 *
 * This is that missing half: the same six bridges, described well enough to GENERATE from -- a model-facing
 * tool name, a description the model reads, a transcript icon and labels, and the parameter schema.
 *
 * `AutomationTrigger_InteractionWorkerAgent::getLlmAgentTools()` turns the live pane's entry into `llm.agent:`
 * tools at RUNTIME (see getToolsFor()), the same way `mounts:` provisions `agent_terminal` -- so a chat drives
 * whichever editor it sits beside with nothing in its script. It used to be generated into every chat as a
 * `tool/` definition and an `on_tool:` await element per command, which went stale here the moment a host
 * gained a command and which nobody could safely hand-edit.
 *
 * DELIBERATELY DEPENDENCY-FREE (no DAOs, no platform services, no extension base class) so it can be loaded
 * and diffed against the hosts headlessly.
 *
 * !! THIS DUPLICATES THE HOSTS. The authoritative `capabilities:` strings live in:
 *
 *   automation     features/cerberusweb.core/templates/internal/automation/peek_edit.tpl
 *   automation_scripting  features/cerberusweb.core/templates/configuration/section/developers/bot-scripting-tester/index.tpl
 *   data_query     features/cerberusweb.core/templates/configuration/section/developers/data-query-tester/index.tpl
 *   icon           features/cerberusweb.core/resources/js/cerb-ui/icon-builder.js
 *   worklist       features/cerberusweb.core/templates/search/quick_search.tpl
 *   mail_reply     features/cerberusweb.core/templates/display/rpc/reply.tpl
 *
 * (`commandbar` has no row: it declares no commands, because its host has no command bridge yet.)
 *
 * Adding a command to a host means adding it here too, BY HAND -- nothing checks. The two drift silently,
 * and the failure is quiet on both sides: a command listed here that the host doesn't implement returns '',
 * which reads as a model failure rather than a wiring bug, and a command the host gained but this file
 * didn't simply never reaches a model. That second direction now costs more than it used to: these entries
 * ARE the agent's tools at runtime, not just a template the author can fix up afterwards.
 *
 * (`mail_compose` appears in Toolbar_AgentPane's placeholder notes but no host implements it. It is not
 * here, and should not be until one does.)
 */
class Components {
	/**
	 * Handler ids for SERVER-side component tools (`server_tools`) -- the ones answered here rather than by a
	 * round-trip to the editor. The catalog names a handler; the trigger runs it. That split is what keeps this
	 * file dependency-free, and it means a component can offer a tool the host's JS knows nothing about.
	 */
	const HANDLER_ICONS_LIST = 'icons.list';

	/**
	 * Every component, keyed by its `component` string (the value the host passes to CerbUI.AgentPane and the
	 * one `{{component}}` gates on in the `agent.pane` toolbar).
	 *
	 * `description` is what the WIZARD's author reads when picking a location. `instructions` is what the
	 * MODEL reads: the opening of the generated system prompt, saying what this editor is and how to work in
	 * it. They differ because an agent embedded in the Icon Builder needs different orientation than one in
	 * the automation editor, and the generated prompt is the only place that difference can live -- the
	 * author edits it afterwards in the automation editor like any other text.
	 *
	 * Commands are keyed by the BRIDGE name (camelCase -- what `runCommand` dispatches on and what a
	 * `uiCommand` element's `command:` must say). Each carries:
	 *
	 *   tool            the snake_case name the MODEL calls
	 *   description     what the model reads to decide whether to call it
	 *   icon            transcript icon -- MUST exist in getCerbIcons(); an invented name renders nothing
	 *   labels          transcript strings: `active` while running, `summary` once done
	 *   parameters      ordered map of name => {description, enum?, required}; [] for a no-argument command
	 *   command_params  OPTIONAL constants passed to the bridge but never shown to the model, for when a
	 *                   generic bridge command has one sensible use here. They also win over anything the
	 *                   model sends under the same name, so a pinned argument stays pinned.
	 *
	 * @return array<string,array{label:string,icon:string,description:string,instructions:string,commands:array}>
	 */
	static function getAll() : array {
		return [
			'automation' => [
				'label' => 'Automation editor',
				'icon' => 'zap',
				'description' => "The automation editor popup -- reads and writes name, description, trigger, script, and policy.",
				'instructions' => "You are an assistant embedded in Cerb's automation editor. You help write and debug automations: KATA scripts bound to a trigger, plus the command policy that grants the script its privileges.\n\nThe script and the policy move together. A script that gains a privileged command stops working until the policy allows it, so when you add one, check the policy in the same turn.\n\nPrefer edit_field over set_field for the script and the policy. They are long, the author may have unsaved work elsewhere in them, and a whole-field overwrite silently discards it.",
				'commands' => [
					'getFields' => [
						'tool' => 'get_fields',
						'description' => "Read the current automation form state from the user's browser. Call this first when the user refers to \"this automation\" or asks what it does.",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the automation...', 'summary' => 'Read the automation'],
						'parameters' => [],
					],
					'setField' => [
						'tool' => 'set_field',
						'description' => "Replace a field's entire value. Send the COMPLETE new value; it overwrites what is there. Prefer edit_field for targeted changes to script or policy.",
						'icon' => 'edit',
						'labels' => ['active' => 'Updating the automation...', 'summary' => 'Updated the automation'],
						'parameters' => [
							'key' => [
								'description' => 'The field to replace.',
								'enum' => ['name', 'description', 'trigger', 'script', 'policy'],
								'required' => true,
							],
							'value' => [
								'description' => "The complete new value for the field. `name` is an identifier rather than a title: dot-delimited, unique across all automations, and limited to letters, numbers, dots, dashes, and underscores -- no spaces (e.g. `acme.ticket.autoReply`). `trigger` is a trigger extension id (e.g. `cerb.trigger.interaction.worker`), not a label; read the current one with get_fields rather than inventing one. Neither is checked here -- an invalid value is accepted and only rejected when the author saves.",
								'required' => true,
							],
						],
					],
					'editField' => [
						'tool' => 'edit_field',
						'description' => "Undo-safe search/replace on the script or policy. `old` must match exactly once; if it matches zero or more than one time you get an error telling you to expand or uniquify the context. Prefer this over set_field for targeted edits.",
						'icon' => 'edit',
						'labels' => ['active' => 'Applying an edit...', 'summary' => 'Edited the automation'],
						'parameters' => [
							'key' => [
								'description' => 'The field to edit.',
								'enum' => ['script', 'policy'],
								'required' => true,
							],
							'old' => [
								'description' => 'The exact existing text to replace, including indentation. Must match exactly once.',
								'required' => true,
							],
							'new' => [
								'description' => 'The replacement text.',
								'required' => true,
							],
						],
					],
					'grepField' => [
						'tool' => 'grep_field',
						'description' => "Search a field and get back matching lines as [{line, path, text}] -- exact line numbers plus the KATA key path for each hit, so you never have to guess a location.",
						'icon' => 'search',
						'labels' => ['active' => 'Searching the automation...', 'summary' => 'Searched the automation'],
						'parameters' => [
							'key' => [
								'description' => 'The field to search.',
								'enum' => ['script', 'policy'],
								'required' => true,
							],
							'query' => [
								'description' => 'The text to search for.',
								'required' => true,
							],
							'limit' => [
								'description' => 'Maximum number of matches to return (default 25).',
								'required' => false,
							],
						],
					],
					'getDiff' => [
						'tool' => 'get_diff',
						'description' => "Read a field's pending changes as hunks against its baseline: {key, tracked, hunks:[{status, line, endLine, added, removed}]} with 1-based line numbers. `tracked: false` means there is no baseline to diff against. Use it to review your own edits before summarizing them.",
						'icon' => 'history',
						'labels' => ['active' => 'Reading the diff...', 'summary' => 'Read the diff'],
						'parameters' => [
							'key' => [
								'description' => 'The field to diff.',
								'enum' => ['script', 'policy'],
								'required' => true,
							],
						],
					],
					'changeTab' => [
						'tool' => 'change_tab',
						'description' => "Switch the focused tab in the editor, to show the user the result of a change.",
						'icon' => 'tab',
						'labels' => ['active' => 'Switching tabs...', 'summary' => 'Switched tabs'],
						'parameters' => [
							'tab' => [
								'description' => 'The tab to focus.',
								'enum' => ['run', 'policy', 'log', 'visualization', 'usage'],
								'required' => true,
							],
						],
					],
					'highlightLine' => [
						'tool' => 'highlight_line',
						'description' => "Flash a line in the script editor to point the user at the code you are discussing. Cheaper and clearer than pasting the code back at them.",
						'icon' => 'sparkles',
						'labels' => ['active' => 'Highlighting a line...', 'summary' => 'Highlighted a line'],
						'parameters' => [
							'line' => [
								'description' => 'The 1-based line number to flash.',
								'required' => true,
							],
						],
					],
					'highlightKey' => [
						'tool' => 'highlight_key',
						'description' => "Flash the row for a KATA key path (e.g. start:while:do:llm.agent). A robust sibling of highlight_line: it targets the path, so it survives edits that shift line numbers.",
						'icon' => 'sparkles',
						'labels' => ['active' => 'Highlighting a key...', 'summary' => 'Highlighted a key'],
						'parameters' => [
							'key' => [
								'description' => 'The field the path refers to.',
								'enum' => ['script', 'policy'],
								'required' => true,
							],
							'path' => [
								'description' => 'The colon-delimited KATA key path to flash (e.g. start:while:do:llm.agent).',
								'required' => true,
							],
						],
					],
				],
			],

			// One editor, so there is no `key` parameter anywhere here -- the automation editor's `key`-scoped
			// shape would be wrong.
			'data_query' => [
				'label' => 'Data Query Tester',
				'icon' => 'database',
				'description' => "The Setup data-query tester -- reads and writes the query editor.",
				'instructions' => "You are an assistant embedded in Cerb's data query tester. You help write data queries: the `type:` of source, its required parameters, and the fields, formats, and subtotals it returns.\n\nRead the editor before you change it. A data query is dense and mostly parameters, so a targeted edit is nearly always right and a whole-field rewrite nearly always loses something the author meant to keep.",
				'commands' => [
					'getEditorValue' => [
						'tool' => 'get_query',
						'description' => "Read the current query from the user's editor. Call this first when the user refers to \"this query\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the query...', 'summary' => 'Read the query'],
						'parameters' => [],
					],
					'setEditorValue' => [
						'tool' => 'set_query',
						'description' => "Replace the entire query. Send the COMPLETE new value. Prefer edit_query for targeted changes.",
						'icon' => 'edit',
						'labels' => ['active' => 'Updating the query...', 'summary' => 'Updated the query'],
						'parameters' => [
							'value' => [
								'description' => 'The complete new query.',
								'required' => true,
							],
						],
					],
					'editField' => [
						'tool' => 'edit_query',
						'description' => "Undo-safe search/replace on the query. `old` must match exactly once; if it matches zero or more than one time you get an error telling you to expand or uniquify the context.",
						'icon' => 'edit',
						'labels' => ['active' => 'Applying an edit...', 'summary' => 'Edited the query'],
						'parameters' => [
							'old' => [
								'description' => 'The exact existing text to replace, including indentation. Must match exactly once.',
								'required' => true,
							],
							'new' => [
								'description' => 'The replacement text.',
								'required' => true,
							],
						],
					],
					'grepField' => [
						'tool' => 'grep_query',
						'description' => "Search the query and get back matching lines as [{line, path, text}] -- exact line numbers plus the KATA key path for each hit.",
						'icon' => 'search',
						'labels' => ['active' => 'Searching the query...', 'summary' => 'Searched the query'],
						'parameters' => [
							'query' => [
								'description' => 'The text to search for.',
								'required' => true,
							],
							'limit' => [
								'description' => 'Maximum number of matches to return (default 25).',
								'required' => false,
							],
						],
					],
					'highlightLine' => [
						'tool' => 'highlight_line',
						'description' => "Flash a line in the editor to point the user at the code you are discussing.",
						'icon' => 'sparkles',
						'labels' => ['active' => 'Highlighting a line...', 'summary' => 'Highlighted a line'],
						'parameters' => [
							'line' => [
								'description' => 'The 1-based line number to flash.',
								'required' => true,
							],
						],
					],
				],
			],

			'automation_scripting' => [
				'label' => 'Automation Scripting Tester',
				'icon' => 'console',
				'description' => "The Setup automation scripting tester -- reads and writes the script editor.",
				'instructions' => "You are an assistant embedded in Cerb's scripting tester. You help write and debug Twig expressions and templates against a test dictionary.\n\nThis editor is for trying an expression in isolation, so keep changes small and explain what a filter chain does rather than only handing back a longer one.",
				'commands' => [
					'getEditorValue' => [
						'tool' => 'get_script',
						'description' => "Read the current script from the user's editor. Call this first when the user refers to \"this script\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the script...', 'summary' => 'Read the script'],
						'parameters' => [],
					],
					'setEditorValue' => [
						'tool' => 'set_script',
						'description' => "Replace the entire script. Send the COMPLETE new value. Prefer edit_script for targeted changes.",
						'icon' => 'edit',
						'labels' => ['active' => 'Updating the script...', 'summary' => 'Updated the script'],
						'parameters' => [
							'value' => [
								'description' => 'The complete new script.',
								'required' => true,
							],
						],
					],
					'editField' => [
						'tool' => 'edit_script',
						'description' => "Undo-safe search/replace on the script. `old` must match exactly once; if it matches zero or more than one time you get an error telling you to expand or uniquify the context.",
						'icon' => 'edit',
						'labels' => ['active' => 'Applying an edit...', 'summary' => 'Edited the script'],
						'parameters' => [
							'old' => [
								'description' => 'The exact existing text to replace, including indentation. Must match exactly once.',
								'required' => true,
							],
							'new' => [
								'description' => 'The replacement text.',
								'required' => true,
							],
						],
					],
					'grepField' => [
						'tool' => 'grep_script',
						'description' => "Search the script and get back matching lines as [{line, path, text}] -- exact line numbers plus the KATA key path for each hit.",
						'icon' => 'search',
						'labels' => ['active' => 'Searching the script...', 'summary' => 'Searched the script'],
						'parameters' => [
							'query' => [
								'description' => 'The text to search for.',
								'required' => true,
							],
							'limit' => [
								'description' => 'Maximum number of matches to return (default 25).',
								'required' => false,
							],
						],
					],
					'highlightLine' => [
						'tool' => 'highlight_line',
						'description' => "Flash a line in the editor to point the user at the code you are discussing.",
						'icon' => 'sparkles',
						'labels' => ['active' => 'Highlighting a line...', 'summary' => 'Highlighted a line'],
						'parameters' => [
							'line' => [
								'description' => 'The 1-based line number to flash.',
								'required' => true,
							],
						],
					],
					'getDiff' => [
						'tool' => 'get_diff',
						'description' => "Read the script's pending changes as hunks against its baseline: {tracked, hunks:[{status, line, endLine, added, removed}]} with 1-based line numbers. `tracked: false` means there is no baseline. Use it to review your own edits before summarizing them.",
						'icon' => 'history',
						'labels' => ['active' => 'Reading the diff...', 'summary' => 'Read the diff'],
						'parameters' => [],
					],
				],
			],

			'icon' => [
				'label' => 'Icon Builder',
				'icon' => 'sparkles',
				'description' => "The Setup icon builder -- reads and writes the SVG geometry of the icon being drawn.",
				'instructions' => "You are an assistant embedded in Cerb's icon builder. You help draw icons for Cerb's icon set: SVG geometry on a 24x24 viewBox, rendered as a CSS mask and tinted by currentColor.\n\nBecause it is a mask, only the shape matters -- fill and stroke colors in the geometry are discarded. Read the current geometry before editing, and use get_icon_geometry to look at a shipped icon when you need the set's existing conventions for weight, corner radius, or optical sizing.",
				// Naming an existing icon is most of this job, and `get_icon_geometry` can only look one up once
				// you know it exists -- so the set has to be enumerable.
				'server_tools' => [
					'list_icons' => [
						'handler' => self::HANDLER_ICONS_LIST,
						'description' => "List the names of every icon already in the Cerb set, one per line. Use it to find a related icon to match, or to check whether a name is taken. Narrow a long list with `filter`.",
						'icon' => 'search',
						'labels' => ['active' => 'Listing icons...', 'summary' => 'Listed icons'],
						'parameters' => [
							'filter' => [
								'description' => 'Optional substring to match against icon names (e.g. "arrow").',
								'required' => false,
							],
						],
					],
				],
				'commands' => [
					'getGeometry' => [
						'tool' => 'get_geometry',
						'description' => "Read the SVG geometry currently in the builder. Call this first when the user refers to \"this icon\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the icon...', 'summary' => 'Read the icon'],
						'parameters' => [],
					],
					'setGeometry' => [
						'tool' => 'set_geometry',
						'description' => "Replace the icon's SVG geometry (the inner markup, without the outer <svg> element). This pushes a new revision, so the user can undo it and sees the preview update immediately.",
						'icon' => 'edit',
						'labels' => ['active' => 'Drawing the icon...', 'summary' => 'Drew the icon'],
						'parameters' => [
							'geometry' => [
								'description' => "The complete new SVG geometry.",
								'required' => true,
							],
						],
					],
					'getIconGeometry' => [
						'tool' => 'get_icon_geometry',
						'description' => "Read another icon's geometry from the existing Cerb set, by name -- use it to match the house style before drawing. Returns no output when no such icon exists.",
						'icon' => 'search',
						'labels' => ['active' => 'Looking up an icon...', 'summary' => 'Looked up an icon'],
						'parameters' => [
							'name' => [
								'description' => 'The icon name to look up (e.g. sparkles).',
								'required' => true,
							],
						],
					],
				],
			],

			'worklist' => [
				'label' => 'Worklist search bar',
				'icon' => 'search',
				'description' => "A worklist quick-search bar -- reads and rewrites the query, and can run the search.",
				'instructions' => "You are an assistant embedded in a Cerb worklist's search bar. You turn what someone is looking for into a Cerb search query.\n\nCall get_fields first, every time. The worklist's record type decides which fields are even valid, and a query written for the wrong type looks reasonable and matches nothing. Write the query, then run it -- the search runs asynchronously, so you will not see the results; say what you searched for and let the worklist answer.",
				'commands' => [
					'getFields' => [
						'tool' => 'get_fields',
						'description' => "Read the current search query and the record type it searches, as {query, record_type, record_context, view_id}. Call this first -- the record type determines which fields are even valid.",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the search...', 'summary' => 'Read the search'],
						'parameters' => [],
					],
					// The bridge is the generic `setField`, but a worklist has exactly ONE writable field, so `key`
					// is pinned rather than asked for: an enum of one is an argument a model can only get wrong.
					'setField' => [
						'tool' => 'set_query',
						'description' => "Write the search query. This only updates the field; call run_search to actually run it.",
						'icon' => 'edit',
						'labels' => ['active' => 'Updating the search...', 'summary' => 'Updated the search'],
						'command_params' => ['key' => 'query'],
						'parameters' => [
							'value' => [
								'description' => 'The complete Cerb search query to write into the field.',
								'required' => true,
							],
						],
					],
					'runSearch' => [
						'tool' => 'run_search',
						'description' => "Run the query currently in the field. The search runs asynchronously, so this reports that it STARTED, not what it found -- do not expect results back.",
						'icon' => 'search',
						'labels' => ['active' => 'Running the search...', 'summary' => 'Ran the search'],
						'parameters' => [],
					],
				],
			],

			'mail_reply' => [
				'label' => 'Mail reply editor',
				'icon' => 'mail',
				'description' => "The ticket reply/forward composer -- reads and writes recipients, subject, format, and body.",
				'instructions' => "You are an assistant embedded in Cerb's reply composer. You help a support worker write a reply to a customer.\n\nRead the draft before you touch it -- the worker may have already started, and replacing the body would discard it. Match the tone of the conversation, keep the worker's voice rather than imposing your own, and never invent facts about an account, an order, or a policy: if you need something you were not given, leave a gap and say so instead of filling it. You are drafting, not sending; the worker reviews and sends.",
				'commands' => [
					'getFields' => [
						'tool' => 'get_fields',
						'description' => "Read the reply form as {to, cc, bcc, subject, format, content}. `format` is markdown or plaintext. Call this first when the user refers to \"this reply\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the reply...', 'summary' => 'Read the reply'],
						'parameters' => [],
					],
					'setField' => [
						'tool' => 'set_field',
						'description' => "Replace a field's entire value. Send the COMPLETE new value; it overwrites what is there. Writing `format` also switches the editor's mode.",
						'icon' => 'edit',
						'labels' => ['active' => 'Updating the reply...', 'summary' => 'Updated the reply'],
						'parameters' => [
							'key' => [
								'description' => 'The field to replace.',
								'enum' => ['to', 'cc', 'bcc', 'subject', 'content', 'format'],
								'required' => true,
							],
							'value' => [
								'description' => "The complete new value. For `format`, one of markdown or plaintext.",
								'required' => true,
							],
						],
					],
				],
			],

			/*
			 * The global command bar. The odd one out, deliberately: every component above is an EDITOR, and its
			 * job is to read and write the document in front of the worker. This one has no document -- it is
			 * app-wide and follows the worker from page to page -- so it has no `get_fields` and, for now, no
			 * commands at all.
			 *
			 * Its commands ACT ON THE APP rather than on a document, so they are all one-way: it can put
			 * something in front of the worker but cannot read anything back. That is why there is no
			 * `get_fields` here and why every description says what the command does NOT return.
			 *
			 * Note the trigger is orthogonal: `interaction.worker` remains the right target for the command
			 * bar's non-agentic shortcuts -- look up an IP, reload a website cache, renew a cert, make a DKIM
			 * key -- and those keep working beside an `interaction.worker.agent` chat.
			 */
			'commandbar' => [
				'label' => 'Command bar',
				'icon' => 'console',
				'description' => "The global command bar -- app-wide rather than tied to an editor. Opens things for the worker.",
				'instructions' => "You are an assistant in Cerb's command bar. You are not attached to any editor: you help with whatever the worker is doing anywhere in Cerb -- answering questions, looking things up, and explaining how Cerb works.\n\nWhen the question is about where they are -- \"what is this page?\", \"what am I looking at\" -- call get_page rather than asking them to describe it, and use what it returns as search terms against any documentation you have. It tells you the page, never its contents, so anything ON the screen you still have to ask about.\n\nYou can also put things in front of them: open a search popup for any record type, with a query you have written. Prefer that over describing a query and asking them to paste it.\n\nWhen you don't know something about this particular installation -- whether a record type exists, what fields it has -- look it up rather than guessing at a name.",
				'commands' => [
					'getPage' => [
						'tool' => 'get_page',
						'description' => "Read where the worker is right now, as {page_uri, page_title, page_id, url, open_popups}. `page_uri` is the path Cerb ROUTED (e.g. `profiles/ticket/1234`), which is what they are actually looking at -- the browser `url` can say otherwise. Call this when they ask about \"this page\" or \"what am I looking at\", and use it as search terms against your documentation. It reports WHERE they are, never the contents.",
						'icon' => 'compass',
						'labels' => ['active' => 'Checking the page...', 'summary' => 'Checked the page'],
						'parameters' => [],
					],
					'openSearch' => [
						'tool' => 'open_search',
						'description' => "Open a search popup in front of the worker, for one record type, optionally prefilled with a query. This SHOWS them results; it does not return any to you -- you will not see what matched, or how many. Say what you searched for and let them read it.",
						'icon' => 'search',
						'labels' => ['active' => 'Opening a search...', 'summary' => 'Opened a search'],
						'parameters' => [
							'record_type' => [
								'description' => 'The record type to search, by alias (e.g. `ticket`, `worker`, `org`). An unknown alias opens nothing at all, so look the alias up rather than guessing it. Not every record type is searchable.',
								'required' => true,
							],
							'record_query' => [
								'description' => 'An optional Cerb search query to prefill (e.g. `status:open group:Support`). Omitted, the search opens unfiltered.',
								'required' => false,
							],
						],
					],
				],
			],
		];
	}

	/**
	 * One component by key, or null. The wizard resolves an author's choice through this; an unknown key means
	 * "no editor bridge" rather than an error, since a standalone chat is a legitimate answer.
	 */
	static function get(string $component) : ?array {
		return self::getAll()[$component] ?? null;
	}

	/**
	 * One component's tools as `llm.agent:` `tools:` entries.
	 *
	 * Two families, distinguished by their key prefix because that's how `llm.agent:` decides who answers:
	 *
	 *   ui_command/<tool>  a bridge command -- answered by the BROWSER, via a round-trip to the host editor.
	 *                      Carries `command`, the camelCase name the host's `runCommand()` dispatches on.
	 *   ui_server/<tool>   answered HERE, with no round-trip. Carries `handler`, which the trigger runs.
	 *
	 * Everything else is the shape the rest of the LLM subsystem already speaks -- the same `description` /
	 * `icon` / `labels` / `parameters` an author writes by hand under `tool/<name>:` -- so the schema builder,
	 * the transcript's label map, and the session's stored tool set read both families without knowing the
	 * difference. Neither `command` nor `handler` is ever shown to the model.
	 *
	 * An unknown component returns `[]` rather than throwing: a chat opened somewhere with no editor to
	 * drive is a standalone chat, not an error.
	 */
	static function getToolsFor(string $component) : array {
		if(!($meta = self::get($component)))
			return [];

		$tools = [];

		foreach($meta['commands'] as $bridge_name => $command) {
			$tool = self::_toolEntry($command);
			$tool['command'] = $bridge_name;

			// Deliberately NOT under `parameters:` -- that's the model-facing schema, and these are exactly the
			// arguments the model must never see or set.
			if(($command_params = $command['command_params'] ?? []))
				$tool['command_params'] = $command_params;

			$tools['ui_command/' . $command['tool']] = $tool;
		}

		foreach($meta['server_tools'] ?? [] as $tool_name => $command) {
			$tool = self::_toolEntry($command);
			$tool['handler'] = $command['handler'];

			$tools['ui_server/' . $tool_name] = $tool;
		}

		return $tools;
	}

	// The half of a `tools:` entry both families share.
	private static function _toolEntry(array $command) : array {
		$tool = [
			'description' => $command['description'],
			'icon' => $command['icon'],
			'labels' => $command['labels'],
		];

		foreach($command['parameters'] as $param_name => $param) {
			// `string/` because that's the only param type the provider schema builder emits
			// (_DevblocksLlmService::_toolSchemaCustom); anything else is silently dropped.
			$tool['parameters']['string/' . $param_name] = array_filter([
				'description' => $param['description'],
				'enum' => $param['enum'] ?? null,
				'required' => $param['required'] ?? false,
			]);
		}

		return $tool;
	}
}
