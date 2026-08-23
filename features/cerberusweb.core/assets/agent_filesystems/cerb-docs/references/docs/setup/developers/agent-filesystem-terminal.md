---
id: "docs-setup-developers-agent-filesystem-terminal"
title: "Agent Filesystem Terminal"
url: "https://cerb.ai/docs/setup/developers/agent-filesystem-terminal/"
summary: "This page documents the Agent Filesystem Terminal in Cerb's developer menu. It's a human-driven terminal over agent filesystems, running the same command evaluator an AI agent gets through its agent_terminal tool, so a volume can be explored and exercised without wiring up an agent. The page covers mounting volumes and the read-only default, the Commands panel that enables the same cerb command line an agent gets, the payload and find boxes that carry file content and edit text out of band, the output pane's fixed height and the pipeline and /tmp answers for large output, and the fact that the whole session lives in the browser and is lost on reload."
tags: ["docs"]
---
The Agent Filesystem Terminal lets you drive an [agent filesystem](/docs/records/types/agent_filesystem/) by hand, using the same [command set](/docs/agents/#filesystem-commands) an [AI agent](/docs/agents/) gets through its `agent_terminal` tool.

Because both run through one evaluator, what you can do here and what an agent can do can't drift apart – which makes this the place to explore a volume, or to exercise the command layer before any agent is wired to it.

 

- [Access](#access)
- [Mounting](#mounting)
- [Commands panel](#commands-panel)
- [Running commands](#running-commands)
- [Output](#output)
- [The session is not saved](#the-session-is-not-saved)
- [Compared to an agent](#compared-to-an-agent)

# Access

Click **Setup » Configure » Developers » Agent Filesystem Terminal**. This tool is limited to administrators.

# Mounting

Nothing is mounted when you arrive. Add [agent filesystem](/docs/records/types/agent_filesystem/) records from the chooser at the top right, and each appears as a chip in the **Mounts** panel. You can mount several at once; mounting the same volume twice does nothing.

Each volume mounts at its own name. Unlike the `mounts:` block an [automation](/docs/agents/#agent-filesystems) composes for an agent, you can't remap a volume to a different path here.

Every mount arrives **read-only**. Switch a chip to `rw` to allow `write`, `append`, `edit`, `rm`, and writing with `copy`.

The `ro`/`rw` switch is a workflow safeguard, not a security boundary -- it protects you from a mistyped `rm`, not from a determined operator. Access control for this screen is the administrator requirement itself.

`/tmp` is always writable, since it's where oversized command output is spilled.

# Commands panel

Beside the mounts, a **Commands** panel turns each [`cerb` command namespace](/docs/agents/#the-cerb-command-line) on or off for this session – the same command line an [automation](/docs/automations/commands/llm.agent/#terminal) gives an agent through its `terminal:` block.

With `records` enabled you can run `cerb records types`, `cerb records filters <type>`, and `cerb records fields <type>` here exactly as an agent would, which is the quickest way to see what an agent will be told about your installation.

With no namespace enabled the `cerb` command isn't merely hidden – it doesn't exist, and typing it says so.

# Running commands

Type `help` to list the commands, or `help <command>` for one of them. **Tab** completes both command names and paths, and the up and down arrows recall previous commands along with their payload.

The prompt shows the working directory, which persists between commands the same way it does for an agent.

### Payload and Find

Two boxes below the command line carry the things that don't belong on it.

**Payload** does three jobs depending on the command:

| With | Payload holds |
| --- | --- |
| `write`, `append` | The file's content |
| `edit` | The replacement text |
| Anything else | A multi-line [Twig](/docs/scripting/) template to evaluate |

**Find** holds the search text for `edit` – the snippet to be replaced, which must match exactly once.

Keeping these out of band means a script or a block of file content never has to be escaped onto a command line.

# Output

The output pane is a fixed height and scrolls with new output pinned to the bottom, so a long result scrolls the command that produced it out of view.

There's no paging. For output larger than the pane, the intended tools are the same ones an agent uses: pipe through a filter chain to narrow it, or read from the `/tmp` file that oversized output spills into.

```
search widgets | results|filter(r => r.hits > 3)|column("path")
```

**Clear** wipes the scrollback only. Your mounts, working directory, and `/tmp` contents survive it, the way `clear` behaves in a shell.

If the pane flashes, that's the terminal bell: Tab completion had either nothing to offer or too many choices to pick from.

# The session is not saved

**Everything here lives in your browser.** The mount set, the working directory, and the `/tmp` scratch area are held in the page, and the server keeps no state between commands. Reloading -- or navigating away and back -- returns you to an empty terminal with nothing mounted, without warning. Nothing you do here is lost from the volumes themselves; it's the session setup that goes.

# Compared to an agent

An agent gets the same commands, the same evaluator, and the same persistent working directory. Two things differ:

- An agent's mounts come from the [automation's](/docs/agents/#agent-filesystems) `mounts:` block, resolved on the server. Here you compose the mount set by hand.
- The Payload and Find boxes are specific to this screen. An agent passes the same values as part of its tool call.
- The `cerb` namespaces an agent may run come from its automation's `terminal:` block. Here you enable them yourself, from the Commands panel.

