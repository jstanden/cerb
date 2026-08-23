---
id: "docs-setup-developers-llm-agent-transcripts"
title: "LLM Agent Transcripts"
url: "https://cerb.ai/docs/setup/developers/llm-agent-transcripts/"
summary: "This page documents the LLM Agent Transcripts browser in Cerb's developer menu. Every agent conversation is recorded automatically and can be read back turn by turn, with token accounting, tool calls, thinking, images, and the filesystem volumes the session mounted. The page covers what is and isn't recorded, the fact that transcripts are never pruned and can only be deleted one at a time by hand, the one-way Active-to-Archived triage flow, forking a conversation onto another provider and what a cross-provider fork does not carry over, the preview-only compaction estimator, and the important fact that a transcript is not a record type -- it has no search filters, no worklist, and no Records API."
tags: ["docs"]
---
The LLM Agent Transcripts browser is where you read back what an [AI agent](/docs/agents/) actually did – every turn of every conversation, with the tokens it consumed, the tools it called, the files it reached, and the reply it gave.

https://www.youtube.com/embed/dOjF7-nofbA

- [Access](#access)
- [What gets recorded](#what-gets-recorded)
- [The browser](#the-browser)
  - [Active and Archived](#active-and-archived)

- [Reading a transcript](#reading-a-transcript)
  - [The turns](#the-turns)
  - [Filesystem mounts](#filesystem-mounts)

- [Forking a conversation](#forking-a-conversation)
- [Estimating compaction](#estimating-compaction)
- [What a transcript isn't](#what-a-transcript-isnt)

# Access

Click **Setup » Configure » Developers » LLM Agent Transcripts**. This tool is limited to administrators.

**An administrator sees every transcript.** There's no per-worker scoping and no non-admin view -- a worker can't read back their own conversations. Since a transcript captures the full content of a conversation along with the worker who started it and their IP address, treat access to this screen as access to everything anyone has said to an agent.

# What gets recorded

Every [`llm.agent:`](/docs/automations/commands/llm.agent/) conversation and every [`agentPrompt`](/docs/automations/triggers/interaction.worker/elements/agentPrompt/) exchange is recorded. There's no setting to turn it on, and none to turn it off.

Not recorded: [`llm.chat:`](/docs/automations/commands/llm.chat/) single-turn completions, which have no session to record, and Cerb's own internal calls such as summarization.

**Transcripts are never pruned.** There's no retention setting, no age limit, no row cap, and no scheduled job that removes them. They accumulate indefinitely until an administrator deletes them by hand, one at a time, from this screen. On a busy installation that's worth knowing before it becomes a surprise.

Deleting a transcript removes its turns, and the images attached to it are released to be reaped by the nightly sweep.

# The browser

A sidebar on the left lists transcripts, newest activity first. Each row carries the agent's mark, the worker who started the conversation (or **Anonymous** for a portal visitor), how long ago it was active, and its total tokens.

The list shows 100 at a time. **More** loads the next batch.

## Active and Archived

The switcher above the list is a triage tool, not a two-way filing system.

**Archive** marks a transcript as dealt with and moves it out of the Active list, then advances you to the next one – so a backlog can be worked through without leaving the keyboard.

**Archiving is one-way.** Nothing un-archives a transcript: not the switcher, not the viewer, not an API call. Archive it when you're finished with it, not to file it for later.

Nothing archives on its own, either. A transcript stays Active however old it is until somebody archives it. And no view shows both lists at once.

# Reading a transcript

The header carries the conversation's ID and its actions, with a **Permalink** for linking someone straight to it.

Beneath that, a row of chips summarizes the session – the provider and model, the [connected account](/docs/records/types/connected_account/) that authenticated it, who was talking, the token totals, the turn counts, and the [automation](/docs/automations/) that ran it. Each links to the record behind it.

The **Tokens** chip is the one worth understanding:

| Figure | Meaning |
| --- | --- |
| Context Used | The current conversation size – the last turn's prompt plus its response |
| Input | Cumulative fresh tokens, charged at full price |
| Cached Reads | Cumulative tokens served from the prompt cache, at roughly a tenth of the price |
| Cached Writes | Cumulative tokens written into the cache, at 1.25× the price |
| Output | Cumulative generated tokens |

Every turn re-sends the whole history, so on a long conversation **cached reads should dominate**. If they don't, [prompt caching](/docs/agents/#long-conversations) isn't doing its job and the session is costing more than it needs to.

## The turns

Turns read like a conversation, with the agent's replies carrying the provider's brand mark and a `provider · model` pill.

| Element | When it appears |
| --- | --- |
| System prompt | When the session had one. Collapsed by default – open it to see what the agent was told |
| Thinking | On models that emit it |
| Tool calls | On agent turns: the tool's name and icon, its parameters, its result, and how long it took |
| Tokens | On agent turns, as In / Out / Cached % |
| Images | When a turn carried them, rendered inline |
| **Truncated** / **Filtered** | Only when a turn ended that way |
| Summary checkpoint | Where a long conversation was folded behind a summary |

A normal turn carries no completion badge – **the absence of a Truncated pill is what tells you a turn finished**.

## Filesystem mounts

When a session had the [agent filesystem](/docs/records/types/agent_filesystem/) tool, a **Filesystem Mounts** panel sits directly below Tools, naming each mountpoint, the volume behind it, its file count and size, and whether the agent had read-only or read-write access.

A mount whose volume has since been deleted or disabled is called out as **Missing** or **Disabled** rather than dropped from the list. Those don't mount at run time, and this panel is the only place that records the agent having had fewer volumes than its automation asked for.

The panel's absence and its emptiness mean different things: **no panel** means the filesystem was never enabled for that session, while a panel showing only `/tmp` means it was enabled with no volumes – a real configuration, since the agent still gets scratch space.

# Forking a conversation

Forking copies a conversation so you can take it somewhere else. **The original is never modified.**

**Fork** in the header opens a provider picker with an optional model name. Choosing the same provider copies the conversation verbatim. Choosing a different one rewrites every turn into that provider's format, so the conversation replays natively from then on.

**A cross-provider fork copies the conversation, not the agent.** It carries the turns and the provider settings, but deliberately not the session's system prompt, tools, mounts, or agent identity, and not its context window or authentication -- the fork resolves those from its own provider block instead. It exists to show how another model answers the same history, rather than to move an agent's setup between providers.

A cross-provider fork of an already-compacted conversation also arrives **shorter than its origin**, and deliberately so: earlier summaries are left behind rather than translated, for [compaction](/docs/agents/#long-conversations) to rebuild against the new model's context window. That's the copy working as intended, not losing turns.

**Fork from here**, on a user turn, branches the conversation just before that message – so the copy ends on the agent's previous reply, ready to be taken in a different direction. It's offered on user turns only, and not on the first message, since there's nothing before it to keep.

Switching a conversation to a different provider **mid-flight** isn't done here – that happens when an automation changes its model, and this screen is where you see the result: a summary checkpoint marking where the conversation changed hands.

# Estimating compaction

**Compact** previews what [compaction](/docs/agents/#long-conversations) would do to a session: how many turns would fold behind a summary, how much of the tail would be kept verbatim, and what the summarizing call itself would cost.

Adjust the context and tail ratios to see how the thresholds behave before committing them to a model's `compaction:` block.

**Nothing is saved.** The preview never alters the transcript, which is what makes it safe to run repeatedly while tuning.

It's offered only on sessions with a resolved provider, since estimating needs a model to summarize with and a context window to measure against.

# What a transcript isn't

A transcript is **not a [record type](/docs/records/types/)**. It's an artifact of a conversation, reachable only through this screen.

That means no search filters, no [worklists](/docs/worklists/), no profile page, no card widget, no placeholders, no custom fields, no watchers, and no [Records API](/docs/api/endpoints/records/) access. Automations can't read transcripts back, and there's no way to query across them.

The sidebar, its Active filter, and the permalink are the whole of the navigation. If you need a conversation's content somewhere else, capture it in the automation that ran it rather than expecting to retrieve it later.

