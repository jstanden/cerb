---
id: "docs-automations-commands-var-set"
title: "Automations: var.set"
url: "https://cerb.ai/docs/automations/commands/var.set/"
summary: "This page provides detailed information on the 'var.set' command used in Cerb automations, which allows setting a value using a specified key path. It includes a practical example demonstrating how to set and retrieve values using key paths, resulting in the output 'Kina Halpue.' The page outlines the syntax for the command, including required and optional inputs such as key, value, and delimiter. It also describes the optional sections for handling output, simulation, success, and error scenarios, providing a comprehensive guide for implementing the 'var.set' command effectively in automation workflows."
tags: ["docs", "docs-automations"]
---
The **var.set:** command sets a value using a key path.

```
start:
  set:
    person:
      name:
        first: Kina
  var.set:
    inputs:
      key: person:name:last
      value: Halpue
  return:
    output@text:
      {{person.name.first}} {{person.name.last}}
```

Result:

```
output: Kina Halpue
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

| Key | Req'd | &nbsp; |
| --- | --- | --- |
| `key:` | **x** | The [key path](/docs/automations/#dictionaries) of the value to set, delimited with colons (`:`). |
| `value:` | **x** | The value to set. |
| `delimiter:` | &nbsp; | An optional delimiter to use in `key:` paths. |

## output:

The optional placeholder to store the result.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of setting the value.

If omitted, the value is set during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The optional `output:` placeholder is set to the new value.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

