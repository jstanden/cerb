---
id: "docs-automations-triggers-interaction-worker-elements-uicommand"
title: "uiCommand - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/uiCommand/"
summary: "This page documents the uiCommand element for worker interaction forms in Cerb. It runs a command in the editor an interaction was launched beside, filling its return variable synchronously when the form renders rather than submitting on its own. The page covers its command, params, and disabled keys, the fact that the element name is the return variable independent of the command that ran, what the host editors offer today and why those vocabularies differ between hosts, how to discover them at runtime through ui_capabilities instead of hard-coding them, why a submit element with is_automatic completes the round-trip unattended, and the important caution that errors and values are both plain strings that can't be told apart structurally."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **uiCommand** element runs a command in the **host editor** the interaction was launched beside, and puts the result in a placeholder.

This is what lets an interaction read and write the document a worker is looking at.

```
await:
  form:
    elements:
      uiCommand/current_value:
        command: getEditorValue
      submit:
        is_automatic@bool: yes
```

- [Availability](#availability)
- [Syntax](#syntax)
- [Submitting](#submitting)
- [Host commands](#host-commands)
- [Returned values](#returned-values)

# Availability

`uiCommand` is available on interaction triggers whose caller supplies a **command bridge**. Today that means [interaction.worker.agent](/docs/automations/triggers/interaction.worker.agent/), which extends [interaction.worker](/docs/automations/triggers/interaction.worker/) with this one extra element.

There's nothing agent-specific about the element itself – any caller that provides a bridge can use it. In practice every host that provides one today does so through an [agent pane](/docs/toolbars/interactions/agent.pane/), so an agent chat driving its editor is the case you'll actually meet.

You don't have to keep track of this yourself: saving an automation whose `await:form:` uses an element its trigger doesn't offer is rejected, and the error names the trigger you need.

`uiCommand` is **not** available in [interaction.website](/docs/automations/triggers/interaction.website/) portal interactions, which have no bridge concept.

# Syntax

A `uiCommand` element names a command for the host to run. The key you give the element is the **return variable**, and it's independent of which command ran:

```
uiCommand/prompt_document:
  command: getEditorValue
```

| Key | Notes |
| --- | --- |
| `command:` | The name of the host command to run |
| `params:` | Optional parameters passed to the command |
| `disabled:` | Render inert: emit an empty return variable without running anything |

### disabled:

A disabled element still emits its return variable – empty – so the field validates and `{{<name>}}` resolves to an empty string. It just doesn't run.

That's what lets one `await:form:` carry several commands and enable only the one that applies:

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

The `{{__tool.parameters}}` reads above are an [`on_tool:`](/docs/automations/triggers/llm.tool/) idiom for branching on what a model asked for. They're a convention of the agent host, not a requirement of this element.

# Submitting

A `uiCommand` fills its value when the form **renders**, by calling the host synchronously. It does not submit the form on its own.

So the form's own [submit](/docs/automations/triggers/interaction.worker/elements/submit/) is what posts the result. If you omit one entirely, a manual submit with a **Continue** button is appended for you – which works, but makes the worker click to complete a round-trip they had no part in.

For an unattended round-trip, include a submit that fires itself:

```
submit:
  is_automatic@bool: yes
```

Automatic submit is deliberately suppressed in two cases: when a form is re-rendered after a validation error (it would retrigger the same error indefinitely), and in the simulator (there's no runtime to fire it). The `uiCommand` element likewise renders nothing on a validation-error re-render.

# Host commands

There's no shared command vocabulary. Each host editor declares its own set, so the names below are **what those editors offer today**, not a specification:

| Host | Commands |
| --- | --- |
| Automation editor | `getFields`, `setField`, `editField`, `grepField`, `getDiff`, `changeTab`, `highlightLine`, `highlightKey` |
| Automation Scripting Tester | `getEditorValue`, `setEditorValue`, `editField`, `grepField`, `highlightLine`, `getDiff` |
| Command bar | `getPage`, `openSearch` |
| Data Query Tester | `getEditorValue`, `setEditorValue`, `editField`, `grepField`, `highlightLine` |
| Icon Builder | `getGeometry`, `setGeometry`, `getIconGeometry` |
| Mail Reply | `getFields`, `setField` |
| Worklist search field | `getFields`, `setField`, `runSearch` |

Two shapes recur. A host that edits **one document** offers `getEditorValue` and `setEditorValue`; a host that edits a **record of several fields** offers `getFields` and `setField`. The three code-editor hosts also converge on `editField`, `grepField`, `highlightLine`, and `getDiff`, because all three delegate to the same underlying editor helpers.

A [worklist](/docs/worklists/) search bar is the one host that isn't an editor, and the only one that can _act_ rather than only read and write: `getFields` returns its current query and record type as JSON, `setField` accepts the single key `query`, and `runSearch` runs whatever is in the field. Because a search is an asynchronous refresh while the bridge fills synchronously, `runSearch` reports only that the search started – the agent reads results server-side on its next turn.

The command bar is the other non-editor host, and the only one that acts on the app rather than on a document: `getPage` reports which page Cerb routed, and `openSearch` opens a prefilled search popup for a record type. Both are one-way – neither reports what's on the worker's screen, or what a search matched.

None of that is guaranteed, though. Don't carry a command name from one host to another and expect it to work – an unrecognized command isn't an error; it simply returns an empty string.

**A host's command names are not the agent's tool names.** The names above are what `command:` accepts. The tools a model calls are named separately -- a chat offering `get_fields`, `set_query`, and `run_search` is naming tools, and the `uiCommand` elements behind them still have to say `getFields`, `setField`, and `runSearch`. Putting a tool name in `command:` returns an empty string, silently.

**You may not need this element at all.** An agent chat on the [interaction.worker.agent](/docs/automations/triggers/interaction.worker.agent/#tools) trigger is handed its host's commands as tools automatically, dispatched for it. Reach for `uiCommand` when you want to run a command yourself -- at a moment of your choosing, or one the model never asked for.

### ui\_capabilities

Rather than hard-coding a host's vocabulary, an interaction launched from an [agent pane](/docs/toolbars/interactions/agent.pane/) receives a `ui_capabilities` input listing the commands **that** host offers. Reading it is what makes one automation portable across editors.

# Returned values

The return variable is always a **string**.

Conventions across the current hosts – conventions only, nothing enforces them:

| Result | Notes |
| --- | --- |
| Readers | Return the raw text, e.g. the document contents |
| `getFields`, `getDiff` | Return JSON, which the automation has to decode |
| Writers | Return the literal string `ok` |
| Errors | Return a readable message prefixed `error:` |
| Unknown command | Returns an empty string |

**A failure looks exactly like a value.** Errors aren't structurally distinct -- they arrive in the return variable as ordinary strings, so an automation can only recognize one by matching the `error:` prefix. An empty string is especially ambiguous: it means the command was unrecognized, _or_ the editor was genuinely empty, _or_ no command bridge was present. Branch accordingly when it matters.

If a form renders without a command bridge, every `uiCommand` on it quietly posts an empty string. Nothing errors and nothing hangs.

