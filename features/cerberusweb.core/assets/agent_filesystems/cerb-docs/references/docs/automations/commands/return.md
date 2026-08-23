---
id: "docs-automations-commands-return"
title: "Automations: return"
url: "https://cerb.ai/docs/automations/commands/return/"
summary: "This page provides information on the 'return' command in Cerb automations, which is used to successfully terminate an automation in the `return` state and return a dictionary. It includes syntax examples, demonstrating how to use the command to output a message with dynamic content. The specific structure of the `return:` dictionary is determined by the automation trigger."
tags: ["docs", "docs-automations"]
---
The **return:** command successfully terminates an [automation](/docs/automations/) in the `return` [state](/docs/automations/#exit-states) and returns a [dictionary](/docs/automations/#dictionaries).

# Syntax

```
start:
  set:
    name: Kina
  return:
    output@text:
      Hello, {{name}}!
```

The expected `return:` dictionary depends on the automation [trigger](/docs/automations/#triggers).

