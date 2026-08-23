---
id: "docs-automations-commands-storage-set"
title: "Automations: storage.set"
url: "https://cerb.ai/docs/automations/commands/storage.set/"
summary: "This page provides detailed information on the 'storage.set' command used in Cerb automations to save a value to long-term storage. It explains how this key/value pair can be shared between different automations and invocations. The page outlines the syntax for using 'storage.set,' including required inputs such as the storage key and value, and optional parameters like expiration time. It also describes the optional outputs and the commands to execute during simulation, on success, and on error. The page includes an example of how to use the command and the expected result, demonstrating the process of setting and retrieving a stored value."
tags: ["docs", "docs-automations"]
---
The **storage.set:** command saves a value to long-term storage. This key/value can be shared between automations and invocations.

```
start:
  storage.set:
    inputs:
      key: some.arbitrary.identifier
      value: This is the saved value.
      expires: +15 mins
  storage.get:
    output: result
    inputs:
      key: some.arbitrary.identifier
  return:
    output@key: result
```

Result:

```
output: This is the saved value.
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
| `key:` | **x** | The storage key to save. This is an arbitrary identifier. |
| `value:` | **x** | The value to for the storage key. |
| `expires:` | &nbsp; | The optional date/time to expire the key (e.g. `+2 hours`, `31 Dec 2036`). Omit to not expire. |

## output:

The optional placeholder to store the result.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of saving the storage key.

If omitted, the storage key is set during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The optional `output:` placeholder is set to a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `key` | The key which was set. |
| `expires` | The optional expiration of the key. |

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

