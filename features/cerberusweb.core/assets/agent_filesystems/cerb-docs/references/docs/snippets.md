---
id: "docs-snippets"
title: "Snippets"
url: "https://cerb.ai/docs/snippets/"
summary: "This page explains the concept of snippets in Cerb, which allow workers and automations to insert predefined text into messages efficiently. Snippets function like a shared clipboard but with advanced scripting capabilities, including placeholders and conditional logic, enabling dynamic content adaptation based on context. A typical example provided is an auto-responder message that uses placeholders to personalize responses, demonstrating how snippets can streamline communication processes by automatically filling in details like names and ticket information."
tags: ["docs"]
---
[Workers](/docs/workers/) and [automations](/docs/automations/) can use **snippets** to quickly insert predefined text into messages. You can think of snippets as _copying and pasting_ from a giant shared clipboard.

However, unlike the traditional _paste_ action, snippets also support [sophisticated scripting functionality](/docs/scripting/) with **placeholders** and **conditional logic**. This means that the content of a snippet can change based on when and where you use it.

In a common use case, an auto-responder message will use placeholders in a snippet like:

```
Hi <b>{{first_name}}</b>,

Thanks for contacting us!

A new support ticket has been opened in response to your message:

Reference #: <b>{{mask}}</b>
Subject: <b>{{subject}}</b>

We'll be in contact shortly.
```

The above snippet results in the following text when used by an [automation](/docs/automations/) on a new ticket:

```
Hi <b>Charlotte</b>,

Thanks for contacting us!

A new support ticket has been opened in response to your message:

Reference #: <b>CRB-01092-002</b>
Subject: <b>Do you accept purchase orders?</b>

We'll be in contact shortly.
```

# Prompts

 

In snippets, **prompts** are used to dynamically modify content based on additional information from a worker in real-time. These responses available as placeholders in the snippet.

Prompts are configured in the "Prompts" section at the bottom of the snippet editor. They are defined in [KATA](/docs/kata/).

There are three prompt types: `text:`, `picklist:`, and `checkbox:`.

## Text

`text:` gathers a free-form text value.

| **label:** | A label to describe the prompt's value. |
| **default:** | A default value. |
| **required@bool:** | If true, this prompt must contain a value. |
| **params:multiple@bool:** | If true, multiples lines of text may be provided. Otherwise, the default is a single line of text. |

## Picklist

`picklist:` gathers a text value from a dropdown with pre-defined options.

| **label:** | A label to describe the prompt's value. |
| **default:** | A default value. |
| **required@bool:** | If true, this prompt must contain a value. |
| **params:options@csv:** | A `@list` or `@csv` list of possible options. |

## Checkbox

`checkbox:` gathers a true/false value from a toggle. The value will be `1` for true and blank otherwise. This can be used to include or exclude paragraphs in a larger snippet based on various conditions.

| **label:** | A label to describe the prompt's value. |
| **default:** | A default value. |
| **required@bool:** | If true, this prompt must contain a value. |

