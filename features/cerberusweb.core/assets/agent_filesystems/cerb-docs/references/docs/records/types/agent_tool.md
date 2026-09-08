---
id: "docs-records-types-agenttool"
title: "Agent Tool Records"
url: "https://cerb.ai/docs/records/types/agent_tool/"
summary: "An agent tool is a record describing one tool an AI agent can call -- the name the model calls, the description it reads, the icon and wording its transcript shows, and the automation that does the work. The arguments the model may send come from that automation's own inputs block, which is the schema the model is shown, so a tool is self-documenting and there is one place to change what it takes. A tool is defined once and then referenced by name from an agent or from a chat's tools block, where the reference may override the presentation but never the schema. This page documents tool names and the reserved cerb prefix, the Available, Unlisted and Disabled statuses, transcript labels and icons, tools with no automation answered by the calling script, when a conversation's tool set is settled, and the Records API fields and search filters available on agent tool records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Agent Tool |
| **Name (plural):** | Agent Tools |
| **Alias (uri):** | agent\_tool |
| **Identifier (ID):** | cerb.contexts.agent.tool |

- [What the model sees](#what-the-model-sees)
- [Naming a tool](#naming-a-tool)
- [Transcript presentation](#transcript-presentation)
- [Status](#status)
- [Giving a tool to an agent](#giving-a-tool-to-an-agent)
- [A tool with no automation](#a-tool-with-no-automation)
- [When a tool set is settled](#when-a-tool-set-is-settled)
- [Records API](#records-api)
- [Search Query Fields](#search-query-fields)

An **agent tool** is one tool an [AI agent](/docs/agents/) can call. A tool is defined once as a record and then added to any agent, rather than restated in every script that needs it.

The record wraps an [`agent.tool`](/docs/automations/triggers/agent.tool/) [automation](/docs/automations/) with everything the automation itself has nowhere to say. The automation knows how to do the work; the record says what the model should call it, and what the conversation shows while it runs and after it finishes.

### What the model sees

Three things reach the model, and only three:

| What | Where it comes from |
| --- | --- |
| The name | The record's `name` – the function name the model calls |
| The description | The record's `description`, or the reference's own `description:` when it overrides it |
| The arguments | The answering automation's `inputs:` block |

The arguments are **not** restated on the record. An [`agent.tool`](/docs/automations/triggers/agent.tool/) automation already declares its `inputs:`, and each input already carries a description, whether it's required, its allowed values, and a default – so that block _is_ the schema the model is shown. Declaring the arguments a second time on the record would mean two places to change and one of them silently wrong.

Every input is advertised to the model as a string regardless of its declared type, because a provider takes JSON scalars and a `record/` input is a name or an id to the model either way.

### Naming a tool

A tool's `name` is the function name the model calls, so it has to be spelled the way a function name is:

- It must start with a lowercase letter, and may contain only lowercase letters, numbers, and underscores.
- It must be unique across every agent tool.
- It may not start with `cerb_`. That namespace is reserved for [Cerb's own built-in tools](/docs/automations/triggers/interaction.worker.agent/#hosts), so a tool you create can never shadow one of ours, whatever you call it.
- It may not be `agent_terminal`, `automation`, `tool`, `ui_command`, or `ui_server`, which are the key prefixes the `tools:` grammar already uses.

The `label` is the human name shown in worklists and pickers; it falls back to the `name` when it's blank. The model never reads it.

**A tool holds no credentials.** There is no key or secret field on the record. An API key for whatever the tool calls belongs to the [automation](/docs/automations/triggers/agent.tool/) behind it, or to a [connected account](/docs/records/types/connected_account/) that automation reads.

### Transcript presentation

A conversation shows a tool call twice – once while it's running and once after it returns – and the record supplies both, plus the glyph beside them:

| Field | Shown | Example |
| --- | --- | --- |
| `label_active` | While the call is running | `Searching the web...` |
| `label_summary` | Once the call returns | `Searched the web` |
| `icon` | The glyph beside both, defaulting to `wrench` when blank | `search` |

The `icon` must be a [cerb-icons](/docs/developers/icons/) name, or saving is refused with _"must be a cerb-icons name."_ Leave it blank rather than inventing one.

Either label can quote the call's own arguments, so a label reads back what the agent actually asked for rather than a fixed phrase:

```
label_summary: Searched for {{query}}
```

The model never sees either label or the icon. They exist for the person reading the transcript.

Either label may be omitted. A blank one falls back to the other, and if both are blank the transcript names **the tool itself**, humanized so it reads as words rather than an identifier – `web_search` becomes "Running web search" while the call runs and "Ran web search for 1.2s" once it returns, with the duration included whenever one was measured. The bare verbs "Working" and "Worked" appear only for a tool with no name at all.

So labels are worth writing, but leaving them blank is not a failure -- it costs you the phrasing, not the meaning. A reader still learns which tool ran and how long it took.

### Status

A tool is in one of three states:

| Status | Offered in pickers | Runs when an agent names it |
| --- | --- | --- |
| **Available** | Yes | Yes |
| **Unlisted** | No | Yes |
| **Disabled** | No | No |

**Unlisted** is the state for a tool you want reachable by name without it turning up wherever something asks for "a tool" – a helper another tool composes with, or one still being tested.

### Giving a tool to an agent

An agent references a tool by its record name, either in the **AI** tab of its [worker](/docs/records/types/worker/) record or in a chat's `tools:` block:

```
tools:
  web_search:
  lookup_order:
    labels:
      active: Checking the order...
      summary: Checked the order
```

The entry under a reference may override the presentation the record supplies, and nothing else:

| Key | Notes |
| --- | --- |
| `description:` | The description the model reads, replacing the record's |
| `icon:` | The transcript glyph, replacing the record's |
| `labels:` | `active:` and `summary:`, each replacing the record's |
| `disabled@bool:` | Switch the tool off for this agent without removing the reference |

What the tool **takes** is never overridden there. The schema always comes from the answering automation, so a reference can't quietly offer the model a different contract than the one the script validates.

To **force** an argument the model shouldn't get to choose, rewrite `__tool.parameters` in the calling script's `on_tool:` branch, which runs before the tool does.

### A tool with no automation

The automation is optional. Leave a tool's automation empty and the conversation answers the call from its own `on_tool:` branch with `tool.return:`.

That's how a tool asks for approval before it runs, collects a missing argument with a form, or hands off to another interaction – the cases only a script can answer.

### When a tool set is settled

A conversation's tools are resolved when it **starts**, and frozen for its lifetime. The tool set sits in the prompt prefix the provider caches, so changing it mid-conversation would throw that cache away on every remaining turn.

What follows from that:

- Editing a tool reaches **new** conversations. One already under way keeps what it started with.
- Switching a tool off refuses new calls to it rather than making it disappear mid-conversation. The model sees the tool and is told no, which is also the shape a guardrail denial takes.
- A tool that's disabled, or whose record is missing, when a conversation _starts_ is never admitted at all – so the model is never shown something it would only be refused.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | The description the model reads to decide when to call this tool |
| &nbsp; | `icon` | [text](/docs/records/fields/types/text/) | The transcript glyph; must be a [cerb-icons](/docs/developers/icons/) name. Defaults to `wrench` |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this tool |
| &nbsp; | `label` | [text](/docs/records/fields/types/text/) | The human name shown in worklists and pickers; falls back to `name` |
| &nbsp; | `label_active` | [text](/docs/records/fields/types/text/) | What a transcript shows while the call is running |
| &nbsp; | `label_summary` | [text](/docs/records/fields/types/text/) | What a transcript shows once the call returns |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The function name the model calls; lowercase letters, numbers, and underscores, starting with a letter |
| &nbsp; | `status` | [text](/docs/records/fields/types/text/) | `available`, `unlisted`, or `disabled` |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `uri` | [text](/docs/records/fields/types/text/) | The `cerb:automation:<name>` URI of the [`agent.tool`](/docs/automations/triggers/agent.tool/) automation that answers this tool |

### Search Query Fields

These [filters](/docs/search/#filters) are available in agent tool [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created` | date | When the record was created |
| `description` | text | The model-facing description (partial match) |
| `fieldset` | virtual | Filter by [custom fieldset](/docs/records/types/custom_fieldset/) |
| `id` | number | The record ID |
| `label` | text | The human name (partial match) |
| `name` | text | The function name the model calls (partial match) |
| `status` | text | `available`, `unlisted`, or `disabled` |
| `status.id` | number | The status as its stored number |
| `updated` | date | When the record was last modified |
| `uri` | text | The answering automation's URI (partial match) |
| `watchers` | virtual | Filter by [watchers](/docs/watchers/) |

