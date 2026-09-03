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
 *   commandbar     features/cerberusweb.core/templates/automations/interactions/button.tpl
 *
 * Adding a command to a host means adding it here too, BY HAND -- nothing checks. The two drift silently,
 * and the failure is quiet on both sides: a command listed here that the host doesn't implement returns '',
 * which reads as a model failure rather than a wiring bug, and a command the host gained but this file
 * didn't simply never reaches a model. That second direction now costs more than it used to: these entries
 * ARE the agent's tools at runtime, not just a template the author can fix up afterwards.
 *
 * (`mail_compose` was named in the retired `agent.pane` toolbar's placeholder notes but no host implements it.
 * It is not here, and should not be until one does.)
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
	 * `description` is what an author reads when picking a location -- the Agent Chat wizard's chooser and the
	 * worker peek's AI tab. Both render it beside the `label`, so it must NOT restate the name: "Icon Builder --
	 * the Setup icon builder" tells a reader nothing. Lead with what the agent can DO there.
	 *
	 * `description` and `tagline` are BOTH one line about this surface, aimed at different readers.
	 *
	 * `description` states what the surface can do. It labels the row in the AI tab's "Where it runs" list,
	 * where a person is deciding whether to switch an agent on here.
	 *
	 * `tagline` answers "what is an agent on this surface for", in the voice an author would use -- so it is
	 * written about an AGENT rather than about the surface. It SHIPS as the default: every launcher on this
	 * surface reads it until an agent says something more specific in its own per-surface `description:`
	 * (`Launchers::getKata()`), and it is the placeholder under that field so the editor shows exactly what
	 * leaving it blank will produce. Keep it short and true of ANY agent here -- it is what most launchers will
	 * actually say, and one long enough to clip in a text input has stopped being a tagline.
	 *
	 * `instructions` is the FALLBACK role -- what the model reads when the role asset is missing. The real
	 * one lives in `assets/agents/<component>.md` (see getRoleFor()), because it is prose and a PHP
	 * string literal is a bad place to maintain prose. Keep this copy short and correct: it is what a broken
	 * deploy ships.
	 *
	 * `skills` names the `cerb-agents` skills this component's work needs, emitted by getSystemPromptFor()
	 * only when that volume is mounted. It has TWO entry shapes, and the difference is what the model is
	 * told:
	 *
	 *   'records'                            reference -- read it when you need it
	 *   'mail-replies' => 'draft a reply'    REQUIRED -- read it before you do that work
	 *
	 * A keyed entry's value is the work it gates, phrased to finish "Before you ___". Reserve it for a skill
	 * that carries CONVENTIONS the output is measured against -- house quoting style, a language whose
	 * indentation isn't YAML's, an icon set's geometry rules. Those are the ones a capable model gets wrong
	 * confidently, because its default is a reasonable answer that is not ours. A skill that is only a lookup
	 * table (field names, filter keys) stays a bare string: making it a precondition spends a read on every
	 * conversation to prevent nothing, since not knowing a name is a failure the model can already feel.
	 *
	 * `docs` does the same for the documentation volume: the handful of paths where THIS component's work is
	 * documented.
	 *
	 * Commands are keyed by the BRIDGE name (camelCase -- what `runCommand` dispatches on and what a
	 * `uiCommand` element's `command:` must say). Each carries:
	 *
	 *   tool            the snake_case name the MODEL calls. ALWAYS `cerb_`-prefixed: a tool name has to be
	 *                 	 unique within a turn, and an agent's own `agent_tool` records share that namespace.
	 *                 	 The prefix is reserved (DAO_AgentTool rejects it) so a customer's tool can never
	 *    	           	 shadow a host's -- the same move MCP hosts make with `mcp__<server>__<tool>`.
	 *   description     what the model reads to decide whether to call it
	 *   icon            transcript icon -- MUST exist in getCerbIcons(); an invented name renders nothing
	 *   labels          transcript strings: `active` while running, `summary` once done
	 *   parameters      ordered map of name => {description, enum?, required}; [] for a no-argument command
	 *   command_params  OPTIONAL constants passed to the bridge but never shown to the model, for when a
	 *                   generic bridge command has one sensible use here. They also win over anything the
	 *                   model sends under the same name, so a pinned argument stays pinned.
	 *
	 * @return array<string,array{label:string,icon:string,description:string,tagline:string,instructions:string,skills:array<int|string,string>,docs:array,commands:array}>
	 */
	static function getAll() : array {
		return [
			'automation' => [
				'label' => 'Automation Editor',
				'icon' => 'zap',
				'description' => "Reads and writes the name, description, trigger, script, and policy of the automation being edited.",
				'tagline' => 'Help writing and debugging automations',
				'instructions' => "You are an assistant embedded in Cerb's automation editor. You help write and debug automations: KATA scripts bound to a trigger, plus the command policy that grants the script its privileges.\n\nThe script and the policy move together. A script that gains a privileged command stops working until the policy allows it, so when you add one, check the policy in the same turn.\n\nWhen the request doesn't imply a trigger, use `cerb.trigger.automation.function` and say you assumed it.\n\nPrefer edit_field over set_field for the script and the policy. They are long, the author may have unsaved work elsewhere in them, and a whole-field overwrite silently discards it.",
				'skills' => [
					'kata' => 'write or edit any KATA',
					'automations' => 'write or edit an automation',
					'scripting',
					'records',
				],
				'docs' => ['references/docs/automations.md', 'references/docs/kata.md'],
				'commands' => [
					'getFields' => [
						'tool' => 'cerb_get_fields',
						'description' => "Read the current automation form state from the user's browser. Call this first when the user refers to \"this automation\" or asks what it does.",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the automation...', 'summary' => 'Read the automation'],
						'parameters' => [],
					],
					'setField' => [
						'tool' => 'cerb_set_field',
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
						'tool' => 'cerb_edit_field',
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
						'tool' => 'cerb_grep_field',
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
						'tool' => 'cerb_get_diff',
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
						'tool' => 'cerb_change_tab',
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
						'tool' => 'cerb_highlight_line',
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
						'tool' => 'cerb_highlight_key',
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
				'description' => "Reads and writes the query being tested, under Setup > Developers.",
				'tagline' => 'Help writing data queries',
				'instructions' => "You are an assistant embedded in Cerb's data query tester. You help write data queries: the `type:` of source, its required parameters, and the fields, formats, and subtotals it returns.\n\nRead the editor before you change it. A data query is dense and mostly parameters, so a targeted edit is nearly always right and a whole-field rewrite nearly always loses something the author meant to keep.",
				'skills' => [
					'data-queries' => 'write or edit a data query',
					'records',
					'search-queries',
				],
				'docs' => ['references/docs/data-queries/'],
				'commands' => [
					'getEditorValue' => [
						'tool' => 'cerb_get_query',
						'description' => "Read the current query from the user's editor. Call this first when the user refers to \"this query\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the query...', 'summary' => 'Read the query'],
						'parameters' => [],
					],
					'setEditorValue' => [
						'tool' => 'cerb_set_query',
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
						'tool' => 'cerb_edit_query',
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
						'tool' => 'cerb_grep_query',
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
						'tool' => 'cerb_highlight_line',
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
				'icon' => 'editor',
				'description' => "Reads and writes the script being tested, under Setup > Developers.",
				'tagline' => 'Help with automation scripting',
				'instructions' => "You are an assistant embedded in Cerb's automation scripting tester. You help write and debug Twig expressions and templates against a test dictionary.\n\nThis is real Twig, sandboxed: standard Twig works, Cerb adds its own filters/functions/tests on top, and the template-composition tags (macro, include, import, extends, block) are unavailable.\n\nThis editor is for trying an expression in isolation, so keep changes small and explain what a filter chain does rather than only handing back a longer one.",
				'skills' => [
					'scripting' => 'write or edit any scripting expression or template',
					'records',
				],
				'docs' => ['references/docs/scripting.md'],
				'commands' => [
					'getEditorValue' => [
						'tool' => 'cerb_get_script',
						'description' => "Read the current script from the user's editor. Call this first when the user refers to \"this script\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the script...', 'summary' => 'Read the script'],
						'parameters' => [],
					],
					'setEditorValue' => [
						'tool' => 'cerb_set_script',
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
						'tool' => 'cerb_edit_script',
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
						'tool' => 'cerb_grep_script',
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
						'tool' => 'cerb_highlight_line',
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
						'tool' => 'cerb_get_diff',
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
				'description' => "Reads and writes the SVG geometry of the icon being drawn, and can look up every name in the set.",
				'tagline' => 'Help drawing icons',
				'instructions' => "You are an assistant embedded in Cerb's icon builder. You help draw icons for Cerb's icon set: SVG geometry on a 24x24 viewBox, rendered as a CSS mask and tinted by currentColor.\n\nBecause it is a mask, only the shape matters -- fill and stroke colors in the geometry are discarded. Read the current geometry before editing, and use get_icon_geometry to look at a shipped icon when you need the set's existing conventions for weight, corner radius, or optical sizing.",
				'skills' => [
					'icons' => 'draw or edit any icon geometry',
				],
				'docs' => ['references/docs/developers/icons.md'],
				// Naming an existing icon is most of this job, and `get_icon_geometry` can only look one up once
				// you know it exists -- so the set has to be enumerable.
				'server_tools' => [
					'cerb_list_icons' => [
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
						'tool' => 'cerb_get_geometry',
						'description' => "Read the SVG geometry currently in the builder. Call this first when the user refers to \"this icon\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the icon...', 'summary' => 'Read the icon'],
						'parameters' => [],
					],
					'setGeometry' => [
						'tool' => 'cerb_set_geometry',
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
						'tool' => 'cerb_get_icon_geometry',
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
				'label' => 'Worklist Search Bar',
				'icon' => 'search',
				'description' => "Reads and rewrites the search query above any worklist, and can run it.",
				'tagline' => 'Help searching for records',
				'instructions' => "You are an assistant embedded in a Cerb worklist's search bar. You turn what someone is looking for into a Cerb search query.\n\nCall get_fields first, every time. The worklist's record type decides which fields are even valid, and a query written for the wrong type looks reasonable and matches nothing. Write the query, then run it -- the search runs asynchronously, so you will not see the results; say what you searched for and let the worklist answer.",
				'skills' => [
					'search-queries' => 'write or edit a search query',
					'records',
				],
				'docs' => ['references/docs/search.md'],
				'commands' => [
					'getFields' => [
						'tool' => 'cerb_get_fields',
						'description' => "Read the current search query and the record type it searches, as {query, record_type, record_context, view_id}. Call this first -- the record type determines which fields are even valid.",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the search...', 'summary' => 'Read the search'],
						'parameters' => [],
					],
					// The bridge is the generic `setField`, but a worklist has exactly ONE writable field, so `key`
					// is pinned rather than asked for: an enum of one is an argument a model can only get wrong.
					'setField' => [
						'tool' => 'cerb_set_query',
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
						'tool' => 'cerb_run_search',
						'description' => "Run the query currently in the field. The search runs asynchronously, so this reports that it STARTED, not what it found -- do not expect results back.",
						'icon' => 'search',
						'labels' => ['active' => 'Running the search...', 'summary' => 'Ran the search'],
						'parameters' => [],
					],
				],
			],

			'mail_reply' => [
				'label' => 'Mail Reply Editor',
				'icon' => 'mail',
				'description' => "Reads and writes the recipients, subject, format, and body of a reply or forward.",
				'tagline' => 'Help writing replies to customers',
				'instructions' => "You are an assistant embedded in Cerb's reply composer. You help a support worker write a reply to a customer.\n\nRead the draft before you touch it -- the worker may have already started, and replacing the body would discard it. Match the tone of the conversation, keep the worker's voice rather than imposing your own, and never invent facts about an account, an order, or a policy: if you need something you were not given, leave a gap and say so instead of filling it. You are drafting, not sending; the worker reviews and sends.",
				'skills' => [
					'mail-replies' => 'draft or revise a reply',
				],
				'commands' => [
					'getFields' => [
						'tool' => 'cerb_get_fields',
						'description' => "Read the reply form as {to, cc, bcc, subject, format, content}. `format` is markdown or plaintext. Call this first when the user refers to \"this reply\".",
						'icon' => 'eye-open',
						'labels' => ['active' => 'Reading the reply...', 'summary' => 'Read the reply'],
						'parameters' => [],
					],
					'setField' => [
						'tool' => 'cerb_set_field',
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
				'label' => 'Command Bar',
				'icon' => 'console',
				'description' => "Available on every page from the lower-right floating icon.",
				'tagline' => 'Start an AI agent chat',
				'instructions' => "You are an assistant in Cerb's command bar. You are not attached to any editor: you help with whatever the worker is doing anywhere in Cerb -- answering questions, looking things up, and explaining how Cerb works.\n\nWhen the question is about where they are -- \"what is this page?\", \"what am I looking at\" -- call get_page rather than asking them to describe it, and use what it returns as search terms against any documentation you have. It tells you the page, never its contents, so anything ON the screen you still have to ask about.\n\nYou can also put things in front of them: open a search popup for any record type, with a query you have written. Prefer that over describing a query and asking them to paste it.\n\nWhen you don't know something about this particular installation -- whether a record type exists, what fields it has -- look it up rather than guessing at a name.",
				'skills' => [
					'search-queries' => 'write a search query',
					'records',
				],
				'commands' => [
					'getPage' => [
						'tool' => 'cerb_get_page',
						'description' => "Read where the worker is right now, as {page_uri, page_title, page_id, url, open_popups}. `page_uri` is the path Cerb ROUTED (e.g. `profiles/ticket/1234`), which is what they are actually looking at -- the browser `url` can say otherwise. Call this when they ask about \"this page\" or \"what am I looking at\", and use it as search terms against your documentation. It reports WHERE they are, never the contents.",
						'icon' => 'compass',
						'labels' => ['active' => 'Checking the page...', 'summary' => 'Checked the page'],
						'parameters' => [],
					],
					'openSearch' => [
						'tool' => 'cerb_open_search',
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
	 * Just the identity of each surface -- what an editor needs to offer them as a list, without shipping every
	 * tool description and parameter schema to the browser.
	 *
	 * The catalog being the single source of this list is what makes a new component configurable on an agent
	 * the moment it's added here: no template edit, no schema edit (`agent()`'s `components:` is an
	 * attributePatterns block), and no migration for agents that don't mention it.
	 *
	 * Ordered by label so the list reads the same on every install.
	 *
	 * @return array `{component => {label, icon, description, tagline}}`
	 */
	static function getSurfaceCatalog() : array {
		$surfaces = [];

		foreach(self::getAll() as $key => $meta) {
			$surfaces[$key] = [
				'label' => strval($meta['label'] ?? $key),
				'icon' => strval($meta['icon'] ?? 'bot'),
				'description' => strval($meta['description'] ?? ''),
				'tagline' => strval($meta['tagline'] ?? ''),
			];
		}

		uasort($surfaces, fn($a, $b) => strcasecmp($a['label'], $b['label']));

		return $surfaces;
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

	/**
	 * One component's system prompt: the role, the tool inventory, and -- only when the skills volume is
	 * actually mounted -- a pointer at it, including the reads this component requires before certain work.
	 *
	 * This is what `interaction.worker.agent` contributes at RUNTIME, so improving a role reaches every
	 * existing chat on the next release. It used to be copied into each generated automation's
	 * `system_prompt:` once, at authoring time, and was frozen there forever -- the same drift that made us
	 * stop generating the built-in tools into every chat.
	 *
	 * $volumes maps a ROLE ('skills', 'docs') to the name that volume is mounted under, or omits it when the
	 * turn doesn't have it. The caller resolves that because this file can't see a session's mounts and
	 * shouldn't -- and pointing an agent at files it cannot read is worse than saying nothing. Taking names
	 * rather than flags is also what keeps a volume name out of this file entirely.
	 *
	 * An unknown component returns '' -- a chat with no editor beside it writes its own prompt.
	 */
	static function getSystemPromptFor(string $component, array $volumes=[]) : string {
		if(!($meta = self::get($component)))
			return '';

		$skills_volume = trim(strval($volumes['skills'] ?? ''));
		$docs_volume = trim(strval($volumes['docs'] ?? ''));

		$blocks = [self::getRoleFor($component), self::getSharedRole()];

		if('' !== ($inventory = self::getInventoryFor($component)))
			$blocks[] = $inventory;

		// The documentation, and where in it this component's work lives. The role already says to verify
		// rather than guess; this is the part that can only be said once we know the volume is really here.
		if('' !== $docs_volume) {
			$line = sprintf(
				"The Cerb documentation is mounted at `@%s` and is the authority -- look a name up rather than "
					. "recalling it, because an invented key usually returns nothing instead of failing.",
				$docs_volume
			);

			if(($paths = $meta['docs'] ?? []))
				$line .= ' ' . sprintf('For this work, start at %s.', self::_andList(array_map(
					fn($path) => sprintf('`@%s/%s`', $docs_volume, ltrim($path, '/')),
					$paths
				)));

			$blocks[] = $line;
		}

		// Named skills rather than "read the index and work it out": the index costs another read, and the
		// component already knows which skills its work needs. `@<volume>` resolves by NAME, so this holds
		// even when the volume is mounted somewhere other than `/<volume>`.
		if('' !== $skills_volume && ($skills = $meta['skills'] ?? [])) {
			$required = [];
			$reference = [];

			// A bare entry is lookup material; a keyed one names the work it gates. See getAll().
			foreach($skills as $key => $value) {
				if(is_int($key)) {
					$reference[] = $value;
				} else {
					$required[$key] = $value;
				}
			}

			$path = fn($skill) => sprintf('`@%s/skills/%s/SKILL.md`', $skills_volume, $skill);

			$parts = [sprintf(
				"Reference skills are mounted at `@%s`. Nothing there is loaded for you -- read a skill with "
					. "the terminal, and `@%s/INDEX.md` lists the full set.",
				$skills_volume,
				$skills_volume
			)];

			// Stated as a PRECONDITION ON THE WORK, not as a reading list. A model told only that a file is
			// relevant does the work first and consults it after, which is how a reply gets top-posted and
			// then apologized for -- the apology is not the fix, the ordering is. Naming what the read
			// buys ("conventions that are not your default") is what makes it worth a tool call to a model
			// that already believes it knows how to write a reply.
			if($required) {
				$parts[] = "The ones listed below are preconditions, not suggestions. Each carries house "
					. "conventions your output is measured against, and those conventions are NOT what you would do by "
					. "default, so read the one that applies BEFORE you produce anything. Reading it "
					. "afterwards is too late: a correction the worker has to ask for is a failure. Once per "
					. "conversation is enough -- don't re-read a skill you've already read.";

				$lines = [];

				foreach($required as $skill => $gate)
					$lines[] = sprintf('- Before you %s, read %s.', trim($gate), $path($skill));

				$parts[] = implode("\n", $lines);
			}

			if($reference)
				$parts[] = sprintf(
					'Also available, to read when you need one: %s.',
					self::_andList(array_map($path, $reference))
				);

			$blocks[] = implode("\n\n", $parts);
		}

		return implode("\n\n", array_filter($blocks));
	}

	/**
	 * The ROLE half: `assets/agents/<component>.md`, falling back to the catalog's `instructions`.
	 *
	 * Prose lives in a file because it is prose -- readable diffs, no escaping, and no practical ceiling on
	 * how much guidance a role can carry. `__DIR__` rather than `APP_PATH` keeps this class loadable with no
	 * bootstrap, which is the property its header promises.
	 *
	 * One flat file per component, named for its key. A directory each would be ceremony while a role is the
	 * only per-component asset there is; if one ever grows companions, that's the point to nest it.
	 *
	 * NOT an agent filesystem: `assets/agents/` is a sibling of `assets/agent_filesystems/`, so
	 * `FilesystemAssets::syncAll()` never imports it. The roles are ours; the skills volume is the agent's.
	 */
	static function getRoleFor(string $component) : string {
		static $cache = [];

		if(array_key_exists($component, $cache))
			return $cache[$component];

		$role = '';

		// The catalog is the allowlist: `$component` arrives from caller metadata that is never
		// validated, and it is being concatenated into a path. Only a key the catalog already knows
		// gets that far.
		if(($meta = self::get($component))) {
			$path = __DIR__ . '/../../../assets/agents/' . $component . '.md';

			if(is_readable($path) && false !== ($contents = file_get_contents($path)))
				$role = trim($contents);

			// A missing asset is a broken deploy, not a configuration. Degrade to the catalog's copy rather
			// than shipping an empty system prompt, but say so -- silently working forever is how a typo'd
			// path survives.
			if('' === $role) {
				$role = trim(strval($meta['instructions'] ?? ''));

				if(class_exists('DevblocksPlatform'))
					\DevblocksPlatform::services()->log()->info(
						sprintf("Agent role asset missing or empty (%s); using the built-in fallback.", $path)
					);
			}
		}

		return $cache[$component] = $role;
	}

	/**
	 * The half of the role that is the same for every component: how to write for a chat this narrow.
	 *
	 * `assets/agents/_shared.md`, read once and emitted right after the component's own role. It sits in
	 * one file rather than a paragraph in each of the seven because the surface is one fact -- a sidebar
	 * or a floating panel, never a document pane -- and seven copies of it drift the moment one is tuned.
	 * A new component gets it for free, and so does one whose role asset is missing and falls back to the
	 * catalog's `instructions`.
	 *
	 * The leading underscore keeps it out of the filename-IS-the-lookup namespace `getRoleFor()` uses: no
	 * component key can collide with it, and it is read by constant path rather than from caller metadata.
	 *
	 * Missing is not fatal -- a deploy that lost this file should still answer, just less tidily.
	 */
	static function getSharedRole() : string {
		static $cache = null;

		if(is_null($cache)) {
			$path = __DIR__ . '/../../../assets/agents/_shared.md';
			$cache = (is_readable($path) && false !== ($contents = file_get_contents($path)))
				? trim($contents)
				: '';
		}

		return $cache;
	}

	/**
	 * The INVENTORY half: the tools this component answers, in prose.
	 *
	 * Built from the live catalog on purpose. The provider already sends tool schemas, but a model that
	 * isn't told in PROSE that it can read the editor tends to ask the user to paste instead. Hand-writing
	 * this into the role file would be the drift this whole arrangement removes -- a tool listed here that
	 * the host doesn't implement returns '', which reads as a model failure rather than a wiring bug.
	 *
	 * Both families, since the model can't tell them apart and shouldn't have to: `commands` are answered by
	 * the browser, `server_tools` here. A component may have neither -- the command bar is a real place
	 * whose host has no command bridge yet -- and then there is no inventory to write, and nothing is
	 * emitted. Saying "you act on it through these tools:" above an empty list would contradict the role
	 * that just said it can't see the screen.
	 *
	 * NOTHING about the filesystem is written here. The `agent_terminal` tool's own schema description
	 * already carries all of it, and carries it better: a "Mounted filesystems:" overview with each volume's
	 * path, mode, description AND an `ls` of its contents, the whole-line-in-`command` shape, "no working
	 * directory", `Filesystem::help()` verbatim, and `/tmp` plus the `|` pipeline. Restating it cost a copy
	 * of all that in every turn's system prompt, and it was WRONG in a way it couldn't detect: it listed
	 * `search` unconditionally, while the schema drops `search` when no volumes are mounted.
	 */
	static function getInventoryFor(string $component) : string {
		if(!($meta = self::get($component)))
			return '';

		$tools = [];

		// Bridge commands carry their model-facing name in `tool`; a server tool IS its key.
		foreach($meta['commands'] as $command)
			$tools[$command['tool']] = $command['description'];

		foreach($meta['server_tools'] ?? [] as $tool_name => $server_tool)
			$tools[$tool_name] = $server_tool['description'];

		if(!$tools)
			return '';

		$lines = ['You act on it through these tools:'];

		foreach($tools as $tool_name => $description)
			$lines[] = sprintf('- %s -- %s', $tool_name, $description);

		$lines[] = '';
		$lines[] = 'Read before you write. Prefer acting over describing: make the change, then say briefly what changed and why.';

		return implode("\n", $lines);
	}

	// "a", "a and b", "a, b, and c" -- so a one-skill component doesn't read like a list of one.
	private static function _andList(array $items) : string {
		if(count($items) < 3)
			return implode(' and ', $items);

		$last = array_pop($items);

		return implode(', ', $items) . ', and ' . $last;
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
