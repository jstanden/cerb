---
id: "docs-automations-triggers-record-bulkupdate"
title: "record.bulkUpdate"
url: "https://cerb.ai/docs/automations/triggers/record.bulkUpdate/"
summary: "This page documents the 'record.bulkUpdate' automation trigger in Cerb, which extends the Bulk Update popup on a worklist with custom actions. An automation on this trigger runs three times per job -- once at the start for setup, once for each batch of up to 100 record IDs, and once at the end for tear-down -- with a state input naming which phase it's in. The page covers those phases, the inputs each one receives, the setup dictionary a start run cascades to every later run, aborting a job from the start phase, the automation event listener that binds an automation to a record type and decides who can see the action, the custom inputs that appear as prompts in the popup, and the update permissions that still apply per record."
tags: ["docs", "docs-automations"]
---
**record.bulkUpdate** [automations](/docs/automations/) extend the **Bulk Update** popup on a [worklist](/docs/worklists/) with actions of your own.

A [bulk update](/docs/worklists/#bulk-update) runs as a background [queue job](/docs/records/types/queue_job/), so an automation here runs several times for one job rather than once.

This trigger uses [event handler](/docs/automations/#events) KATA, and all enabled automations are executed.

- [Phases](#phases)
- [Inputs](#inputs)
- [Outputs](#outputs)
  - [Cascading setup](#cascading-setup)
  - [Aborting a job](#aborting-a-job)

- [Event listener](#event-listener)
- [Permissions](#permissions)

# Phases

One bulk update runs an automation three ways, distinguished by its `state` input:

| `state` | When | Notes |
| --- | --- | --- |
| `start` | Once, before the queue job is enqueued | Setup. What it returns rides along to every later run. Exiting in the `error` state aborts the job before anything runs. |
| `batch` | Once per batch of record IDs, up to 100 at a time | The work itself. |
| `end` | Once, after the job completes | Tear-down. |

Only the `start` run can hand values to the others, and its `return:` is how. Whatever it returns under `setup:` is passed to every `batch` and `end` run of that automation as `setup.<key>` – a [queue job](/docs/records/types/queue_job/) ID, a container record it created, a timestamp every batch has to agree on, or anything else the batches need in common. See [cascading setup](#cascading-setup).

Each batch otherwise runs on its own. There's no shared state between them beyond what `start` returned, so a value every batch needs is either computed there or looked up again in each one.

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `state` | string | `start`, `batch`, or `end`. |
| `record_type` | string | The context of the records being updated (e.g. `cerb.contexts.ticket`). |
| `record_ids` | array | The record IDs in this batch. Empty on `start` and `end`. |
| `count` | number | The number of records in this batch, or the job total on `start` and `end`. |
| `worker_*` | record | The [worker](/docs/records/types/worker/) who started the job. Supports key expansion. |
| `worklist_id` | string | The originating worklist. |
| `worklist_query` | string | The query that produced the set of records. |
| `queue_job_*` | record | The [queue job](/docs/records/types/queue_job/) running this update. Only on `batch` and `end`. |
| `inputs.*` | dictionary | [Custom input](/docs/automations/#inputs) values the worker filled in on the popup. |
| `setup.*` | dictionary | The `setup:` dictionary returned by the `start` run. Empty on `start`. |

# Outputs

| Key | Type | Notes |
| --- | --- | --- |
| `error` | string | On `start`, aborts the job and shows this message to the worker. On `batch` and `end`, it's logged. |
| `setup` | dictionary | On `start`, a dictionary cascaded to every later run of the same automation as `setup.<key>`. |

## Cascading setup

Anything the `start` run needs to hand to the rest of the job goes in its `setup:` return. Every `batch` and `end` run of that automation reads it back as `setup.<key>`:

```
start:
  outcome/setup:
    if@bool: {{state == 'start'}}
    then:
      record.create/log:
        output: log_record
        inputs:
          record_type: comment
          fields:
            context: cerb.contexts.app
            comment: {{worker_name}} started a bulk update on {{count}} records
      return:
        setup:
          log_id: {{log_record.id}}
```

A later run reads `setup.log_id` without having to look it up again.

## Aborting a job

Exiting the `start` run in the `error` [state](/docs/automations/#exit-states) stops the job before it's enqueued, and the message is shown to the worker who started it:

```
start:
  outcome/tooMany:
    if@bool: {{state == 'start' and count > 1000}}
    then:
      error:
        message: Bulk updates are limited to 1,000 records at a time.
```

An `error` exit on a `batch` or `end` run is logged instead. By then the job is already running.

# Event listener

An automation on this trigger doesn't appear anywhere until an [automation event listener](/docs/automations/#events) binds it, under **Setup » Developers » Automation Events**. The listener names the automation and decides who sees the action and where:

```
automation/assignFollowup:
  uri: cerb:automation:example.bulk.followup
  disabled@bool: {{record_type is not record type ('ticket')}}
```

Every enabled listener becomes an entry on the popup's **Run Automation** menu, labeled with the [automation's](/docs/automations/) own description, or its name where it has no description. Picking one adds that automation's [inputs](/docs/automations/#inputs) to the form as prompts, so an action asks for the values it needs before the job starts.

The gates are evaluated while that menu is being built, with `state` set to `menu` rather than to one of the [phases](#phases) – the automation itself never runs in that state. An action can therefore be limited by record type, by the worklist it was started from, or by the worker running it. A worker never chooses an automation the listener didn't offer them either: the popup posts a handler name, and the set of valid handlers is resolved again on the server.

A binding can also fix an input itself:

```
automation/assignFollowup:
  uri: cerb:automation:example.bulk.followup
  inputs:
    due_in_days: 7
```

A fixed input is left out of the worker's form entirely and applied as an override at run time, so it wins over anything posted under the same key.

# Permissions

This trigger adds actions to a bulk update; it doesn't bypass one. Workers still need the appropriate update permission for every record they're acting on.

To perform the update itself from a script, see the [`records.update:`](/docs/automations/commands/records.update/) command.

