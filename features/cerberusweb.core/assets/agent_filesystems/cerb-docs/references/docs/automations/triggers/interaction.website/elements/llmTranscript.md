---
id: "docs-automations-triggers-interaction-website-elements-llmtranscript"
title: "LLM Transcript - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.website/elements/llmTranscript/"
summary: "This page provides information on the 'llmTranscript' interaction form element used in Cerb's web forms."
tags: ["docs", "docs-automations"]
---
In [website interactions](/docs/automations/triggers/interaction.website/) web forms, an **llmTranscript** element displays an [llm.agent:](/docs/automations/commands/llm.agent/) chat transcript.

```
start:
  # ... Run llm.agent:
  await:
    form:
      title: AI Agent
      elements:
        llmTranscript/prompt_transcript:
          session_id: {{results.session_id}}
          hidden@bool: {{prompt_user is empty}}
        text/prompt_user:
          required@bool: yes
          placeholder: (ask a question about Cerb)
        submit:
          buttons:
            continue/send:
              label: Send
              icon: send
              value: send
```

 

# Syntax

### session\_id:

The transcript ID to display. This can be retrieved from [llm.agent:](/docs/automations/commands/llm.agent/) output.

### label:

An optional label displayed above the transcript.

Tool display names and icons aren't configured here. They're set on the tool itself in [`llm.agent:`](/docs/automations/commands/llm.agent/#tools), with `labels:` (`summary:` and `active:`) and an `icon:`.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not prompt_user}}
```
