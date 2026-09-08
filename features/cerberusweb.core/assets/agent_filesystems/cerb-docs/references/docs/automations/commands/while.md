---
id: "docs-automations-commands-while"
title: "Automations: while"
url: "https://cerb.ai/docs/automations/commands/while/"
summary: "This page provides an overview of the 'while' command in Cerb automations, which is used to conditionally repeat a sequence of actions, effectively creating controlled loops for various interactions and timers. It includes a sample script demonstrating how to increment a counter until a condition is met. The page details the syntax for the 'if' and 'do' keys, explaining that 'if' must resolve to a boolean value to determine whether the loop continues, and 'do' contains the commands to be repeated. It lists the values treated as false, notes that every other value is true, and warns that a condition rendering to arbitrary text loops until the automation reaches its time limit."
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

The `if:` key must resolve to a boolean value. The `if:` key is **required**, and omitting it returns an error.

While `true`, the commands in `do:` are repeatedly executed.

If `false`, the while-loop terminates.

Any other value is converted to a boolean first. Only these are `false`: the boolean `false`, a null or unset value, empty text, and the text `false`, `0`, `no`, `n`, or `off`. **Everything else is `true`**, including any other text.

`if:` and `if@bool:` are equivalent. The [@bool](/docs/kata/#bool) annotation performs the same conversion the loop performs, so the annotation isn't what makes a condition work. What matters is that the expression renders to one of the false values above when the loop should stop.

A comparison does that: Twig renders `true` as `1` and `false` as empty text, so `{{counter < 5}}` alternates between a value that's `true` and one that's `false`.

A bare placeholder doesn't. Any text that isn't in that list is `true`, so `if: {{my_flag}}` keeps repeating until the automation reaches its [time limit](/docs/automations/#time-limit), which defaults to 25 seconds. Nothing reports that the condition was never a boolean.

This is the same root cause as the [repeat:](/docs/automations/commands/repeat/) command's [each:](/docs/automations/commands/repeat/#each) key rendering to text, with the opposite result. A bare `each:` runs zero times; a bare `if:` runs until the time limit.

### do:

The `do:` key contains any number of [commands](/docs/automations/#commands) to repeat.

