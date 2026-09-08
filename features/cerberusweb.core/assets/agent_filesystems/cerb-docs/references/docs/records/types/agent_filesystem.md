---
id: "docs-records-types-agentfilesystem"
title: "Agent Filesystem Records"
url: "https://cerb.ai/docs/records/types/agent_filesystem/"
summary: "An agent filesystem is a named volume of files that AI agents and workers can both read and write. Automations mount volumes for an agent under the mounts key, which enables a single agent_terminal tool covering every mounted volume. Workers browse and edit the same volumes from the Setup Agent Filesystem Terminal using the same command set, so the two views can never drift apart. A ZIP archive can be bulk-imported in the background. This page documents the Records API fields and search filters available on agent filesystem records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Agent Filesystem |
| **Name (plural):** | Agent Filesystems |
| **Alias (uri):** | agent\_filesystem |
| **Identifier (ID):** | cerb.contexts.agent.filesystem |

- [Mounting a volume](#mounting-a-volume)
- [Commands](#commands)
- [The Setup terminal](#the-setup-terminal)
- [Importing a ZIP archive](#importing-a-zip-archive)
- [Bundled volumes](#bundled-volumes)
  - [The volumes Cerb ships](#the-volumes-cerb-ships)
  - [cerb-agents](#cerb-agents)
  - [cerb-docs](#cerb-docs)

- [Records API](#records-api)
- [Search Query Fields](#search-query-fields)

An **agent filesystem** is a named volume of [agent files](/docs/records/types/agent_file/) that [AI agents](/docs/agents/) and [workers](/docs/workers/) can both read and write. Each volume caches its file count and total size.

https://www.youtube.com/embed/LAy--sYJQYk

A volume's `name` must start with a letter, followed by letters, digits, or dashes.

### Mounting a volume

There are two ways a volume reaches an agent, and most installations only ever need the first.

**On the agent record.** An [AI worker](/docs/agents/)'s **AI** tab has a filesystems field, so giving an agent a volume is a picker rather than a script. Volumes chosen under **Everywhere** come with the agent wherever it runs, and a surface _adds_ to that rather than replacing it – an agent's filesystems are the union of the two. Nothing else is required: an agent that names no chat of its own runs the one Cerb ships, so a new volume, a new AI worker, and one surface turned on is enough to ask it a question.

**In an automation.** For a script that composes its own agent turn, an [automation](/docs/automations/) makes volumes available by listing them under `mounts:` on [`llm.agent:`](/docs/automations/commands/llm.agent/):

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
| `filesystem:` | The source volume, when it differs from the mountpoint key. Accepts a name, an ID, or a `cerb:agent_filesystem:<name>` URI |
| `mode:` | `read-only` (the default) or `read-write` |
| `at:` | An explicit mountpoint path, overriding the key |
| `create@bool:` | Provision the volume by name on first mount. Idempotent, and skipped when simulating |

Writing `mounts:` at all is what enables the `agent_terminal` tool. Because **one tool covers every mounted volume**, adding or removing a mount doesn't change the tool's schema – which means it doesn't invalidate the provider's cached prompt prefix.

An empty `mounts:` block is a valid configuration: it mounts no volumes, but the agent still gets a `/tmp` scratch area.

The same volume can be mounted twice using a `<name>@<suffix>` key.

However a session's `mounts:` resolved on the day it ran, its transcript records the result. A **Filesystem Mounts** panel sits directly below Tools, naming each mountpoint, the volume behind it, that volume's file count and size, and the mode the agent had. `/tmp` appears alongside them as per-run scratch, without a linked record or a file count:

 

This is worth knowing about because a mount that can't resolve – a volume since deleted or disabled – is skipped silently at run time rather than failing the turn. One unresolvable entry never costs the agent the volumes that do resolve, so a session can have had fewer volumes than its automation asked for, and the transcript is the only place that says so.

### Commands

Agents and workers use the same command set. The tool description is generated from the same source as the terminal's `help` output, so what an agent is told and what a worker sees can't drift apart.

| Command | Notes |
| --- | --- |
| `ls` | List one directory |
| `find` | Recursive search by name, type, extension, or depth |
| `cd` | Change directory; supports `..`, absolute paths, and `@filesystem/path` |
| `read` | Read a file, optionally by offset and limit |
| `search` | Search file contents |
| `write` | Write a file (read-write mounts only) |
| `append` | Append to a file (read-write mounts only) |
| `edit` | Replace an exact snippet, which must match exactly once (read-write mounts only) |
| `copy` | Copy one file; `-f` to overwrite |
| `rm` | Delete a file (read-write mounts only) |
| `pwd` | Print the working directory |
| `help` | Usage for all commands, or one command |

There is no `move` command. Copy the file, then `rm` the source.

Command output can be piped through a Twig filter chain in place of Unix pipes. Output too large to return inline is spooled to `/tmp` and referenced by path.

### The Setup terminal

Workers browse and edit volumes from **Setup » Developers » Agent Filesystem Terminal**, which is restricted to administrators.

### Importing a ZIP archive

A ZIP archive can be uploaded to a volume and imported in the background as a [queue job](/docs/records/types/queue_job/). Progress appears on the filesystem record, and the 'Agent Filesystem' card widget shows the volume's file count, total size, and a history of past imports.

### Bundled volumes

Some volumes ship **with Cerb** and import themselves on `/update`, so an agent has reference material without anyone uploading an archive. They stay current for whatever version you're running, with no maintenance.

Each bundled volume carries a manifest, and only its hash is read at runtime: an update where nothing changed does no work at all, while a changed volume runs through the same import job an admin upload uses. That job is drained inline, so when `/update` finishes, the content is there.

**A bundled volume mirrors what shipped.** A file created in one by a worker or an agent is removed on the next update. Content imported by hand through the ordinary [ZIP upload](#importing-a-zip-archive) is never touched.

**Setup » Developers » Platform** has a **Reload** button that reconciles every bundled volume against its rows without waiting for a version change.

#### The volumes Cerb ships

Cerb ships two bundled volumes of its own:

| Volume | Contents |
| --- | --- |
| `cerb-agents` | **Skills** – short guides an agent reads when it needs one |
| `cerb-docs` | The **complete Cerb documentation** as Markdown |

#### cerb-agents

Each skill is one file an agent reads when it needs it, covering a domain a capable model is most often subtly wrong about – the [agent terminal](/docs/agents/#filesystem-commands), [KATA](/docs/kata/), [automations](/docs/automations/), [scripting](/docs/scripting/), [search queries](/docs/search/), [records](/docs/records/), [data queries](/docs/data-queries/), [icons](/docs/developers/icons/), mail replies, and the documentation itself.

Skills **compose**, which is the point. An agent asked for a chart of open tickets by group reads `automations` for the command shape, `data-queries` for the source and its aggregation, `scripting` for the Twig in between, and `search-queries` for the filter grammar – four short reads on the turn that needs them, rather than one prompt large enough to hold all four being re-sent on every turn of every conversation.

Nothing is loaded up front. An `INDEX.md` says what each skill covers and when to reach for it, and the agent reads from there.

Reading a skill grants **knowledge, not a role**. An agent's identity and tools come from its system prompt and stay fixed for the conversation; work that genuinely belongs to another role belongs to a subagent.

The same volume serves every agent, so a skill improved once is improved for all of them. A new agent chat mounts it by default.

#### cerb-docs

The `cerb-docs` volume carries the complete Cerb documentation as Markdown – the reference manual, [guides](/guides/), worked [solutions](/solutions/), [tips](/tips/), [workflows](/docs/workflows/), and release notes. Mount it and an agent can **look a feature up rather than recall it**, which matters most exactly where a capable model is subtly wrong: search query syntax, KATA keys, API endpoints, and automation commands.

It's over a thousand files, so it's a volume to **search rather than browse**. An `INDEX.md` at the volume root says what lives in each directory, which paths are predictable enough to read directly, and what each page's front matter carries – enough to orient an agent that mounts the documentation and nothing else. The `docs` skill in `cerb-agents` covers the same ground in more depth for an agent that has both.

Because it's bundled, the documentation **tracks the release** -- what an agent reads is the documentation for the version it's running on, not whatever is current on the website.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | A description of this volume |
| &nbsp; | `file_count` | [number](/docs/records/fields/types/number/) | The cached number of files in this volume |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this volume |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | Is this volume disabled? (`0` or `1`) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The volume name; must start with a letter, then letters, digits, or dashes |
| &nbsp; | `total_bytes` | [number](/docs/records/fields/types/number/) | The cached total size of this volume, in bytes |
| &nbsp; | `type` | [text](/docs/records/fields/types/text/) | The volume type |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Search Query Fields

These [filters](/docs/search/#filters) are available in agent filesystem [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created` | date | When the record was created |
| `fieldset` | virtual | Filter by [custom fieldset](/docs/records/types/custom_fieldset/) |
| `id` | number | The record ID |
| `name` | text | The volume name (partial match) |
| `updated` | date | When the record was last modified |
| `watchers` | virtual | Filter by [watchers](/docs/watchers/) |

