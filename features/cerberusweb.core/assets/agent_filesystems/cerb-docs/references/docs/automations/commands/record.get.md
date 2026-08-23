---
id: "docs-automations-commands-record-get"
title: "Automations: record.get"
url: "https://cerb.ai/docs/automations/commands/record.get/"
summary: "This page provides detailed information on the 'record.get' command used in Cerb automations to load a record based on a specified type and ID. It outlines the syntax and structure of the command, including the necessary inputs such as `record_type` and `record_id`, and the expected output. The page also explains the optional parameters like `on_simulate`, `on_success`, and `on_error`, which define the actions to take during simulation, upon successful execution, or in case of an error, respectively. The example provided demonstrates how to load a task record and format the output message."
tags: ["docs", "docs-automations"]
---
The **record.get:** command loads a record from a [type](/docs/records/types/) and ID.

```
start:
  record.get:
    output: record
    inputs:
      record_type: task
      record_id: 123
  return:
    output@text:
      Loaded {{record._context}} #{{record.id}}: {{record._label}}
```

```
output: Loaded cerberusweb.contexts.task #123: Install Cerb
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

| Key | &nbsp; |
| --- | --- |
| `record_type:` | The [record type](/docs/records/types/) to load. |
| `record_id:` | The ID of the given record type to load. |

## output:

Save the record dictionary to this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of loading the record.

If omitted, the record will be loaded during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The dictionary of the loaded record is saved to the `output:` placeholder.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

