---
id: "docs-automations-commands-set"
title: "Automations: set"
url: "https://cerb.ai/docs/automations/commands/set/"
summary: "This page provides an overview of the 'set' command in Cerb automations, which is used to associate values with placeholder keys. It explains the syntax for setting one or more key/value pairs and demonstrates how sequential keys can reference previously set values. The page also covers the use of dictionaries as values and how to set values using key paths. Examples are provided to illustrate how these concepts are applied in practice, showing the resulting outputs for each scenario."
tags: ["docs", "docs-automations"]
---
The **set:** command associates a value with a placeholder key.

```
start:
  set:
    name: Kina
    title: Customer Support Manager
  return:
    output@text:
      {{name}} is a {{title}}
```

Result:

```
output: Kina is a Customer Support Manager
```

# Syntax

One or more key/value pairs may be children of the `set:` command.

### Sequential keys

You can refer to any keys set above the current one.

```
start:
  set:
    a@int: 2
    b@int: {{a*2}}
    c@int: {{b*2}}
  return:
    answer@int: {{a + b + c}}
```

Result:

```
answer: 14
```

### Dictionaries

A [dictionary](/docs/automations/#dictionaries) may be provided as the value:

```
start:
  set:
    person:
      name: Kina
      role:
        title: Customer Support Manager
  return:
    output@text:
      {{person.name}} is a {{person.role.title}}
```

Result:

```
output: Kina is a Customer Support Manager
```

### Paths

You can set a value using a key path with the [var.set](/docs/automations/commands/var.set/) command.

