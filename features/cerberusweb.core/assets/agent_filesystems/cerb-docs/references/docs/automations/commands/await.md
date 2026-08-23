---
id: "docs-automations-commands-await"
title: "Automations: await"
url: "https://cerb.ai/docs/automations/commands/await/"
summary: "This page provides information on the 'await' command in Cerb automations, which pauses an automation in the `await` state and returns a dictionary. It explains how this command creates a continuation for resuming the automation at the current point. The page includes syntax examples and details on how the expected dictionary varies depending on the trigger. It specifically mentions that the `interaction.worker` trigger supports the `await` state, where the dictionary describes a form for gathering user input."
tags: ["docs", "docs-automations"]
---
The **await:** command pauses an [automation](/docs/automations/) in the `await` [state](/docs/automations/#exit-states) and returns a [dictionary](/docs/automations/#dictionaries).

This creates a [continuation](/docs/automations/#continuations) for resuming the automation at the current point.

# Syntax

```
start:
  await:
    form:
      title: Intro
      elements:
        text/prompt_name:
          label: What is your name?
          required@bool: yes
  return:
    output@text:
      Hello, {{prompt_name}}!
```

The expected dictionary depends on the trigger.

These [triggers](/docs/automations/#triggers) support the `await` state:

| Trigger | &nbsp; |
| --- | --- |
| [automation.timer](/docs/automations/triggers/automation.timer/#await) | The dictionary describes the next interval. |
| [interaction.website](/docs/automations/triggers/interaction.website/#outputs) | The dictionary describes a form for gathering website visitor input. |
| [interaction.worker](/docs/automations/triggers/interaction.worker/#outputs) | The dictionary describes a form for gathering worker input. |

