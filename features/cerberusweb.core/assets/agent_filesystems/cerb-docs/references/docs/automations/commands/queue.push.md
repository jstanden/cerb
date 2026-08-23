---
id: "docs-automations-commands-queue-push"
title: "Automations: queue.push"
url: "https://cerb.ai/docs/automations/commands/queue.push/"
summary: "This page provides detailed information on the 'queue.push' command used in Cerb automations to add new messages to a queue. It outlines the syntax and parameters required for the command, including inputs like `queue_name` and `messages`, and optional settings such as `available_at@date` and `job_id`. The page also explains how to handle outputs, simulate the command, and manage success and error scenarios. It includes examples and descriptions of how to use placeholders for results and error handling, ensuring users can effectively implement and troubleshoot the queue.push command in their automation workflows."
tags: ["docs", "docs-automations"]
---
The **queue.push:** command adds new messages to a [queue](/docs/queues/).

```
start:
  queue.push:
    inputs:
      queue_name: example.queue.name
      messages:
        0:
          id: message0
          priority: high
        1:
          id: message1
          priority: low
    output: results
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [job\_id:](#job_id)

  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

# Syntax

## inputs:

| Key | Notes |
| --- | --- |
| `available_at@date:` | The optional future date to process the messages |
| `job_id:` | The optional [queue job](/docs/records/types/queue_job/) ID that these messages belong to |
| `messages:` | An array of messages to add. These can be strings or objects |
| `queue_name:` | The [queue](/docs/queues/) name to add messages to |

### job\_id:

Messages can be grouped into a [queue job](/docs/records/types/queue_job/) so that progress across the whole batch can be monitored as a single unit.

This key **associates messages with an existing job. It doesn't create one.** The value is stored on each message as given. If you push messages with a job ID that doesn't exist, they're still queued and processed normally, but there's no job record to track them with – and no progress monitor.

Create the [queue job](/docs/records/types/queue_job/) record first, then pass its `id`:

```
start:
  record.search:
    inputs:
      record_type: queue
      record_query: name:"example.queue.name" limit:1
    output: queue
    on_success:
      record.create:
        inputs:
          record_type: queue_job
          fields:
            name: Review historical tickets
            queue_id@int: {{queue.id}}
        output: job
        on_success:
          queue.push:
            inputs:
              queue_name: example.queue.name
              job_id@int: {{job.id}}
              messages:
                0:
                  id: message0
```

## output:

Save the queue push result to this placeholder.

This parameter is optional. It's only needed when you want to capture the resulting queue message IDs.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of pushing messages to the queue.

If omitted, messages are pushed to the queue during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder contains an array of unique queue message IDs. These can be reused to look up eventual message processing success or failure.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

