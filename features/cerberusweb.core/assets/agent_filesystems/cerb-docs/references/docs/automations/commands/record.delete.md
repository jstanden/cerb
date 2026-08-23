---
id: "docs-automations-commands-record-delete"
title: "Automations: record.delete"
url: "https://cerb.ai/docs/automations/commands/record.delete/"
summary: "This page provides detailed information on the 'record.delete' command used in Cerb automations. It explains the syntax and parameters required to delete a record of a specified type, such as 'task,' by providing the record type and ID. The page outlines the structure for inputs, outputs, and handling different scenarios like simulation, success, and error states. It describes how the output placeholder is used to store the dictionary of the deleted record or error messages, ensuring users can effectively manage record deletion within their automations."
tags: ["docs", "docs-automations"]
---
The **record.delete:** command deletes a record of the given type.

```
start:
  record.delete:
    output: results
    inputs:
      record_type: task
      record_id: 123
```

# Syntax

## inputs:

| Key | &nbsp; |
| --- | --- |
| `record_type:` | The [record type](/docs/records/types/) to delete. |
| `record_id:` | The ID of the given record type to delete. |

## output:

The dictionary of the deleted record will be saved to this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of deleting the record.

If omitted, the record is deleted during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder is set to the dictionary of the deleted record.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

