---
id: "docs-automations-commands-function"
title: "Automations: function"
url: "https://cerb.ai/docs/automations/commands/function/"
summary: "This page provides detailed information on using the 'function' command in Cerb automations, specifically focusing on executing an automation function and returning an output dictionary. It includes an example of an automation function named `example.math.sum` that calculates the sum of numbers. The page outlines the syntax and components required to run an automation function, such as `uri`, `inputs`, `output`, `on_simulate`, `on_success`, and `on_error`. It explains how to define inputs, handle outputs, and manage success and error states within the automation process. The example provided demonstrates how to input a series of numbers and return their sum, showcasing the practical application of these commands."
tags: ["docs", "docs-automations"]
---
The **function:** command executes an [automation.function](/docs/automations/triggers/automation.function/) and returns an output dictionary.

With this automation function named `example.math.sum`:

```
inputs:
  array/numbers:
    required@bool: yes

start:
  return:
    sum@text,int:
      {{array_sum(inputs.numbers)}}
```

This automation uses the function:

```
start:
  function/sum:
    uri: cerb:automation:example.math.sum
    output: result
    inputs:
      numbers@csv: 2,4,8
    on_success:
      return:
        sum@key: result:sum
```

To return:

```
sum: 14
```

- [Syntax](#syntax)
  - [uri:](#uri)
  - [inputs:](#inputs)
  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## uri:

The [automation.function](/docs/automations/triggers/automation.function/) to run.

## inputs:

The inputs vary based on the automation function.

## output:

Save the results in this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of the function.

If omitted, the function is executed during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder receives the return dictionary from the function.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

