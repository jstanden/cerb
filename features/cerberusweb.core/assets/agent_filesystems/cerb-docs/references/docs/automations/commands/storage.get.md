---
id: "docs-automations-commands-storage-get"
title: "Automations: storage.get"
url: "https://cerb.ai/docs/automations/commands/storage.get/"
summary: "This page provides detailed information on the 'storage.get' command used in Cerb automations to retrieve previously saved values from long-term storage. It explains how this key/value system can be shared between different automations and invocations. The page outlines the syntax for using 'storage.get,' including required and optional inputs such as the storage key and default value, as well as placeholders for output. It also describes the commands to execute during simulation, on success, and on error, providing a comprehensive guide for implementing this command effectively in automation workflows."
tags: ["docs", "docs-automations"]
---
The **storage.get:** command retrieves a previously saved value from long-term storage. This key/value can be shared between automations and invocations.

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
      default: This is a default value.
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
| `key:` | **x** | The storage key to load. This is an arbitrary identifier. |
| `default:` | &nbsp; | A default value to return when the storage key doesn't exist. |

## output:

The optional placeholder to store the result.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of fetching the storage key.

If omitted, the storage key is fetched during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The optional `output:` placeholder is set to the value of the storage key.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

