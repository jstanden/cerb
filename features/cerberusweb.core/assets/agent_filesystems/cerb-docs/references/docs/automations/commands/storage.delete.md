---
id: "docs-automations-commands-storage-delete"
title: "Automations: storage.delete"
url: "https://cerb.ai/docs/automations/commands/storage.delete/"
summary: "This page provides detailed information on the 'storage.delete' command used in Cerb automations to remove a value from long-term storage. It includes a practical example demonstrating how to set, delete, and attempt to retrieve a storage value, resulting in a null output after deletion. The page outlines the syntax for using the command, including required inputs such as the storage key, and optional outputs. It also describes the behavior of the command during simulation, success, and error states, offering placeholders for handling outcomes and error messages."
tags: ["docs", "docs-automations"]
---
The **storage.delete:** command removes a value from long-term storage.

```
start:
  storage.set:
    inputs:
      key: some.arbitrary.identifier
      value: This is the saved value.
  storage.delete:
    inputs:
      key: some.arbitrary.identifier
  storage.get:
    output: result
    inputs:
      key: some.arbitrary.identifier
    on_error:
      set:
        result@json: null
  return:
    output@key: result
```

Result:

```
output: (null)
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
| `key:` | **x** | The storage key to delete. This is an arbitrary identifier. |

## output:

The optional placeholder to store the result.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of deleting the storage key.

If omitted, the storage key is deleted during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The optional `output:` placeholder is set `true` if successful and `false` otherwise.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

