---
id: "docs-automations-commands-decision"
title: "Automations: decision"
url: "https://cerb.ai/docs/automations/commands/decision/"
summary: "This page provides an overview of the 'decision' command in Cerb automations, which is used to conditionally select one of multiple potential outcomes based on specified conditions. It explains the syntax and structure of the decision command, highlighting the use of multiple 'outcome' commands, each with a unique name. The page details how each outcome is evaluated using the `if@bool:` key to determine if it is desirable, and the `then:` key to specify the commands to execute if the outcome matches. An example is provided to illustrate how the decision command can differentiate between weekdays and weekends."
tags: ["docs", "docs-automations"]
---
The **decision:** command conditionally selects one of multiple potential outcomes.

# Syntax

```
start:
  decision:
    outcome/weekend:
      if@bool: {{'now'|date('l') in ['Saturday','Sunday'] ? 'yes'}}
      then:
        return:
          output: It is the weekend.
    outcome/weekday:
      then:
        return:
          output: It is a weekday.
```

## outcome:

A decision has multiple `outcome:` commands. The first matching outcome is used.

Each outcome must have a unique name.

| Key | &nbsp; |
| --- | --- |
| `if@bool:` | This key should resolve to a `yes` or `no` value to determine whether this outcome is desirable. |
| `then:` | If this outcome matches, the [commands](/docs/automations/#commands) to run. |

