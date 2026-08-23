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

Three record types work together, and you don't need all of them to get started:

| Record type | Purpose |
| --- | --- |
| [Agent Model](/docs/records/types/agent_model/) | A model you can use: its provider, model ID, credentials, context window, capabilities, and ratings |
| [Worker](/docs/records/types/worker/) (`is_ai`) | The agent's identity – name, image, ownership, `@mention` handle |
| [Agent Filesystem](/docs/records/types/agent_filesystem/) | A named volume of files an agent can read and write |

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
| `cerb platform plugins` | Every plugin with its ID, version, and whether it's enabled |
| `cerb platform points` | Every [extension point](/docs/plugins/extensions/), with how many extensions this installation has on it |
| `cerb platform extensions <point>` | The extensions on one point, by fully qualified ID |

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

