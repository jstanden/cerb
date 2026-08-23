---
id: "docs-automations-commands-while"
title: "Automations: while"
url: "https://cerb.ai/docs/automations/commands/while/"
summary: "This page provides an overview of the 'while' command in Cerb automations, which is used to conditionally repeat a sequence of actions, effectively creating controlled loops for various interactions and timers. It includes a sample script demonstrating how to increment a counter until a condition is met. The page details the syntax for the 'if' and 'do' keys, explaining that 'if@bool' must resolve to a boolean value to determine whether the loop continues, and 'do' contains the commands to be repeated."
tags: ["docs", "docs-automations"]
---
The **while:** command conditionally repeats a sequence of actions. This can implement controlled infinite loops for interactions and timers.

```
start:
  set:
    counter: 0
  while:
    if@bool: {{counter < 5 ? 'yes'}}
    do:
      set:
        counter: {{counter+1}}
  return:
    counter@key: counter
```

```
counter: 5
```

- [Syntax](#syntax)
  - [if:](#if)
  - [do:](#do)

# Syntax

### if:

The `if@bool:` key must resolve to a boolean value.

While `true`, the commands in `do:` are repeatedly executed.

If `false`, the while-loop terminates.

### do:

The `do:` key contains any number of [commands](/docs/automations/#commands) to repeat.

