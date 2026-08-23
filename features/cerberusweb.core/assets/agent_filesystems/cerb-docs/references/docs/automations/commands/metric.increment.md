---
id: "docs-automations-commands-metric-increment"
title: "Automations: metric.increment"
url: "https://cerb.ai/docs/automations/commands/metric.increment/"
summary: "This page provides detailed information on the `metric.increment` command used in Cerb automations to add new samples to a metric. It outlines the syntax and parameters required for the command, including `inputs`, `output`, `on_simulate`, `on_success`, and `on_error`. The `inputs` section specifies keys such as `metric_name`, `dimensions`, `values`, `is_realtime`, and `timestamp`, explaining their roles in defining the metric's characteristics and behavior. The `output` section describes how to store the result of the metric operation, while `on_simulate`, `on_success`, and `on_error` sections detail the commands to execute during different stages of the automation process, including handling errors and successful executions."
tags: ["docs", "docs-automations"]
---
The **metric.increment:** command adds new samples to a [metric](/docs/metrics/).

```
start:
  metric.increment:
    inputs:
      metric_name: example.workerLogin.fails
      dimensions:
        worker@int: {{worker_id}}
        ip: {{client_ip}}
      values: 1
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
| `metric_name:` | The [metric](/docs/metrics/) name to add samples to |
| `dimensions:` | An optional key/value dictionary for [dimensions](/docs/metrics/#dimensions) |
| `values:` | A number, or array of numbers, for each sample. This defaults to `1` for counters if omitted |
| `is_realtime@bool:` | If `true` the metric is updated instantly, otherwise (by default) it's queued |
| `timestamp@date:` | The optional timestamp for retroactive metrics. This defaults to `now` |

## output:

Save the metrics result to this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of updating the metric.

If omitted, the metric is updated during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder contains an empty dictionary on success.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

