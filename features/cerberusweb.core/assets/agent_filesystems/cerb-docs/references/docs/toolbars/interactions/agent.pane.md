---
id: "docs-toolbars-interactions-agent-pane"
title: "agent.pane"
url: "https://cerb.ai/docs/toolbars/interactions/agent.pane/"
summary: "This page documents the `agent.pane` toolbar in Cerb. It controls which agent chats an agent pane offers, and which hosts each chat appears in. The page covers its placeholders, the component placeholder used to gate items per host, its interaction inputs, how an interaction can refresh the toolbar when it finishes, the six hosts that provide an agent pane and their component values, the interaction.worker.agent trigger a chat needs to drive its host through uiCommand, the agent.pane caller policy an item requires, and the extra worklist placeholders a search bar host provides."
tags: ["docs"]
---
The `agent.pane` [toolbar](/docs/toolbars/) controls which [agent](/docs/agents/) chats an editor's agent pane offers.

An agent pane is a collapsible chat that sits beside an editor. This toolbar decides what it offers, and which editors each item appears in.

It ships with no sections, so items are authored per environment.

https://www.youtube.com/embed/TPSTDjX6Unw

# Placeholders

| Placeholder | Notes |
| --- | --- |
| `component` | The host the pane is mounted on: `automation`, `bot_scripting`, `commandbar`, `data_query`, `icon`, `mail_reply`, or `worklist` |
| `worker_*` | The current [worker](/docs/records/types/worker/) |
| `worklist_*` | On `worklist` hosts only – see [Worklist search fields](#worklist-search-fields) |

Use `component` to show an item only in the hosts where it makes sense:

```
interaction/describe_icon:
  label: Describe this icon
  hidden@bool: {{component != 'icon'}}
  uri: cerb:automation:example.agent.icon.describe
```

# Interaction inputs

| Input | Notes |
| --- | --- |
| `component` | The host the pane is mounted on |
| `worker_*` | The current worker |
| `worklist_*` | On `worklist` hosts only – see [Worklist search fields](#worklist-search-fields) |

# Refreshing the toolbar

An interaction can reload the toolbar when it completes:

```
return:
  after:
    refresh_toolbar@bool: yes
```

# Hosts

| Host | `component` |
| --- | --- |
| Automation editor | `automation` |
| Automation Scripting Tester | `bot_scripting` |
| Command bar | `commandbar` |
| Data Query Tester | `data_query` |
| [Icon Builder](/docs/setup/developers/icon-builder/) | `icon` |
| Mail Reply | `mail_reply` |
| [Worklist](/docs/worklists/) search fields | `worklist` |

An agent chat gets its host's commands as tools automatically, and can drive the host directly with the [`uiCommand`](/docs/automations/triggers/interaction.worker/elements/uiCommand/) element. Both come from the [interaction.worker.agent](/docs/automations/triggers/interaction.worker.agent/) trigger, so build a chat that drives its host on that trigger rather than [interaction.worker](/docs/automations/triggers/interaction.worker/).

An item also needs a [policy](/docs/automations/#policies) allowing the `agent.pane` caller, or its tile never appears in the pane.

## Command bar

The command bar is the one host that isn't tied to a screen at all. Its menu is the [`global.menu`](/docs/toolbars/interactions/global.menu/) toolbar merged with the items on this toolbar gated to `commandbar`, so a chat that follows a worker around Cerb is authored here, alongside every editor chat, rather than in a second place.

Gate an item to the command bar with:

```
hidden@bool: {{component != 'commandbar'}}
```

A chat there can read which page the worker is on and open a prefilled search popup for any record type. Both are one-way: the command bar can put something in front of a worker, but nothing comes back through the bridge – an agent knows where they are, never what's on the screen, and never what a search it opened matched.

The command bar's ordinary non-agentic shortcuts are unaffected. Those stay [`interaction.worker`](/docs/automations/triggers/interaction.worker/) items and keep working alongside a chat.

## Worklist search fields

A search field's agent is the one host that isn't an editor. This covers more than the search bar above a [worklist](/docs/worklists/): the same field is included in popups throughout the interface – the quick-search popup, record choosers – and every one carries the same agent under the same `component`, with no state var distinguishing them.

Items on a `worklist` host additionally receive:

| Placeholder | Notes |
| --- | --- |
| `worklist_id` | The worklist being searched |
| `worklist_record_type` | The record type **alias** (`ticket`) – what [`data.query`](/docs/data-queries/) expects after `of:` |
| `worklist_record_context` | The record type's context ID |
| `worklist_query` | The current search query |
| `worklist_query_required` | The worklist's required query, which a search can't escape |
| `worklist_page` | The current page |
| `worklist_limit` | Rows per page |

Gate an item to search fields with:

```
hidden@bool: {{component != 'worklist'}}
```
