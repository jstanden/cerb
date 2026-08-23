---
id: "docs-automations-triggers-interaction-worker-elements-say"
title: "Say - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/say/"
summary: "This page provides information on the 'say' interaction form element used in Cerb's web forms. It explains how the 'say' element is utilized to display blocks of text or Markdown within a form. The page includes syntax details, specifically focusing on the 'content' attribute for displaying Markdown-formatted text and the 'message' attribute for displaying plain text. An example is provided to illustrate the implementation of the 'say' element in a form."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **say** element displays a block of text or Markdown.

```
start:
  await:
    form:
      elements:
        say/hello:
          content@text:
            # Heading
            This is a **paragraph** in Markdown.
```

 

# Syntax

### content:

Content to display in Markdown format.

### message:

Message to display as plain text.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```
