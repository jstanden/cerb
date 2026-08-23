---
id: "docs-automations-triggers-interaction-website-elements-say"
title: "Say - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.website/elements/say/"
summary: "This page provides information on the 'say' interaction form element used in Cerb's website interaction forms. It explains how the 'say' element is utilized to display blocks of text or Markdown within a form. The page includes a syntax example demonstrating how to configure the 'say' element with content in Markdown format, highlighting the use of headings and paragraphs. Additionally, it outlines the syntax options available for displaying content, such as using the 'content' attribute for Markdown and the 'message' attribute for plain text."
tags: ["docs", "docs-automations"]
---
In [website interactions](/docs/automations/triggers/interaction.website/) forms, a **say** element displays a block of text or Markdown.

```
start:
  await:
    form:
      title: Example
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
hidden@bool: {{expression}}
```

### references:

```
await/router:
  form:
    title: How can we help?
    elements:
      say:
        content@text:
          
        references:
          resource/logo:
            uri: cerb:resource:portal.logo.cerb
```
