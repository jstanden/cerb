---
id: "guides-automations-load-balanced-assignment"
title: "Load-balanced assignment"
url: "https://cerb.ai/guides/automations/load-balanced-assignment/"
summary: "This page provides a guide on implementing load-balanced assignment in Cerb using automations. It covers two approaches for distributing work fairly across team members. The counter-based round-robin approach uses a metric with the cycle function and works best for fixed worker lists. The least-loaded approach assigns to the worker with fewest open tickets and works best when availability changes dynamically."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Approach 1: Round-robin](#approach-1-round-robin)
  - [How it works](#how-it-works)
  - [Create the metric](#create-the-metric)
  - [The automation](#the-automation)
  - [Policy](#policy)

- [Approach 2: Least-loaded](#approach-2-least-loaded)
  - [How it works](#how-it-works-1)
  - [The automation](#the-automation-1)
  - [Policy](#policy-1)

- [Choosing an approach](#choosing-an-approach)
- [Worker selection examples](#worker-selection-examples)
  - [Workers active in the past 15 minutes](#workers-active-in-the-past-15-minutes)
  - [Workers available per calendar](#workers-available-per-calendar)
  - [Workers in a specific group](#workers-in-a-specific-group)
  - [Combining criteria](#combining-criteria)

- [Using the result](#using-the-result)
- [Next steps](#next-steps)

# Introduction

Load-balanced assignment distributes work fairly across team members. This guide covers two approaches:

1. **Round-robin** - Cycles through workers in order using a persistent counter
2. **Least-loaded** - Assigns to the worker with the fewest open tickets

Choose the approach that best fits your workflow based on whether your worker list is fixed or changes dynamically.

# Approach 1: Round-robin

Round-robin assignment cycles through a list of workers in order. Each new assignment goes to the next worker in the rotation.

This approach works best when:

- The worker list is fixed (e.g. all members of a group)
- You want strict rotation order
- Workers are expected to handle similar volumes

## How it works

1. **Query workers** - Get a list of worker IDs from a fixed pool
2. **Increment the counter** - Use a [metric](/docs/metrics/) to maintain a persistent counter
3. **Cycle through workers** - Pass the counter to [cycle()](/docs/scripting/functions/#cycle) to select the next worker

## Create the metric

First, create a custom metric to track assignment invocations.

Navigate to **Search&nbsp;» Metrics&nbsp;» (+)**.

Configure the metric:

| Field | Value |
| --- | --- |
| **Name** | `example.roundRobin.counter` |
| **Type** | `counter` |
| **Description** | Round-robin assignment counter |

Click **Save Changes**.

## The automation

```
start:
  # Get the list of workers from a fixed pool
  record.search:
    output: workers
    inputs:
      record_type: worker
      record_query: isDisabled:no group:(name:"Support") sort:id

  # Extract worker IDs into an array
  set/init:
    worker_ids@csv: {{workers|column('id')|join(',')}}

  # Increment the counter atomically (realtime)
  metric.increment:
    inputs:
      metric_name: example.roundRobin.counter
      values: 1
      is_realtime@bool: yes
    output: metric_result

  # Query the current counter value
  data.query:
    output: counter_data
    inputs:
      query@text:
        type:metrics.timeseries
        period:day
        range:today
        series.total:(
          metric:example.roundRobin.counter
          function:sum
        )
        format:dictionaries

  set:
    # Get the total count from the metric
    counter@int: {{counter_data.data|first.value|default(0)}}

  return:
    # Select the next worker using cycle
    assigned_worker_id@int: {{cycle(worker_ids, counter)}}
```

## Policy

```
commands:
  record.search:
    deny/type@bool: {{inputs.record_type is not record type ('worker')}}
    allow@bool: yes
  metric.increment:
    allow@bool: yes
  data.query:
    allow@bool: yes
```

# Approach 2: Least-loaded

Least-loaded assignment gives work to the worker with the fewest currently open tickets.

This approach works best when:

- Worker availability changes dynamically (on/off shift, busy, etc.)
- You want to balance current workload rather than historical assignments
- Some workers may handle tickets faster than others

## How it works

1. **Query available workers** - Get workers who are currently available
2. **Count open tickets** - Use [worklist.subtotals](/docs/data-queries/worklist/subtotals/) to count each worker's open tickets
3. **Select least loaded** - Assign to the worker with the minimum count

## The automation

```
start:
  # Get available workers
  record.search:
    output: workers
    inputs:
      record_type: worker
      record_query: isDisabled:no isAvailable:yes group:(name:"Support") sort:id
    on_success:
      outcome/empty:
        if@bool: {{0 == workers|length}}
        then:
          return:

  # Count open tickets per available worker
  data.query:
    output: assignment_counts
    inputs:
      query@text:
        type:worklist.subtotals
        of:ticket
        by:owner~1000
        query:(status:o owner.id:[{{workers|column('id')|join(',')}}])
        format:dictionaries

  # Build a sorted dictionary of workers with their open ticket counts
  set/loads:
    worker_loads@json:
      {% set counts = array_combine(
        assignment_counts.data|column('owner_id'),
        assignment_counts.data|column('count')) %}
      {{
        workers|map((worker) => 0+counts[worker.id] ?: 0)
        |sort
        |json_encode
      }}

  return:
    assigned_worker_id@int: {{worker_loads|keys|first}}
```

## Policy

```
commands:
  record.search:
    deny/type@bool: {{inputs.record_type is not record type ('worker')}}
    allow@bool: yes
  data.query:
    allow@bool: yes
```

# Choosing an approach

| Approach | Best for |
| --- | --- |
| **Round-robin** | Fixed worker lists, strict rotation order, equal distribution over time |
| **Least-loaded** | Dynamic availability, balanced current workload, varying worker capacity |

You can also combine approaches. For example, use round-robin for the initial assignment, then reassign based on least-loaded when a worker goes off shift.

# Worker selection examples

You can customize the worker query based on your requirements:

## Workers active in the past 15 minutes

Assign work only to workers who have been active recently:

```
record.search:
  output: workers
  inputs:
    record_type: worker
    record_query: isDisabled:no lastActivity:"-15 mins" sort:id
```

## Workers available per calendar

Assign work only to workers whose calendar shows them as available:

```
record.search:
  output: workers
  inputs:
    record_type: worker
    record_query: isDisabled:no isAvailable:yes sort:id
```

## Workers in a specific group

Assign work only to members of a specific group:

```
record.search:
  output: workers
  inputs:
    record_type: worker
    record_query: isDisabled:no group:(name:"Support") sort:id
```

## Combining criteria

You can combine multiple criteria:

```
record.search:
  output: workers
  inputs:
    record_type: worker
    record_query: isDisabled:no isAvailable:yes group:(name:"Support") lastActivity:"-30 mins" sort:id
```

# Using the result

After getting the assigned worker ID, you can use it to update a record:

```
record.update:
  inputs:
    record_type: ticket
    record_id: {{ticket_id}}
    fields:
      owner_id: {{assigned_worker_id}}
```

# Next steps

- Create separate metrics or queries for different assignment pools (e.g. Support, Sales)
- Add error handling for when no workers are available
- Combine with [automation timers](/docs/automations/#timers) for scheduled assignment
- Use [mail routing](/docs/mail/routing/) to trigger assignment on new tickets

