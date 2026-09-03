# Agent pane roles

One `<component>.md` per agent-pane component, named for its `component` string in
`Cerb\Agent\Pane\Components::getAll()` -- `data_query.md` is the Data Query Tester's. Each is the
ROLE half of that agent's system prompt: what the agent is, where it sits, and the host facts that
change how it works.

The filename IS the lookup, so a file whose name matches no catalog key is simply never read (this
README included).

`_shared.md` is the exception, read by constant path and appended to every component's role. It holds
only what is true of all of them: the chat is a narrow sidebar or floating panel, so wide tables,
document headings, and long blocks do not fit. Put something there only when it would otherwise be
copied into all seven files; anything a component could disagree with belongs in its own role. The
underscore keeps it out of the lookup namespace, since no catalog key starts with one.

`Components::getSystemPromptFor()` reads these at runtime and composes them with a tool inventory
built from the live catalog, so improving a role here reaches every existing installation on the
next release -- no automation is patched and nothing is regenerated.

Three rules for editing:

- **Do not restate tool parameters or signatures.** The inventory is generated from `commands` /
  `server_tools` and would go stale here. Naming a tool in prose to say WHEN to reach for it is
  fine and often necessary; describing its arguments is not.
- **Do not reference `@cerb-agents/...` or any mounted path.** A chat can be authored without a
  filesystem, and a role that points at files the agent cannot read is worse than one that says
  nothing. The skills pointer is emitted by `getSystemPromptFor()` only when that volume is
  actually mounted, from the `skills` key in the catalog. That includes a MUST-read: to make a
  skill a precondition, give its catalog entry a gate clause (`'mail-replies' => 'draft or revise
  a reply'`) rather than writing the instruction here, where it would fire on installs that have
  no skills volume to read.
- **Safety-critical rules belong here, not in a skill.** A skill is read on demand; a role is
  always present.

NOT an agent filesystem. This directory is a sibling of `assets/agent_filesystems/`, which
`Cerb\Agent\FilesystemAssets::syncAll()` iterates -- nothing here is imported into `agent_file`,
mounted, or visible in Search > Agent Files. The two paths look alike; they are not.
