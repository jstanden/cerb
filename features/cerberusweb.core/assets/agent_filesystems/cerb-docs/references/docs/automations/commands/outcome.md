---
id: "docs-automations-commands-outcome"
title: "Automations: outcome"
url: "https://cerb.ai/docs/automations/commands/outcome/"
summary: "This page provides an overview of the 'outcome' command in Cerb automations, which allows for conditional execution of a sequence of commands. Each outcome must have a unique name and is determined by a boolean condition specified with the `if@bool:` key. If the condition resolves to 'yes,' the commands under the `then:` key are executed. The page includes syntax examples, such as checking if the current day is a weekend, and explains that multiple outcomes can be grouped, with the first matching outcome being executed."
tags: ["docs", "docs-automations"]
---
The **outcome:** command makes a sequence of commands conditional. Each outcome must have a unique name.

| Key | &nbsp; |
| --- | --- |
| `if@bool:` | This key should resolve to a `yes` or `no` value to determine whether this outcome is desirable. |
| `then:` | If this outcome matches, the [commands](/docs/automations/#commands) to run. |

# Syntax

```
start:
  outcome/weekend:
    if@bool: {{'now'|date('l') in ['Saturday','Sunday']}}
    then:
      return:
        output: It is the weekend.
```

Multiple outcomes can be grouped in a [decision](/docs/automations/commands/decision/) command. The first matching outcome is used.

