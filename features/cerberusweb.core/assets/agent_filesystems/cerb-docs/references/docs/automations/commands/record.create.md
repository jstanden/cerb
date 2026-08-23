---
id: "docs-automations-commands-record-create"
title: "Automations: record.create"
url: "https://cerb.ai/docs/automations/commands/record.create/"
summary: "This page provides detailed instructions on using the `record.create` command in Cerb to create records with specified fields. It outlines the syntax and parameters required, including `inputs`, `output`, `on_simulate`, `on_success`, and `on_error`. The `inputs` section specifies the record type and fields to set, with options to expand keys and disable events for new records. The `output` section describes how to save the record dictionary, while `on_simulate`, `on_success`, and `on_error` sections detail the commands to execute during simulation, upon success, and in case of errors, respectively. This guide is essential for automating record creation in Cerb workflows."
tags: ["docs", "docs-automations"]
---
The **record.create:** command creates a record with the given fields.

```
start:
  record.create/newTask:
    output: new_task
    inputs:
      # See: https://cerb.ai/docs/records/types/task/#records-api
      record_type: task
      fields:
        title: This is a new task
        status: open
        importance: 50
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
| `record_type:` | The [record type](/docs/records/types/) to create. |
| `fields:` | The [fields](/docs/records/#fields) to set based on the record type. |
| `expand:` | The keys to expand on the `output:` dictionary. |
| `disable_events@bool:` | Avoid triggering events for newly created records. This is particularly useful for import workflows where events like auto-responders and auto-assignment shouldn't be triggered. |

## output:

Save the record dictionary to this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of creating the record.

If omitted, the record is created during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder is a dictionary based on the record type.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

