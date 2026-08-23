---
id: "docs-automations-commands-queue-pop"
title: "Automations: queue.pop"
url: "https://cerb.ai/docs/automations/commands/queue.pop/"
summary: "This page provides detailed documentation on the 'queue.pop' command used in Cerb automations to read messages from a specified queue. It outlines the syntax and parameters required for the command, including inputs like `queue_name` and `limit`, and describes how to handle outputs. The page also explains the optional sections for handling different scenarios: `on_simulate` for simulation commands, `on_success` for actions upon successful message retrieval, and `on_error` for error handling. Examples are provided to illustrate how the command can be implemented, including the structure of the output dictionary containing consumer IDs and message details."
tags: ["docs", "docs-automations"]
---
The **queue.pop:** command reads messages from a [queue](/docs/queues/).

```
start:
  queue.pop:
    inputs:
      queue_name: example.queue.name
      limit: 1
    output: results
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
| `queue_name:` | The [queue](/docs/queues/) name to read messages from |
| `limit:` | The maximum number of messages to retrieve at once |

## output:

Save the metrics result to this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of reading messages from the queue.

If omitted, messages are read from the queue during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `consumer_id` | The unique consumer ID key used to reserve the read messages. This is used to mark queue messages as successful or failed. |
| `messages` | An array of messages. The key is the unique queue message ID and the value is a dictionary with keys for `queue:` and `data:` |

For example:

```
results:
  consumer_id: 0x1ec8aee9cb856fd48e0a8d3225229102
  messages:
    1ec8aedf82d0642e858367a0b25a73aa:
      queue: example.queue.name
      data:
        id: message0
        priority: high
```

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

