---
id: "docs-automations-commands-records-update"
title: "Automations: records.update"
url: "https://cerb.ai/docs/automations/commands/records.update/"
summary: "This page documents the 'records.update' command in Cerb automations, which updates a batch of records of one type in a single action rather than one at a time in a loop. It covers the inputs -- the record type, the list of record IDs, the fields to set, and the optional disable_events toggle -- along with the output dictionary, the on_simulate, on_success, and on_error branches, and the error output. It also explains why fields that must be unique can't be bulk updated, that the command requires its own automation policy grant rather than inheriting one from record.update, and that workers still need update permission on every target record."
tags: ["docs", "docs-automations"]
---
The **records.update:** command updates a batch of records of the same type with the given fields.

```
start:
  records.update:
    output: updated_records
    inputs:
      record_type: task
      record_ids:
        - 123
        - 456
        - 789
      # See: https://cerb.ai/docs/records/types/task/#records-api
      fields:
        importance: 90
```

Every record gets the same values. Use [`record.update:`](/docs/automations/commands/record.update/) in a [`repeat:`](/docs/automations/commands/repeat/) loop when each record needs different ones.

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [Unique fields](#unique-fields)

  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

- [Policies](#policies)
- [Extending bulk updates](#extending-bulk-updates)

# Syntax

## inputs:

| Key | &nbsp; |
| --- | --- |
| `record_type:` | The [record type](/docs/records/types/) to update. |
| `record_ids:` | A list of record IDs of that type. |
| `fields:` | The [fields](/docs/records/#fields) to set on every record, based on the record type. |
| `disable_events@bool:` | Avoid triggering events for modified records. |

A batch of one ID behaves exactly like a single-record update.

An ID that doesn't exist is a harmless no-op. The records aren't loaded first – that's what makes this one statement rather than a loop – so a missing record isn't an error.

### Unique fields

Fields that have to be unique across a record type can't be bulk updated, since the same value can't be unique on many rows at once. That covers a `uri`, an [org](/docs/records/types/org/) or [automation](/docs/automations/) `name`, an [address](/docs/records/types/address/) `email`, and anything else the record type validates as unique.

These fields are rejected rather than silently skipped, and they're left out of the editor's autocomplete suggestions for `fields:`.

## output:

Save a summary of the batch to this placeholder.

| Key | &nbsp; |
| --- | --- |
| `record_type` | The context ID of the updated record type. |
| `record_ids` | The list of IDs that were updated. |
| `count` | How many IDs were in the batch. |

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of updating the records.

If omitted, the records are updated during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

# Policies

An automation [policy](/docs/automations/#policies) has to allow `records.update` by name. There's no fallback to a `record.update` grant – an automation permitted to update one record at a time isn't thereby permitted to rewrite a thousand:

```
commands:
  records.update:
    deny/tasksOnly@bool: {{inputs.record_type is not record type ('task')}}
    allow@bool: yes
```

Workers still need the appropriate update permission for every target record, exactly as they would updating them one at a time.

# Extending bulk updates

The [`record.bulkUpdate`](/docs/automations/triggers/record.bulkUpdate/) trigger is the other half of this: it adds custom actions to the **Bulk Update** popup on a [worklist](/docs/worklists/), and runs an automation for each batch of records the worker selected.

