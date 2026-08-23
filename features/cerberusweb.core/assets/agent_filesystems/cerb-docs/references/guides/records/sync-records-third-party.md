---
id: "guides-records-sync-records-third-party"
title: "Synchronize record changes with third-party systems"
url: "https://cerb.ai/guides/records/sync-records-third-party/"
summary: "This guide explains how to reliably sync record changes from Cerb to external systems using cursor-based pagination that handles bulk updates correctly. It covers the challenge of syncing large batches of records that may share the same timestamp, and demonstrates a solution using dual-field sorting (updated + id) with persistent cursors. The approach uses storage.get and storage.set to maintain sync state between invocations, and automation timers for background processing. A complete example demonstrates syncing ticket records to a third-party system."
tags: ["guides"]
---
- [Introduction](#introduction)
- [The problem with timestamp-only cursors](#the-problem-with-timestamp-only-cursors)
- [The solution: Dual-field cursor pagination](#the-solution-dual-field-cursor-pagination)
- [Persisting sync state](#persisting-sync-state)
- [Running syncs in the background](#running-syncs-in-the-background)
- [Example: Syncing tickets to an external system](#example-syncing-tickets-to-an-external-system)
  - [Create the workflow](#create-the-workflow)
  - [Understanding the workflow](#understanding-the-workflow)
    - [Timer automation](#timer-automation)
    - [Function automation](#function-automation)

  - [Batch processing](#batch-processing)
  - [Enabling the timer](#enabling-the-timer)

- [Handling large initial syncs](#handling-large-initial-syncs)
- [Rate limiting](#rate-limiting)
- [Handling deleted records](#handling-deleted-records)
- [Next steps](#next-steps)

# Introduction

A common integration requirement is synchronizing record changes from Cerb to an external system like a data warehouse, CRM, or custom application. This involves periodically querying for records that have changed since the last sync and pushing those updates to the third-party system.

The naive approach is to track only the last `updated` timestamp and query for records newer than that value. However, this breaks down when many records share the same timestamp – a common occurrence during bulk imports, migrations, or batch updates. If more records share a timestamp than fit in a single page, the sync will skip records or get stuck in an infinite loop.

This guide demonstrates a reliable cursor-based pagination strategy that handles bulk updates correctly.

# The problem with timestamp-only cursors

Consider syncing tickets to an external system. You query for tickets with `updated:>2024-01-15T10:30:00 sort:updated limit:100` and process the results. You then save the `updated` timestamp of the last record as your cursor.

But what happens when an admin bulk-closes 500 tickets at 10:30:00? All 500 tickets share the same timestamp. Your first query returns 100 of them, and you save `2024-01-15T10:30:00` as the cursor. The next query with `updated:>2024-01-15T10:30:00` skips past the remaining 400 tickets because they have the exact same timestamp.

# The solution: Dual-field cursor pagination

The solution is to sort by both `updated` and `id`, and maintain a cursor with both values. The `id` field provides a stable tiebreaker within any timestamp.

The query pattern becomes:

```
(updated:>${last_updated} OR (updated:${last_updated} id:>${last_id})) sort:updated,id limit:100
```

This query returns records where:

- The `updated` timestamp is strictly greater than the cursor, OR
- The `updated` timestamp equals the cursor AND the `id` is greater than the cursor

The ascending sort on both fields ensures records are processed in a consistent order, and the dual cursor allows resumption at any point within a group of records sharing the same timestamp.

For **immutable records** that only need to be synced once (not on updates), you can use a simpler id-only cursor: id:\>${last\_id} sort:id limit:100. For example, ticket **message** records have immutable content -- once created, they never change. This simpler approach avoids the dual-field complexity when you only need to export new records.

# Persisting sync state

Use [storage.get](/docs/automations/commands/storage.get/) and [storage.set](/docs/automations/commands/storage.set/) to persist the cursor between sync invocations:

```
storage.get:
  output: sync_cursor
  inputs:
    key: sync.example.tickets.cursor
    default:
      updated@int: 0
      id@int: 0
```

After processing each batch, update the cursor:

```
storage.set:
  inputs:
    key: sync.example.tickets.cursor
    value:
      updated@int: {{last_record.updated}}
      id@int: {{last_record.id}}
```

# Running syncs in the background

For production use, run the sync on a schedule using an [automation timer](/docs/automations/#timers). This allows the sync to run continuously in the background without manual intervention.

A typical schedule might run every few minutes:

```
# Run every 5 minutes
*/5 * * * *
```

See the guide on [creating recurring tasks](/guides/automations/automation.timer/create-recurring-tasks/) for more details on automation timers.

# Example: Syncing tickets to an external system

Let's build a complete example that syncs ticket records to a third-party system. This demonstrates all the concepts: cursor pagination, state persistence, and background scheduling.

The example uses two automations:

1. **Timer automation**: Queries for changed records using cursor pagination and calls a function for each record.
2. **Function automation**: Receives a single record and handles the sync logic. This is where you implement your specific integration.

## Create the workflow

Navigate to **Search&nbsp;» Workflows** and click the **(+)** icon to create a new workflow.

Select **(Empty)** and click **Create & Continue**.

Paste the following [workflow](/docs/workflows/) template. Change occurrences of `example.syncTickets` to your own workflow identifier using a prefix based on a domain you own (e.g. `com.example.syncTickets`).

```
workflow:
  name: example.syncTickets
  version: 2026-02-04T01:28:17Z
  description: Sync ticket changes to a third-party system using cursor pagination
  requirements:
    cerb_version: >=11.0 <12.0
    cerb_plugins: cerberusweb.core,
records:
  automation/syncTicketFunction:
    fields:
      name: example.syncTicket.function
      extension_id: cerb.trigger.automation.function
      description: Sync a single ticket record to a third-party system
      script@raw:
        inputs:
          record/ticket:
            record_type: ticket
            required@bool: yes
        
        start:
          # Implement your sync logic here
          # For example, use http.request: to call an external API
          log: Syncing ticket #{{inputs.ticket.id}}: {{inputs.ticket._label}}
          
          return:
            synced@bool: yes
      policy_kata@raw:
        commands:
          # Add policies for http.request: etc
  automation/syncTickets:
    fields:
      name: example.syncTickets
      extension_id: cerb.trigger.automation.timer
      description: Sync ticket changes to a third-party system using cursor pagination
      script@raw:
        start:
          # Load the sync cursor from storage
          storage.get:
            output: sync_cursor
            inputs:
              key: sync.example.tickets.cursor
              default:
                updated@int: 0
                id@int: 0
          
          # Search for changed tickets using cursor pagination
          record.search:
            output: results
            inputs:
              record_type: ticket
              record_query@text:
                status:o
                (
                  updated:${last_sync_since}
                  OR (updated:${last_sync_at} id:>${last_sync_id})
                )
                sort:updated,id
                limit:10
              record_query_params:
                last_sync_since@int: {{sync_cursor.updated+1}}
                last_sync_at@int: {{sync_cursor.updated}} to {{sync_cursor.updated}}
                last_sync_id@int: {{sync_cursor.id}}
            on_success:
              # Process each ticket through the sync function
              repeat:
                each@key: results
                as: ticket
                do:
                  function:
                    uri: cerb:automation:example.syncTicket.function
                    inputs:
                      ticket: {{ticket.id}}
                      
                  # Track the last synced record for cursor update
                  set:
                    last_sync_ticket@key: ticket
                    
              # Only update cursor if we processed records
              decision:
                outcome/hasRecords:
                  if@bool: {{last_sync_ticket.id}}
                  then:
                    # Save the new cursor position
                    storage.set:
                      inputs:
                        key: sync.example.tickets.cursor
                        value:
                          updated@int: {{last_sync_ticket.updated}}
                          id@int: {{last_sync_ticket.id}}
          
          return:
            records_synced@int: {{results|length}}
      policy_kata@raw:
        commands:
          storage.get:
            allow@bool: yes
          storage.set:
            allow@bool: yes
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
          function:
            deny/uri@bool: {{uri is not prefixed ('cerb:automation:example.syncTicket.')}}
            allow@bool: yes
  automation_timer/syncTimer:
    fields:
      name: Sync tickets to third-party
      is_disabled@int: 1
      is_recurring@int: 1
      recurring_patterns@text:
        # Run every 5 minutes
        */5 * * * *
      recurring_timezone: UTC
      automations_kata@raw:
        automation/sync:
          uri: cerb:automation:example.syncTickets
```

Click the **Continue** button three times.

## Understanding the workflow

This [workflow](/docs/workflows/) creates three records: a function automation, a timer automation, and an automation timer. When you update the workflow template, changes are automatically synchronized to these records.

### Timer automation

The timer automation (`example.syncTickets`) follows this flow:

1. **Load cursor**: Retrieves the last sync position from storage, defaulting to `updated@int: 0` and `id@int: 0` for the initial sync.

2. **Search records**: Queries for tickets that have changed since the cursor position using the dual-field pagination pattern. The query uses `record_query_params:` to safely inject cursor values into the query.

3. **Process records**: Iterates through each ticket and calls the sync function.

4. **Update cursor**: After processing the batch, saves the new cursor position based on the last processed record.

### Function automation

The function automation (`example.syncTicket.function`) receives a single ticket as a [record input](/docs/automations/inputs/record/):

```
inputs:
  record/ticket:
    record_type: ticket
    required@bool: yes

start:
  # Implement your sync logic here
  # For example, use http.request: to call an external API
  log: Syncing ticket #{{inputs.ticket.id}}: {{inputs.ticket._label}}

  return:
    synced@bool: yes
```

Replace the `log:` command with your actual sync logic, such as an [http.request](/docs/automations/commands/http.request/) to call an external API.

## Batch processing

The example above processes one record at a time for simplicity. If your external API supports batch operations, you can modify the function to accept multiple records:

```
inputs:
  records/tickets:
    record_type: ticket
    required@bool: yes

start:
  # Process records in batch
  repeat:
    each@key: inputs.tickets
    as: ticket
    do:
      # Build batch payload...
```

Then call the function with batches of 5-25 records depending on your use case and the external API's limits.

## Enabling the timer

The automation timer is disabled by default. To enable it, edit the workflow and change `is_disabled@int: 1` to `is_disabled@int: 0` in the timer record's fields. Then save the workflow to synchronize the change.

# Handling large initial syncs

For the first sync of a large dataset, you may want to process records in smaller batches to avoid timeouts. The cursor pagination handles this automatically – each invocation picks up where the last one left off.

You can also adjust the `limit:` in the query to control batch size:

- Smaller batches (e.g., `limit:50`) are safer for slow external APIs
- Larger batches (e.g., `limit:500`) are more efficient for fast APIs

# Rate limiting

You can control sync frequency at multiple levels:

1. **Timer interval**: Adjust the cron expression in the automation timer. For example, `*/15 * * * *` runs every 15 minutes instead of every 5.

2. **Batch size**: Reduce the `limit:` in the search query to process fewer records per invocation.

3. **Monitoring with metrics**: Use the built-in [automation.invocations](/docs/metrics/automation.invocations/) metric to track how often your sync automations run and what exit states they produce. This helps identify if syncs are running too frequently or failing unexpectedly.

If the external API has strict rate limits, you can add delays between function calls or implement exponential backoff on errors within the sync function.

# Handling deleted records

The cursor pagination strategy syncs created and updated records, but not deleted records (which no longer exist to query).

For deleted records, consider:

1. **Soft deletes**: If the third-party system supports soft deletes, sync the ticket status when it changes to `deleted`.

2. **Full reconciliation**: Periodically sync all record IDs and remove any from the third-party system that no longer exist in Cerb.

3. **Record change events**: Use [record.changed](/docs/automations/triggers/record.changed/) automation events to push deletes in real-time.

# Next steps

- Modify the sync query to filter specific record types or statuses
- Add error handling for failed API calls with retry logic
- Implement rate limiting for external APIs with quotas
- Add logging or notifications for sync failures
- Create a dashboard widget to monitor sync status

