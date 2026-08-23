---
id: "solutions-automations-key-expandable-placeholders"
title: "Key expandable placeholders"
url: "https://cerb.ai/solutions/automations/key-expandable-placeholders/"
summary: "This page explains how to create key expandable placeholders in Cerb using a common prefix. By setting variables with `_context` and `id` suffixes, you can automatically create an expandable `_label` placeholder. This is useful for referencing record labels throughout your automations without explicitly loading each record."
tags: ["solutions", "solutions-automations"]
---
When you set both `{prefix}__context` and `{prefix}_id` variables using a common prefix, Cerb automatically creates a key expandable dictionary for the referenced record.

This is a shortcut for [record.get:](/docs/automations/commands/record.get/)

## Create a ticket dictionary

```
start:
  set:
    ticket__context: ticket
    ticket_id: 1
  return:
    output: {{ticket__label}}
```
