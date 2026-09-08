---
id: "docs-automations-triggers-agent-tool"
title: "Automations: agent.tool"
url: "https://cerb.ai/docs/automations/triggers/agent.tool/"
summary: "An agent.tool automation is the work behind an agent tool record. An AI agent calls the tool, the automation runs, and the content it returns is what the model reads back. The automation declares its own inputs block and that block is the schema the model is shown, so a tool is self-documenting and there is one place to change what it takes. Alongside the arguments the automation receives the environment of the call -- the agent tool record that was called, the AI worker running it, the worker that agent is serving, the surface the conversation is happening on, and which kind of conversation it is -- so a tool can refuse work that does not belong where it was invoked. One automation can back several tool records by branching on the tool name. An agent tool runs to completion inside a single tool call, so it cannot await, and saving a script that does is refused."
tags: ["docs", "docs-automations"]
---
An **agent.tool** [automation](/docs/automations/) is the work behind an [agent tool](/docs/records/types/agent_tool/) record. An [AI agent](/docs/agents/) calls the tool, this runs, and the `content:` it returns is what the model reads back.

```
inputs:
  text/query:
    description: What to look up in the handbook
    required@bool: yes

start:
  data.query:
    inputs:
      query: type:kb_article content:{{inputs.query}} limit:5
    output: results
  return:
    content@text:
      {% for row in results.rows %}
      - {{row.title}}: {{row.excerpt}}
      {% endfor %}
```

- [The inputs block is the schema](#the-inputs-block-is-the-schema)
- [Inputs](#inputs)
- [Outputs](#outputs)
- [The environment of the call](#the-environment-of-the-call)
- [One automation, several tools](#one-automation-several-tools)
- [An agent tool can't await](#an-agent-tool-cant-await)
- [Starter template](#starter-template)

# The inputs block is the schema

The automation declares its own [`inputs:`](/docs/automations/#inputs), and **that block is the schema the model is shown**. Its `description`, `required`, `allowed_values`, and `default` are what the model reads and obeys, and they're also what the script reads back as `inputs.<name>`.

There's no second place to declare them. The [agent tool](/docs/records/types/agent_tool/) record supplies what an automation has nowhere to say – the name the model calls, the description it reads, the transcript's icon and wording – and nothing about the arguments.

Every input is advertised to the model as a string regardless of its declared type, because a provider takes JSON scalars and a `record/` input is a name or an id to the model either way.

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `inputs.*` | dictionary | The arguments for this call, read as `{{inputs.query}}`. Declared in this automation's own `inputs:` block |
| `tool_*` | record | The [agent tool](/docs/records/types/agent_tool/) record that was called. Supports key expansion, so `tool_name` is the name the model used |
| `agent_*` | record | The AI [worker](/docs/records/types/worker/) running this tool. Supports key expansion, so `agent_name` and `agent__image_url` are the agent's own name and avatar |
| `transcript_user_*` | record | The [worker](/docs/records/types/worker/) the agent is serving. Unset when the conversation belongs to a portal visitor rather than a worker |
| `transcript_agent_*` | record | The AI worker the conversation itself runs as. The same record as `agent_*` in every normal case |
| `transcript_surface` | text | Where the conversation is running, when it's an agent pane: `automation`, `automation_scripting`, `data_query`, `icon`, `mail_reply`, `worklist`, or `commandbar`. Empty otherwise |
| `transcript_trigger` | text | The trigger of the automation that called this tool, e.g. `cerb.trigger.interaction.worker.agent` |
| `transcript_uuid` | text | The conversation this call belongs to. Empty when the tool is run from the simulator |

# Outputs

| Key | Req'd | Notes |
| --- | --- | --- |
| `content:` | **x** | The result of the tool call, as the model should read it |

```
return:
  content: There are 12 open tickets in that group.
```

# The environment of the call

A tool is handed **where it was called from**, not only what the model asked for. That's what lets a tool refuse work that doesn't belong where it was invoked – an action that's fine from a mail reply and wrong from a portal visitor's chat, or one that only makes sense beside the automation editor.

```
start:
  decision:
    outcome/wrong_place:
      if@bool: {{transcript_surface != 'mail_reply' ? 'yes'}}
      then:
        return:
          content: This tool only runs beside a mail reply.
    outcome/proceed:
      then:
        return:
          content: {{reply_draft}}
```

`transcript_trigger` says which **kind** of conversation this is – a worker's chat, a website visitor's, another agent's – while `transcript_surface` says which screen a worker's chat is open beside.

# One automation, several tools

Because the [agent tool](/docs/records/types/agent_tool/) record owns the schema, one automation can back several tool records. Branch on `tool_name` to tell them apart rather than writing a script per tool:

```
start:
  decision:
    outcome/search:
      if@bool: {{tool_name == 'search_handbook' ? 'yes'}}
      then:
        return:
          content: {{handbook_results}}
    outcome/read:
      if@bool: {{tool_name == 'read_handbook' ? 'yes'}}
      then:
        return:
          content: {{handbook_page}}
```

An automation's **Usage** panel lists the agent tools that reference it, resolved by looking them up rather than by scanning every script for the automation's name.

# An agent tool can't await

An agent tool runs to completion **inside a single tool call**. There's no continuation to resume into, so it returns a `content:` string and can never [`await:`](/docs/automations/commands/await/).

An `await:` at any depth in the script is refused when the automation is saved, with a message saying to return `content:` instead.

A tool that genuinely needs to ask the worker something -- an approval, a missing argument, a handoff to another interaction -- belongs to the calling script instead. Leave the [agent tool](/docs/records/types/agent_tool/) record's automation empty and answer the call from the conversation's own `on_tool:` branch with `tool.return:`, which _can_ await.

# Starter template

**Automations » Build » AI Agent Tool** seeds a working script on this trigger, together with the [agent tool](/docs/records/types/agent_tool/) record that calls it.

