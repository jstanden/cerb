---
id: "docs-agents"
title: "AI Agents"
url: "https://cerb.ai/docs/agents/"
summary: "This page explains how AI agents work in Cerb. An agent is an ordinary worker record flagged as AI, so it can own tickets, be @mentioned, and hold API credentials like any other worker. Agent model records configure which LLM models are available, where their credentials come from, and what each model can do -- vision and extended thinking as capabilities, and intelligence, speed, privacy, and cost as ratings -- so an automation can search for the model the work needs instead of naming one and staying tied to your installation. Agent filesystems give agents named volumes of files they can read and write through a familiar command set, including volumes that ship with Cerb. This page covers all of these pieces, the cerb command line an agent can query its own installation through, and how the llm.agent, llm.chat, and llm.router automation commands tie them together."
tags: ["docs"]
---
- [Introduction](#introduction)
- [The pieces](#the-pieces)
  - [Agent models](#agent-models)
  - [Model pools](#model-pools)
  - [Agent filesystems](#agent-filesystems)
    - [Filesystem commands](#filesystem-commands)
      - [Finding the right words](#finding-the-right-words)

    - [The cerb command line](#the-cerb-command-line)
      - [cerb records](#cerb-records)
      - [cerb platform](#cerb-platform)
      - [cerb code](#cerb-code)

  - [Agent tools](#agent-tools)

- [Where an agent runs](#where-an-agent-runs)
  - [The default chat](#the-default-chat)
  - [Everywhere, and per surface](#everywhere-and-per-surface)
    - [Restricting which models an agent may use](#restricting-which-models-an-agent-may-use)

  - [Authoring the configuration as KATA](#authoring-the-configuration-as-kata)

- [Automations](#automations)
- [Providers](#providers)
- [Long conversations](#long-conversations)

# Introduction

An **AI agent** in Cerb is an ordinary [worker](/docs/workers/) record with an `is_ai` flag, rather than a separate record type. That single decision explains most of how agents behave: an agent can own a [ticket](/docs/records/types/ticket/), be `@mentioned` in a comment, belong to a [group](/docs/groups/), appear in a worklist, and hold [API](/docs/api/) credentials, because those capabilities already belong to workers.

An AI worker can never sign in. Interactive logins and SSO are refused outright, and an AI worker doesn't require an email address.

**None of this is required.** AI in Cerb is additive rather than a dependency: with no [agent model](/docs/records/types/agent_model/) configured, there's nothing to switch off and nothing to ignore – the interface is simply the one you already had. Link a model and the features that depend on one become available. Everything else in Cerb works the same either way.

https://www.youtube.com/embed/dOjF7-nofbA

To list only AI workers, search workers with `isAi:y`. You can also give that query its own entry in the **Search** menu with a [search facet](/docs/search/).

# The pieces

Four record types work together, and you don't need all of them to get started:

| Record type | Purpose |
| --- | --- |
| [Agent Model](/docs/records/types/agent_model/) | A model you can use: its provider, model ID, credentials, context window, capabilities, and ratings |
| [Worker](/docs/records/types/worker/) (`is_ai`) | The agent's identity – name, image, ownership, `@mention` handle |
| [Agent Filesystem](/docs/records/types/agent_filesystem/) | A named volume of files an agent can read and write |
| [Agent Tool](/docs/records/types/agent_tool/) | One tool an agent can call, and the [automation](/docs/automations/) that does the work |

## Agent models

An [agent model](/docs/records/types/agent_model/) record holds everything needed to call one model: the provider, the model ID, an optional API endpoint URL, an encrypted [connected account](/docs/records/types/connected_account/) for credentials, the size of its context window, a block of provider-specific parameters, and a description of what the model can do and how it compares to the others you've configured.

https://www.youtube.com/embed/vF-F3vkKoCI

You don't need to know model IDs by heart. Once the provider and credentials are set, the editor asks the provider which models that key can actually use and offers them as a list. That live list is also the only accurate one for a self-hosted OpenAI-compatible endpoint – llama.cpp, LM Studio, vLLM, or a local Ollama – where a hardcoded list can't know what's loaded.

Once a model is configured, [automations](/docs/automations/) reference it **by name** instead of repeating a provider block inline, so credentials and tuning live in one place:

```
llm.chat:
  output: results
  inputs:
    model: haiku
    messages:
      0:
        role: user
        content: Summarize this conversation in one sentence.
```

## Model pools

Naming a model directly works, but it ties the automation to your installation. A **pool** is a [search](/docs/search/) over agent models rather than a list of names, so a script asks for what the work _needs_ and gets whatever qualifies:

```
hasVision:y intelligence:>=advanced privacy:>=zdr
```

Adding a model puts it in every pool it qualifies for with no edit anywhere, and a pool can ask about anything a model records – its [capabilities](/docs/records/types/agent_model/#capabilities), its [ratings](/docs/records/types/agent_model/#ratings), its context window, its provider, or your own custom fields.

**Doing nothing is the simplest configuration.** An automation that names no model falls through to every [available](/docs/records/types/agent_model/#availability) model, in the [`priority`](/docs/records/types/agent_model/#priority) order set on the records – so one field on one record decides what the whole installation reaches for first, and changing models later is one edit in one place.

A search can only ever **narrow** a pool. Unlisted and disabled models are excluded before a caller's own search runs, so an automation can be handed this vocabulary without being able to widen what an admin allowed.

When you need the model list _as data_ – to round-robin it, weight it by cost, skip one that's over budget, or feed two commands from one resolution – use the [`llm.router:`](/docs/automations/commands/llm.router/) command:

```
llm.router:
  output: routed
  inputs:
    models_query/work: hasVision:y

llm.agent:
  inputs:
    model@key: routed:models
    messages:
      0:
        role: user
        content: {{prompt}}
```

**An agent is identity, not model policy.** Naming an [`agent:`](/docs/automations/commands/llm.agent/#agent) says who the work is attributed to, whose memory it uses, and which credentials it holds -- never what it may run. The same agent can do cheap work and expensive work, because which models a turn may use depends on the work rather than on whose name is on it.

## Agent filesystems

An [agent filesystem](/docs/records/types/agent_filesystem/) is a named volume of files that both agents and workers can read and write. Each volume tracks its file count and total size, and a ZIP archive can be bulk-imported in the background.

https://www.youtube.com/embed/LAy--sYJQYk

An automation makes volumes available to an agent by listing them under `mounts:`. Doing so enables a single `agent_terminal` tool covering every mounted volume – because there's one tool regardless of how many volumes are mounted, adding a mount doesn't change the tool schema or invalidate the cached prompt prefix. That one tool is a command line as well as a filesystem; see [the cerb command line](#the-cerb-command-line).

```
llm.agent:
  inputs:
    agent: @researcher
    mounts:
      handbook:
        mode: read-only
      scratch:
        mode: read-write
        create@bool: yes
```

| Key | Notes |
| --- | --- |
| `filesystem:` | The source volume, if it differs from the mountpoint key |
| `mode:` | `read-only` (default) or `read-write` |
| `at:` | An explicit mountpoint path, overriding the key |
| `create@bool:` | Provision the volume by name on first mount |

An empty `mounts:` block is a valid configuration: it mounts no volumes, but still gives the agent a `/tmp` scratch area.

### Filesystem commands

Agents and workers use the same command set, so what an agent can do and what you see in the terminal can't drift apart. These are the file verbs; the terminal also hosts a [`cerb` command line](#the-cerb-command-line) for asking Cerb about itself.

| Command | Notes |
| --- | --- |
| `ls` | List one directory |
| `find` | Recursive search by name, type, extension, or depth |
| `cd` | Change directory |
| `read` | Read a file, optionally by offset and limit |
| `search` | Search file contents, or count which words are worth searching for |
| `write` | Write a file (read-write mounts only) |
| `append` | Append to a file (read-write mounts only) |
| `edit` | Replace an exact snippet, which must match once (read-write mounts only) |
| `copy` | Copy one file |
| `rm` | Delete a file (read-write mounts only) |
| `pwd` | Print the working directory |
| `help` | Show usage for all commands or one command |

There is no `move` command. `copy` the file, then `rm` the source.

Command output can be piped through a Twig filter chain, and output too large to return inline is spooled to `/tmp` and referenced by path.

#### Finding the right words

`search` is **AND** – every word has to appear in the same file – so throwing a pile of near-synonyms at a volume reliably matches nothing, and a bare "no matches" gives no hint of _which_ word emptied it.

`search --terms` answers the other question: how many files contain **each** word, counted separately. A dozen candidate words go out in one call, and the counts say which one to keep.

```
search --terms refund reimbursement chargeback credit
```

Prefix a word with `+` to require it and count the others alongside it, which collapses the numbers fast and shows what actually co-occurs:

```
search --terms +refund policy window escalation
```

A [wildcard or stem](/docs/search/#search-indexes) term lists the concrete words it covers, which is how you learn the vocabulary a corpus actually uses instead of guessing at it – `auto*` reports `automations (476) automation (374) automatically (228)` and so on.

A `search` that matches nothing falls back to this table on its own, naming the word that matched nothing and the ones worth keeping, and keeping any `+` already in the query so the next step of the loop doesn't throw away the term you just established.

Workers can browse and edit the same volumes from **Setup » Developers » Agent Filesystem Terminal**. Mount a volume there and it comes up read-only; switch it to `rw` to allow the write commands.

 

### The cerb command line

The terminal also hosts a `cerb` command line for asking Cerb about itself.

Ask an agent for "Kina's open tickets" and it has to know that `ticket` is a record type here, that `status` and `owner.at` are things you can filter by, and that your custom fields exist at all. None of that is in a model's training data, and none of it is a file anyone would think to import – so without this an agent guesses, and a wrong guess looks exactly like a working query until it returns nothing.

The command line is organized into **namespaces**, and an automation opts in by naming the ones it allows under `terminal:`:

```
llm.agent:
  inputs:
    terminal:
      cerb:
        records:
        platform:
        code:
```

| Namespace | Answers |
| --- | --- |
| [`records`](#cerb-records) | What record types, search filters, and writable fields exist here |
| [`platform`](#cerb-platform) | What plugins are installed, and what extensions are on each point |
| [`code`](#cerb-code) | Whether a block of [KATA](/docs/kata/) or JSON would actually save |

A namespace that isn't named isn't reachable. Writing `terminal:` by itself is a complete configuration – the agent gets the command line, a `/tmp` scratch area, and the pipeline, without mounting any volume.

Every sub-command accepts `--format md|json` – a compact table by default, JSON when something needs to parse it – and output pipes like any other terminal command, so an agent can narrow a long list without spending the whole thing on context. `cerb help` shows usage for the namespaces this agent has.

#### cerb records

| Command | Notes |
| --- | --- |
| `cerb records types` | Every record type in this installation, custom record types included |
| `cerb records filters <type>` | The keys you can **search** that type by, for building a query |
| `cerb records fields <type>` | The keys you can **write**, including custom fields and fieldsets |

`types` accepts `--filter <text>` to match an alias or name, and `--custom` or `--no-custom` to narrow by origin. `filters` and `fields` take several types at once (`cerb records fields ticket,worker`).

```
cerb records filters ticket | filters|filter(r => "owner" in r.key)
```

The answers come from the same reflection that powers **Setup » Records**, so what an agent is told and what an admin sees can't disagree.

#### cerb platform

The documentation describes the plugins that **exist**. It can't say which of them are in your installation, which are switched off, or what a third party added.

| Command | Notes |
| --- | --- |
| `cerb platform version` | What version of Cerb this is, where it's hosted, and what PHP and database it runs on |
| `cerb platform plugins` | Every plugin with its ID, version, and whether it's enabled |
| `cerb platform points` | Every [extension point](/docs/plugins/extensions/), with how many extensions this installation has on it |
| `cerb platform extensions <point>` | The extensions on one point, by fully qualified ID |

`version` is what an agent should ask before assuming a feature exists. It reports five rows: Cerb's version and build, the deployment, the Devblocks platform build, PHP with its SAPI, and the database server's own version string, which names the flavor too.

```
cerb platform version
```

The **deployment** row reads `Cerb Cloud` when Cerb hosts the install, naming its subdomain, and `Self-Hosted` otherwise.

**Version and build are not the same thing.** The `version` is the exact string a [workflow](/docs/workflows/)'s `cerb_version: '>=12.0'` gate and a [package](/docs/packages/)'s `requires.cerb_version` compare against. The `build` is a date serial that moves with every release and is what the updater compares -- it isn't a version and doesn't sort against one.

Disabled plugins are listed and marked rather than omitted, because "the JIRA plugin is here but switched off" and "there is no JIRA integration" are different answers and only one of them is yours to fix.

Listing the extensions on a point isn't only for plugin development. An extension ID is a value stored on ordinary records, so it's a value you **search** by – an automation's `trigger:` filter takes `cerb.trigger.interaction.worker`, not the short `interaction.worker` the trigger calls itself. This is how an agent gets from "automations that run when a worker starts an interaction" to a query that returns them, and the same holds for queue consumers, [search indexes](/docs/records/types/search_index/), connected services, and card widgets.

```
cerb platform extensions cerb.automation.trigger --filter interaction
```

`--filter` narrows a long point. Extension points are enumerated from the live registry rather than from a hand-maintained list, so the command and the [`platform.extensions`](/docs/data-queries/platform/extensions/) [data query](/docs/data-queries/) can't disagree about what exists.

#### cerb code

A model writing an [automation](/docs/automations/) has no feedback loop. It writes the whole thing, saves, and learns from a save error what one line got wrong – then rewrites the whole thing, because it can't tell which parts were fine. That's several turns and a lot of guessing for what a linter answers in one.

| Command | Notes |
| --- | --- |
| `cerb code kata lint <path>` | Check a [KATA](/docs/kata/) document, optionally against a schema |
| `cerb code kata schemas` | The schemas this installation can validate against |
| `cerb code json lint <path>` | Check a JSON document, reporting where it stops being JSON |
| `cerb code json format <path>` | Print a document in Cerb's own JSON formatting |

The loop is: write the draft to `/tmp`, lint it, fix what it names, and repeat until it passes.

```
cerb code kata lint /tmp/draft.kata --schema automation
```

Every problem is reported with its line number and the line itself. `--schema` validates against the **same** schemas Cerb enforces when the record is saved – automations, automation policies, sheets, toolbars, [workflows](/docs/workflows/), charts, and the rest – so passing here means the save won't be rejected for that reason. Use `--payload` to check something that hasn't been written to a file yet.

`cerb code json lint` says _where_ a document stops being JSON and why: a trailing comma before a brace, a single-quoted key, a missing comma between two values, `True` where `true` belongs. PHP's own answer to all of those is the words "Syntax error" with no position, which is exactly what makes a model rewrite the file instead of the line.

A document that fails a lint is not a failed _command_ -- the check ran and answered, so it succeeds with its findings as rows. That's what lets the output be piped.

A superuser can run any of these commands without an agent from **Setup » Developers » Agent Filesystem Terminal**, where a **Commands** panel turns each namespace on or off. With none enabled, `cerb` doesn't exist there at all. The **Payload** box there holds the document being checked when a `cerb code` command passes `--payload`.

 

## Agent tools

An [agent tool](/docs/records/types/agent_tool/) is one tool an agent can call, defined once as a record and then added to any agent rather than restated in every script that needs it.

The record says what the model should call the tool, the description it reads, and the icon and wording its transcript shows. The [`agent.tool`](/docs/automations/triggers/agent.tool/) [automation](/docs/automations/) behind it does the work, and **that automation's own `inputs:` block is the schema the model is shown** – so the arguments are declared in exactly one place.

An agent references a tool by its record name:

```
tools:
  search_handbook:
  lookup_order:
    labels:
      active: Checking the order...
      summary: Checked the order
```

The entry under a reference may override the record's `description:`, `icon:`, and `labels:`, or switch the tool off with `disabled@bool: yes`. What the tool **takes** is never overridden there.

A conversation's tools are resolved when it starts and frozen for its lifetime, because the tool set sits in the prompt prefix the provider caches. Editing a tool reaches new conversations; one already under way keeps what it started with.

Alongside the tools you define, an agent is given tools automatically for **where it's running** – an agent beside the [automation](/docs/automations/) editor can read and edit the script in front of it, one in the command bar can say which page you're on. Those are named with a reserved `cerb_` prefix, so a tool you create can never shadow one. See [the hosts](/docs/automations/triggers/interaction.worker.agent/#hosts) for what each surface offers.

# Where an agent runs

Where an agent's chat appears is part of the **agent**, not a separate list to keep in sync with it.

Every [agent pane](/docs/toolbars/interactions/agent.pane/) and the command bar lists the agents enabled for _that_ surface, each with its own name and picture and a line saying what it's for. An agent is enabled somewhere by having a block for that surface in its own configuration, edited in the **AI** tab of its [worker](/docs/records/types/worker/) record.

## The default chat

Cerb ships the chat an agent runs when it names none of its own. An agent with no `automation:` set runs `cerb.ai.agent.chat`, so you don't have to write a conversation before you can have one.

It adapts to where it opens: the surface contributes that editor's commands as tools and its orientation as a system prompt at runtime, so one script drives whichever screen it sits beside. It mounts Cerb's own [documentation and skills](/docs/records/types/agent_filesystem/#the-volumes-cerb-ships) read-only, and enables the [`cerb` command line](#the-cerb-command-line) over this installation's own record types, fields, and filters, so it looks a name up rather than recalling one that may not exist here.

The same workflow also ships an agent to run it: an AI worker named **Cerb**, enabled on every surface. So the default chat is reachable from every editor pane and from the command bar on a new installation, without authoring anything.

The shipped agent deliberately names no model. Its `models_query:` is `sort:priority,-intelligence`, which orders whatever [agent models](/docs/records/types/agent_model/) you have rather than naming one that may not exist here – so it works with a single model configured, and follows your priorities once there are several. It mounts the `cerb-docs` and `cerb-agents` volumes and enables the `cerb platform` and `cerb records` [command line](#the-cerb-command-line) namespaces.

That leaves **one** thing to do before a chat can answer:

1. An [agent model](/docs/records/types/agent_model/) under **Search » Agent Models**. Without one the chat opens and every turn reports an error.

Beyond that, remember that enablement is per surface, so an agent **you** create appears nowhere until you give it a block for a surface. **A surface with no agent enabled on it hides its agent toggle entirely** rather than showing an empty pane.

The chat and the agent both arrive as the `cerb.ai.agent` [workflow](/docs/workflows/), installed on new installations and once on upgrade. Disable or delete it and it **stays** gone -- it's never re-enabled on a later update. Its script is replaced on each update, so edit a copy rather than the original.

To build your own, use **Automations » Build » AI Agent Chat**, which generates a copy you own, then point an agent's `automation:` at it.

## Everywhere, and per surface

An agent's configuration has one scope for **Everywhere** and one per surface it can run on.

Everywhere is what the agent brings no matter where it's running. A surface **adds** to that rather than replacing it – which is why the scope is called Everywhere rather than Defaults, since there's nothing there to override:

| Setting | How a surface combines with Everywhere |
| --- | --- |
| Instructions (`system_prompt:`) | The surface's text is appended to Everywhere's |
| [Model query](#restricting-which-models-an-agent-may-use) (`models_query:`) | The surface's value replaces it when not blank |
| The chat it runs (`automation:`) | The surface's value replaces it when not blank |
| Filesystems (`mounts:`) | Union; the surface overrides only the mounts it names |
| Tools (`tools:`) | Union; the surface overrides only the tools it names |
| Command line (`terminal:`) | Union, and **additive only** |

The command line is grants only. A surface can hand the agent a `cerb` namespace it doesn't have everywhere, and can never take one away – so it's a picker of things to add rather than a row of checkboxes that would suggest unticking one could revoke it.

### Restricting which models an agent may use

`models_query:` is how you scope an agent to a subset of your [agent models](/docs/records/types/agent_model/) without editing the [automation](/docs/automations/) it runs. It's an ordinary [search query](/docs/search/) over agent model records, in the same syntax a worklist search uses:

```
models_query: hasVision:y cost:[1,3]
```

Every model matching the query becomes the pool the agent's turns draw from, and the pool is also what the [`agentPrompt`](/docs/automations/triggers/interaction.worker/elements/agentPrompt/) composer offers a worker in its model picker. Leave it blank and the agent may use every available model.

Because it's a search rather than a list, adding a model puts it in every pool it qualifies for without anyone maintaining anything – the same reason [model pools](#model-pools) are resolved by search.

**Availability is enforced underneath the query, not by it.** Only models with a status of **Available** can enter a pool, whatever the query says, so an **Unlisted** or **Disabled** model can never be reached by widening one. You can't accidentally grant access to a model you've switched off.

A query that doesn't parse resolves to **no models**, which fails the turn rather than silently falling back to every model. When a query doesn't set its own `sort:`, models are ordered by `priority`, then by descending intelligence rating, then by name.

**Authoring a block for a surface is the opt-in.** An agent with no block for a surface isn't enabled there, so a new surface never turns every existing agent loose on it. Turning one off is `disabled@bool: yes` rather than deleting the block, so a surface that's switched off keeps the prompt, tools, and model query you set for it.

Each surface shows what it's adding _to_, in the field itself, in two different ways:

| Field | How the inherited value appears |
| --- | --- |
| Filesystems, tools, the chat, the command line | Faded **ghost tiles** beside your own choices, with no remove control |
| Instructions, the model query | The inherited text as the field's **placeholder**, with the field itself empty |

Both mean the same thing: nothing is set here, so what's above applies. All of it follows an edit to Everywhere immediately, without saving, so turning something on above takes it out of every surface below while you watch.

A placeholder and a typed value look alike at a glance but are not the same. An empty field showing inherited text as its placeholder **follows** Everywhere; the same words typed into the field **pin** them here and stop following. If you need to know which you have, clear the field -- the placeholder returns.

A ghost tile has no remove control, because it isn't yours to remove. That's the "a surface can grant more, never less" rule made visible: you can add to what Everywhere contributes, never subtract from it.

Below, the **Command Bar** scope of the [agent Cerb ships](#the-default-chat). Its filesystems and terminal namespaces are ghost tiles inherited from Everywhere, and its instructions and model query show their inherited values as placeholders. Its **description** is the one thing set on the surface itself – typed into the field rather than showing through it – which is what the shipped configuration gives this surface and nothing more:

 

Configuration written by hand through the [API](/docs/api/) or an [automation](/docs/automations/) survives a visit to the AI tab, including keys the form draws no control for. Anything it can't show as a chip is listed plainly with a button to remove it.

**A workflow-managed agent is a different matter**, and the banner at the top of the tab says so: an agent defined by a [workflow](/docs/workflows/) is restored from that template on the next import, and anything typed into this form in the meantime is replaced. Surviving a visit to the tab is not the same as surviving an import. Edit the workflow, not the agent.

## Authoring the configuration as KATA

The **AI** tab is a form over a [KATA](/docs/kata/) document, and that document is readable and writable as the `agent_config` field on the [worker](/docs/records/types/worker/) record – through the [Records API](/docs/api/endpoints/records/), a [package](/docs/packages/), or a [workflow](/docs/workflows/). That's what lets an agent be version-controlled, diffed, and synchronized between a development and a production install, rather than existing only as something clicked into a form.

The top-level keys are:

| Key | Type | Description |
| --- | --- | --- |
| `system_prompt:` | text | The agent's instructions |
| `models_query:` | text | A [search query](#restricting-which-models-an-agent-may-use) over agent models |
| `automation:` | text | The chat this agent runs. Omit it to run the [default chat](#the-default-chat) |
| `mounts:` | object | [Filesystems](#agent-filesystems) to mount, each with `filesystem:`, `at:`, `mode:`, and `create@bool:` |
| `tools:` | object | [Agent tools](#agent-tools) by name, each optionally overriding `description:`, `icon:`, and `labels:` |
| `terminal:` | object | The [`cerb` command line](#the-cerb-command-line) namespaces this agent may use |
| `commands:` | object | Commands the agent may run |
| `components:` | object | One block per [surface](#where-an-agent-runs) the agent is enabled on |

`components:` is keyed by a surface's `component` value, and matches any key rather than a fixed list, so a surface added in a later release needs no change here. Each block accepts `system_prompt:`, `models_query:`, `automation:`, `mounts:`, `tools:`, `terminal:`, and `commands:` – the same keys, scoped to that surface and combined with the top level per the [table above](#everywhere-and-per-surface) – plus `description:` and `disabled@bool:`.

```
system_prompt: Your name is Cerb.
models_query: sort:priority,-intelligence
mounts:
  docs:
    filesystem: cerb-docs
    at: /docs
    mode: read
terminal:
  cerb:
    platform:
    records:
components:
  commandbar:
  worklist:
  mail_routing:
    system_prompt: Prefer editing a rule over rewriting the document.
```

Written as a nested object, a workflow template can target a single leaf – a `models_query:` drawn from workflow config, say – rather than interpolating into one opaque blob. KATA text is accepted too, for anyone writing it by hand. Either way it's validated against the same schema the AI tab uses, so a malformed configuration is refused rather than stored.

**An empty component block is a complete configuration.** A surface key with nothing under it enables the agent there with no overrides, which is the normal case. Authoring the block _is_ the opt-in, and `disabled@bool: yes` is how you switch one off without losing what you wrote for it.

The [Workflow Builder](/docs/workflows/) exports AI workers as `records: worker/<label>:`, carrying `first_name`, `last_name`, `at_mention_name`, `title`, the `is_ai`, `is_superuser`, and `is_disabled` flags, and `agent_config` when the agent has one. Workers that **aren't** AI are skipped rather than refused – a person's record is personal data and doesn't belong in a shared template. The configuration is exported as a parsed tree rather than as the raw text you typed, so comments in an authored config aren't carried across.

# Automations

Three [automation](/docs/automations/) commands work with agents:

| Command | Notes |
| --- | --- |
| [`llm.agent:`](/docs/automations/commands/llm.agent/) | A multi-turn agent conversation with tools, mounts, and a transcript |
| [`llm.chat:`](/docs/automations/commands/llm.chat/) | A single-turn completion with no transcript, memory, or tools |
| [`llm.router:`](/docs/automations/commands/llm.router/) | Resolve one or more agent model searches to a list of models for use as data |

Worker [interactions](/docs/interactions/) can include an [`agentPrompt`](/docs/automations/triggers/interaction.worker/elements/agentPrompt/) form element – the composer a worker types into when an automation runs an agent conversation. It supports `@mention` completion for workers and filesystem files, image paste and attachment, model selection, and custom slash commands.

# Providers

Cerb supports fourteen LLM providers:

`anthropic`, `aws_bedrock`, `docker`, `gemini`, `groq`, `huggingface`, `ollama`, `openai`, `openrouter`, `pinecone`, `qwen`, `together`, `voyage`, and `zai`.

`openrouter` is worth calling out: one account reaches nearly every frontier model, and it publishes a context window and the accepted input types for every model it serves – so refreshing the model list on an [agent model](/docs/records/types/agent_model/) record fills in the context window and image support for whichever model you pick, across every vendor it fronts. Model IDs there are namespaced by vendor, like `anthropic/claude-sonnet-5`. Transcripts and pickers show the OpenRouter mark rather than the underlying vendor's, since that's the route the request and the bill actually took; set an `icon` on the model record if you'd rather see the vendor.

Beyond that list, any provider that speaks the OpenAI API or Anthropic API protocol works too. Choose the matching provider on an [agent model](/docs/records/types/agent_model/) and change its API endpoint URL to point at the service – that covers self-hosted runtimes like llama.cpp, LM Studio, and vLLM, as well as gateways and proxies that emulate either protocol.

Not every provider supports every capability. Chat streaming is implemented by every provider in that list, and is on by default wherever it's supported; add `stream@bool: no` to a provider's block to send a single blocking request instead. Embedding models are available from `aws_bedrock`, `huggingface`, `ollama`, `openai`, `pinecone`, `together`, and `voyage`.

# Long conversations

A conversation that outgrows the model's context window is **compacted** rather than failing: older turns fold behind a summary while recent turns are kept verbatim. By default this triggers at 90% of the context window and keeps roughly the last 5% verbatim. Both ratios are configurable per model under a `compaction:` block, and a worker can fold a session on demand with the `/compact` command.

Agent turns also use **prompt caching** by default, since a long conversation re-sends the same prefix every turn.

