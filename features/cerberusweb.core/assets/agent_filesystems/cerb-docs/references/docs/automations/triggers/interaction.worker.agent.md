---
id: "docs-automations-triggers-interaction-worker-agent"
title: "Automations: interaction.worker.agent"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker.agent/"
summary: "This page documents the interaction.worker.agent automation trigger in Cerb. It contributes the host editor's commands to an agent as tools automatically, so a chat can read and rewrite the editor beside it with nothing in its script, and it adds the uiCommand form element, which round-trips a command to that host directly. Because both depend on the caller providing a command bridge, they're advertised only on this trigger rather than on the generic worker trigger. The page covers when to choose this trigger over interaction.worker and interaction.internal, the browser-answered and server-answered tool families, gating a tool behind a human with on_tool and tool.return, why a model's tool names differ from the bridge's command names, the uiCommand syntax and its return variable, the agent scope a chat reads to stay reusable by every agent, the two caller names these interactions are launched under and why a chat should leave its callers block unset, and the command vocabulary of every host -- the automation editor, the scripting and data query testers, the command bar, the Icon Builder, mail replies, and worklist search bars."
tags: ["docs", "docs-automations"]
---
The **interaction.worker.agent** [automation](/docs/automations/) trigger extends [interaction.worker](/docs/automations/triggers/interaction.worker/) in two ways: it hands an agent its host editor's commands as [tools](#tools) automatically, and it adds the [uiCommand](/docs/automations/triggers/interaction.worker/elements/uiCommand/) form element for driving that host directly.

It's the trigger for an agent chat that sits beside an editor and can read and write the document in front of it.

Everything from `interaction.worker` – its inputs, outputs, and form elements – is available here as well, plus `agent_*`, the AI [worker](/docs/records/types/worker/) this chat runs as.

- [The agent running the chat](#the-agent-running-the-chat)
- [Choosing a trigger](#choosing-a-trigger)
- [Why a separate trigger](#why-a-separate-trigger)
- [Tools](#tools)
  - [Gating a tool behind a human](#gating-a-tool-behind-a-human)
  - [Tool names and command names differ](#tool-names-and-command-names-differ)

- [uiCommand](#uicommand)
  - [Gating several commands in one form](#gating-several-commands-in-one-form)

- [Hosts](#hosts)
  - [Caller policy](#caller-policy)
  - [Automation editor](#automation-editor)
  - [Automation Scripting Tester](#automation-scripting-tester)
  - [Command bar](#command-bar)
  - [Data Query Tester](#data-query-tester)
  - [Icon Builder](#icon-builder)
  - [Mail Reply](#mail-reply)
  - [Mail Routing](#mail-routing)
  - [Worklist search fields](#worklist-search-fields)
  - [Return values and failures](#return-values-and-failures)

# The agent running the chat

A [launcher](/docs/toolbars/interactions/agent.pane/) tells the interaction which [agent](/docs/agents/) it stands for, and the trigger exposes that as `agent_*` – a [worker](/docs/records/types/worker/) record with key expansion, so `agent_name` and `agent__image_url` are the agent's own name and avatar.

Pass it on rather than naming an agent in the script:

```
await:
  elements:
    agentPrompt/prompt_agent:
      agent@key: agent_id
```

The same form works on [`llm.agent:`](/docs/automations/commands/llm.agent/). Which agent is running belongs to the trigger's **state**, exactly as the active worker does, and that's what lets one interaction serve every agent instead of being copied per agent. `agent_*` is unset when no agent launched the interaction.

The launcher passes the agent as a **reserved** input named `agent`, consumed before your `inputs:` are validated. An automation that declares `agent` in its own `inputs:` block fails to run with **Unknown inputs: agent**. Read `agent_*` from scope instead.

# Choosing a trigger

Three interaction triggers look similar from the outside. The difference is who the conversation belongs to, and whether it can reach the editor beside it.

| Trigger | Use it for |
| --- | --- |
| [interaction.worker](/docs/automations/triggers/interaction.worker/) | An ordinary [worker interaction](/docs/interactions/). No UI component access in the browser. |
| **interaction.worker.agent** | A worker's agent chat that drives the editor it was launched beside. |
| [interaction.internal](/docs/plugins/extensions/cerb.trigger.interaction.internal/) | Automations Cerb runs on its own behalf – record choosers, autocomplete helpers, the dialogs inside the builders. |

`interaction.internal` exists so a worker can filter Cerb's own plumbing out of an automation [worklist](/docs/worklists/) and never think about it again. Agent panes are the opposite: they're the worker's own conversations, they park as resumable continuations, and they appear in the pane's History and the command bar's Resume list.

# Why a separate trigger

`uiCommand` round-trips a command to the **host editor** the interaction was launched beside. That only works when the caller provides a command bridge, so the element is advertised on this trigger alone.

Gating it this way means no caller without the bridge is ever handed a capability it can't fulfill.

You don't have to keep track of it yourself: saving an automation whose `await:form:` uses an element its trigger doesn't offer is rejected, and the error names the trigger you need.

# Tools

A chat on this trigger is handed its host's commands as tools **automatically**. Nothing in the script declares them: the trigger resolves the host its pane is mounted on and contributes that host's commands to the turn, the same way `mounts:` provisions an agent's terminal. A chat opened beside the automation editor, a [worklist](/docs/worklists/) search bar, or the [Icon Builder](/docs/setup/developers/icon-builder/) can read and rewrite it with an empty `tools:` block.

Two families, differing only in who answers the call:

| Family | Answered by | Notes |
| --- | --- | --- |
| `ui_command/<tool>` | The browser | A round-trip to the host editor, the same bridge a [`uiCommand`](#uicommand) element uses |
| `ui_server/<tool>` | Cerb | Answered on the server with no round-trip – for what the installation knows rather than what's on screen |

The Icon Builder's `cerb_list_icons` is a server tool: naming an existing icon is most of that job, and the browser has no way to say what the whole set contains.

Your own `tools:` entries are unaffected. Declare `llm.tool` automations and hand-written `tool/` definitions as usual; they sit alongside the host's. A tool you declare with the same **name** as one of the host's wins, which is how a host command is overridden.

Because the host contributes them rather than your script copying them, an editor that gains a command reaches every existing chat on its next turn – including chats already saved.

## Gating a tool behind a human

An `on_tool:` branch still runs before every host command, so that's where a call is intercepted. Answer it with `tool.return:` and the editor is never touched:

```
on_tool:
  decision/tool:
    outcome/confirm_write:
      if@bool: {{__tool.name == 'set_script'}}
      then:
        await:
          form:
            title: Overwrite the script?
            elements:
              say/warning:
                content: The agent wants to replace the whole script.
        # Falling through runs the command; `tool.return:` here would answer instead
```

## Tool names and command names differ

The name a **model** calls is not the name the **bridge** dispatches on, and one isn't derivable from the other. Each host names its tools to read naturally in that place: the Data Query Tester offers `setEditorValue` to the model as `cerb_set_query`, while the Scripting Tester offers the same command as `cerb_set_script`.

Each host's table below lists both. Use the tool name when you're reasoning about what the model sees – a system prompt, an `on_tool:` branch, a transcript – and the command name in a `uiCommand` element's `command:`.

A host can also **pin** an argument the model never sees. A worklist search bar has exactly one writable field, so its `cerb_set_query` tool takes `value` alone and the bridge's `key` is supplied for it – an enum of one is an argument a model can only get wrong.

# uiCommand

A `uiCommand` element drives a host directly from a form, rather than waiting for the model to call a tool. It's the manual path: automatic tools cover a host's catalog, while `uiCommand` runs a command of your choosing at a moment of your choosing – when a form opens, after a worker answers a question, or as part of a chat that dispatches commands itself.

The element names a command for the host editor to run, and the key you give it is the **return variable**:

```
await:
  form:
    elements:
      uiCommand/current_value:
        command: getEditorValue
```

| Key | Notes |
| --- | --- |
| `command:` | The name of the host callback to run |
| `params:` | Optional parameters passed to the callback |
| `disabled:` | When true, render inert – emit an empty return variable with no round-trip |

Unlike most awaits, a `uiCommand` fills its value synchronously from the host's callback. It does **not** submit the form on its own; the form's own `submit:` posts it along with everything else.

See the [uiCommand](/docs/automations/triggers/interaction.worker/elements/uiCommand/) element reference for the full command vocabulary of each host, the shape of returned values, and the `ui_capabilities` input that makes one automation portable across editors.

## Gating several commands in one form

Because a disabled `uiCommand` emits an empty return variable instead of round-tripping, one `await:form:` can carry several commands and let the agent's request decide which actually runs:

```
await:
  form:
    elements:
      uiCommand/read_result:
        command: getEditorValue
        disabled@bool: {{__tool.parameters.command != 'getEditorValue'}}
      uiCommand/write_result:
        command: setEditorValue
        params:
          value: {{__tool.parameters.value}}
        disabled@bool: {{__tool.parameters.command != 'setEditorValue'}}
```

# Hosts

A host provides the command bridge. Which [agents](/docs/agents/) each host offers is configured [on each agent](/docs/toolbars/interactions/agent.pane/) rather than on the host. The hosts today are:

| Host | `component` |
| --- | --- |
| Automation editor | `automation` |
| Automation Scripting Tester | `automation_scripting` |
| Command bar | `commandbar` |
| Data Query Tester | `data_query` |
| [Icon Builder](/docs/setup/developers/icon-builder/) | `icon` |
| Mail Reply | `mail_reply` |
| Mail Routing | `mail_routing` |
| [Worklist](/docs/worklists/) search fields | `worklist` |

An agent appears on a host by having a block for that host's `component` under `components:` in its own configuration – see [agent launchers](/docs/toolbars/interactions/agent.pane/). The host reports its `component` in `caller_params`, and that value is functional rather than informational: it's what [`llm.agent:`](/docs/automations/commands/llm.agent/) resolves this host's tools from, and which of the agent's per-host overrides apply.

There's no shared command vocabulary. Each host declares its own, so read `ui_capabilities` at runtime rather than hard-coding a list – that's what makes one automation portable across hosts. Every return value is a **string**; commands that need structure return JSON the automation has to decode.

In the tables below, **Command** is the bridge name a [`uiCommand`](#uicommand) element sends and **Tool** is the name the model calls. The two differ on several hosts. Every built-in tool is named with a reserved `cerb_` prefix, so an [agent tool](/docs/records/types/agent_tool/) you create can never shadow one – a tool record whose name starts with `cerb_` is refused when it's saved.

**No host exposes results.** The search bar can start a search and the testers can set a query, but neither hands anything back through the bridge. An interaction reads results server-side on its next turn with a [data query](/docs/data-queries/) instead. Don't go looking for a `getResults`.

## Caller policy

Two caller names launch these interactions: the six editor panes and worklist search fields post `agent.pane`, while the command bar posts `cerb.toolbar.global.menu`.

**Leave `callers:` unset.** An automation with no `callers:` block allows every caller, which is what an agent chat wants. Naming only `agent.pane` is the tempting mistake -- it loses the command bar and breaks resuming the chat from History. A `callers:` block here has to allow **both** names.

## Automation editor

`component: automation`. The only field-keyed code host: `key:` selects `script` or `policy`.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getFields` | `cerb_get_fields` | &nbsp; | JSON `{name, description, trigger, script, policy}`. `trigger` is the raw extension ID, not a label. |
| `setField` | `cerb_set_field` | `key:` one of `name`, `description`, `trigger`, `script`, `policy`; `value:` | `ok`. Wholesale replace. |
| `editField` | `cerb_edit_field` | `key:` `script` or `policy`; `old:`; `new:` | `ok`. `old:` must match **exactly once** – zero or several matches is an error naming the count. Unfolds the editor and flashes the changed range. |
| `grepField` | `cerb_grep_field` | `key:` `script` or `policy`; `query:`; `limit:` (default 25) | JSON array of `{line, path, text}`. `line` is 1-based; `path` is the [KATA](/docs/kata/) key path of the row; `text` is trimmed and truncated to 200 characters. |
| `getDiff` | `cerb_get_diff` | `key:` `script` or `policy` (default `script`) | JSON `{key, tracked, hunks}` against the last save. See the caveat below. |
| `changeTab` | `cerb_change_tab` | `tab:` one of `run`, `policy`, `log`, `visualization`, `usage` | `ok` |
| `highlightLine` | `cerb_highlight_line` | `line:` (1-based) | `ok`. Always the script editor – it takes no `key:`. |
| `highlightKey` | `cerb_highlight_key` | `key:` `script` or `policy` (default `script`); `path:` | `ok`. Pairs with `grepField`, which reports the path to jump to – more robust than counting lines. |

Only the script editor tracks changes, so `getDiff` with `key: policy` always reports `tracked: false` and no hunks. It doesn't error; it simply never has anything to say.

## Automation Scripting Tester

`component: automation_scripting`. A single-editor host – no `key:` anywhere.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getEditorValue` | `cerb_get_script` | &nbsp; | The raw editor text. |
| `setEditorValue` | `cerb_set_script` | `value:` | `ok`. Wholesale replace. |
| `editField` | `cerb_edit_script` | `old:`; `new:` | `ok`, with the same exactly-once rule as above. |
| `grepField` | `cerb_grep_script` | `query:`; `limit:` (default 25) | JSON array of `{line, path, text}`. `path` is always empty here – only the [KATA](/docs/kata/) editor knows key paths. |
| `highlightLine` | `cerb_highlight_line` | `line:` (1-based) | `ok` |
| `getDiff` | `cerb_get_diff` | &nbsp; | JSON `{tracked, hunks}` – no `key`, since there's one editor. The baseline is the **last test run**, not the last save, so nothing is tracked until the script has been run once. |

## Command bar

`component: commandbar`. The one host with no document. It's app-wide rather than tied to a screen – it follows the worker from page to page – so it has no `getFields`, and everything it offers is one-way.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getPage` | `cerb_get_page` | &nbsp; | JSON `{page_uri, page_title, page_id, url, open_popups}`. `page_uri` is the path Cerb **routed** (e.g. `profiles/ticket/1234`), which is what the worker is looking at even when `url` says otherwise. |
| `openSearch` | `cerb_open_search` | `record_type:` a record type **alias** (`ticket`); `record_query:` an optional query to prefill | `ok`. Opens a search popup in front of the worker. An unknown alias opens nothing at all. |

Neither command reads anything back. `getPage` reports **where** the worker is, never what's on the screen, and `openSearch` shows them results without reporting what matched or how many. A chat here should say what it searched for and let the worker read the answer.

An agent that needs to know what a query would match reads it server-side on its next turn with a [data query](/docs/data-queries/) instead – and to write a query for a record type it hasn't seen before, it can look the type's fields up through its [`cerb` command line](/docs/agents/#the-cerb-command-line) rather than guessing at names.

The command bar's ordinary shortcuts are untouched by any of this. They stay [`interaction.worker`](/docs/automations/triggers/interaction.worker/) items and keep working beside a chat.

## Data Query Tester

`component: data_query`. Identical to the Automation Scripting Tester for all five of its commands – same params, same returns, same empty `path` – minus `getDiff`, which this editor doesn't track.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getEditorValue` | `cerb_get_query` | &nbsp; | The raw editor text. |
| `setEditorValue` | `cerb_set_query` | `value:` | `ok` |
| `editField` | `cerb_edit_query` | `old:`; `new:` | `ok` |
| `grepField` | `cerb_grep_query` | `query:`; `limit:` (default 25) | JSON array of `{line, path, text}` |
| `highlightLine` | `cerb_highlight_line` | `line:` (1-based) | `ok` |

## Icon Builder

`component: icon`. See the [Icon Builder](/docs/setup/developers/icon-builder/).

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getGeometry` | `cerb_get_geometry` | &nbsp; | The editor's raw SVG inner geometry. Not JSON. |
| `setGeometry` | `cerb_set_geometry` | `geometry:` | `ok`. Pushes a new revision onto the history ring, so it stays navigable with the history arrows, and repaints the preview. |
| `getIconGeometry` | `cerb_get_icon_geometry` | `name:` an icon name without its `cerb-icon-` prefix | That shipped glyph's geometry, or empty for an unknown name. This is how an agent looks up how a comparable icon is drawn. |

The Icon Builder also offers a **server-answered** tool, with no bridge command behind it:

| Tool | Params | Returns |
| --- | --- | --- |
| `cerb_list_icons` | `filter:` an optional substring to match names | Every icon name in the set, one per line |

Naming an existing icon is most of the work here, and `cerb_get_icon_geometry` can only look one up once you already know it exists – so the set has to be enumerable. Because it's answered in Cerb rather than by the editor, there's no `command:` for it: a [`uiCommand`](#uicommand) element can't reach it.

## Mail Reply

`component: mail_reply`. Available on the popup reply editor.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getFields` | `cerb_get_fields` | &nbsp; | JSON `{to, cc, bcc, subject, format, content}`. `format` is normalized on read to `markdown` or `plaintext`. |
| `setField` | `cerb_set_field` | `key:` one of `to`, `cc`, `bcc`, `subject`, `content`, `format`; `value:` | `ok` |

Setting `format:` to `markdown` switches the editor to Markdown; **any** other value means plaintext. There's no validation, so a typo silently selects plaintext rather than erroring.

## Mail Routing

`component: mail_routing`. Available on **both** editors that write a routing document: the standalone [mail routing rule](/docs/records/types/mail_routing_rule/) record, and a [group](/docs/groups/)'s **Mail: Incoming** tab.

One component rather than two, because the grammar, the schema, and the evaluator are shared. What differs is where in the pipeline the document runs, and `cerb_get_routing` reports that as a `scope` rather than there being a second host to tell apart.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getFields` | `cerb_get_routing` | &nbsp; | JSON `{scope, routing_kata}`, plus `{group_name, buckets}` when the scope is a group. `scope` is `rule` for a standalone rule that decides the **group**, or `group` for a group's own rules that decide the **bucket**. |
| `setField` | `cerb_set_routing` | `value:` the complete new document | `ok`. Replaces the whole document. The bridge's `key:` is pinned to `routing_kata` and never shown to the model. |
| `editField` | `cerb_edit_routing` | `old:`, `new:` | `ok`. Undo-safe search and replace. `old` must match **exactly once**; zero or several matches is an error telling the agent to expand the context. |
| `grepField` | `cerb_grep_routing` | `query:`; `limit:` optional, default 25 | JSON `[{line, path, text}]` – line numbers _and_ the KATA key path for each hit, so a location never has to be guessed. |
| `getDiff` | `cerb_get_diff` | &nbsp; | JSON `{tracked, hunks:[{status, line, endLine, added, removed}]}` with 1-based line numbers. `tracked: false` means there's no baseline to diff against. |
| `highlightLine` | `cerb_highlight_line` | `line:` 1-based | `ok`. Flashes a line to point the reader at the rule under discussion. |
| `highlightKey` | `cerb_highlight_key` | `path:` a colon-delimited KATA key path, e.g. `rule/billing:then` | `ok`. The robust sibling of `highlightLine` – it targets the path, so it survives edits that shift line numbers. |

Mail Routing also offers a **server-answered** tool, with no bridge command behind it:

| Tool | Params | Returns |
| --- | --- | --- |
| `cerb_test_routing` | `routing_kata:` the complete document to test; `placeholders:` the sample message, as a KATA document | Which rule matched, if any |

`placeholders:` recognizes `subject`, `body`, `recipients` (a list of To/Cc addresses), `sender_email`, `spam_score` (0.0 to 1.0), and `headers` (a map with lowercase keys).

It's answered in Cerb rather than by the editor for two reasons. The tester is an HTTP round trip and a bridge command has to answer synchronously, so it couldn't be one. And taking the document as an **argument** rather than reading the editor is what lets a candidate rule be tested _before_ it's written in. Being server-answered means there's no `command:` for it: a [`uiCommand`](#uicommand) element can't reach it.

Both editors mark changed lines in the gutter against the last save, whoever made the change, and **Save and continue** takes a fresh checkpoint. `cerb_get_diff` reads that same baseline, which is what lets an agent's account of an edit be checked against what it actually did.

## Worklist search fields

`component: worklist`. The one host that isn't an editor, and the only one that can _act_ rather than only read and write.

This isn't only the quick search bar above a [worklist](/docs/worklists/). The same search field is included in popups throughout the interface – the quick-search popup, record choosers – and each one carries the same agent and reports the same `component`. An item gated to `worklist` appears in all of them, and there's no state var that tells them apart.

| Command | Tool | Params | Returns |
| --- | --- | --- | --- |
| `getFields` | `cerb_get_fields` | &nbsp; | JSON `{query, record_type, record_context, view_id}`. `record_type` is the **alias** (`ticket`) – what [`data.query`](/docs/data-queries/) expects after `of:` – while `record_context` is the full context ID. |
| `setField` | `cerb_set_query` | `key:` **only** `query`; `value:` | `ok`, and focuses the field with the caret at the end. Any other key is an error. |
| `runSearch` | `cerb_run_search` | &nbsp; | `ok`. Asynchronous – it takes the same path as pressing `Enter`, so it can only report that the search **started**. |

Three commands rather than one per field is deliberate: the agent learns the field's shape from `getFields`, so state added later becomes a new key rather than a new command.

A search bar has exactly one writable field, so the `cerb_set_query` tool takes `value` alone – the bridge's `key:` is pinned to `query` and never shown to the model. A `uiCommand` element writing the same field still has to send it.

## Return values and failures

Conventions across the current hosts:

| Result | Notes |
| --- | --- |
| Readers | Return raw text, or JSON where the host has several fields |
| Writers | Return the literal string `ok` |
| Errors | Lead with `error:`, `unknown `, or `invalid ` |
| Unknown command | Returns an empty string on every host except worklist, which names it |

That error prefix is a **contract**, not a convention. An agent pane checks a mutating command's return against it to decide whether the editor now has unsaved changes, so a failure that doesn't lead with one of those three words leaves the editor falsely marked dirty. Anything you build a new host for should follow it.

**An empty string is ambiguous.** It means the command was unrecognized, _or_ the editor was genuinely empty, _or_ the host threw, _or_ there was no command bridge at all. Failures here are silent -- the interaction proceeds either way, and never reports why. Branch on it accordingly when it matters.

