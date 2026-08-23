---
id: "docs-records-types-queuejob"
title: "Queue Job Records"
url: "https://cerb.ai/docs/records/types/queue_job/"
summary: "Queue jobs group together related queue messages so workers can monitor long-running background work -- bulk updates, file imports/exports, search re-indexing, and other parallel queue activity. Each job tracks progress counters, an optional singleton key, worker ownership, and arbitrary metadata. This page documents the Records API fields, dictionary placeholders, search filters, and worklist columns available on queue job records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Queue Job |
| **Name (plural):** | Queue Jobs |
| **Alias (uri):** | queue\_job |
| **Identifier (ID):** | cerb.contexts.queue.job |

- [Progress tracking](#progress-tracking)
- [Log](#log)
- [Cancellation](#cancellation)
- [Permissions](#permissions)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

A queue job groups a batch of [queue](/docs/queues/) messages under a single record so workers can monitor progress against long-running background work. Jobs are initiated by a worker (e.g. a bulk update, worklist export, search re-index) and complete when all of their linked messages have been processed. Opening a job's card displays a live progress widget until it finishes.

A `singleton_key` ensures that only one job with a given key can be active at a time – for example, search re-indexing for a particular index.

### Progress tracking

The job's `count_*` totals sum each message's **cardinality** (its work units) rather than counting raw messages. A message's cardinality defaults to `1`, so single-op messages behave the same as a simple count. Producers that bundle many records per message – such as `cerb.records.bulk_update`, `cerb.records.export`, and search re-indexing – can set a higher cardinality (typically `100`), and the monitor will report progress in record-equivalent units.

### Log

Each successful or failed queue message can write a summary entry into a permanent job log alongside the live counters. The log captures a short message plus arbitrary metadata – for instance, the affected `record_ids` on a [worklist](/docs/worklists/) bulk update – and survives after the job completes for an audit trail.

### Cancellation

A queue job can be **paused** (stop processing but keep pending messages), **canceled** (immediately remove pending messages and mark the job done), or **deleted** (remove the job and its log).

### Permissions

Only the worker who initiated a queue job and administrators can view the job's details or download attachments linked to it (e.g. an export's resulting file). Other workers can see the job exists but not its contents.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this queue job |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `name` | [text](/docs/records/fields/types/text/) | The name of this queue job |
| **x** | **`queue_id`** | [number](/docs/records/fields/types/number/) | The ID of the parent [queue](/docs/records/types/queue/) |
| &nbsp; | `singleton_key` | [text](/docs/records/fields/types/text/) | An optional unique key that prevents multiple jobs from running concurrently |
| &nbsp; | `status_id` | [number](/docs/records/fields/types/number/) | The status of the job: `0` running, `1` paused, `2` done |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `worker_id` | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) who initiated this job |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `count_available` | number | [Work units](#progress-tracking) still waiting to be processed |
| `count_done` | number | [Work units](#progress-tracking) that completed successfully |
| `count_failed` | number | [Work units](#progress-tracking) that failed |
| `count_inflight` | number | [Work units](#progress-tracking) currently being processed |
| `count_total` | number | Total [work units](#progress-tracking) in this job |
| `created_at` | date | Created |
| `id` | number | Id |
| `name` | text | Name |
| `queue_id` | number | Queue ID |
| `record_url` | text | Record URL |
| `singleton_key` | text | Singleton key |
| `status_id` | number | Status |
| `updated_at` | date | Updated |
| `worker_id` | number | Worker ID |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `queue_` | record | The parent [Queue](/docs/records/types/queue/) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |
| `worker_` | record | The [Worker](/docs/records/types/worker/) who initiated the job |

### Search Query Fields

These [filters](/docs/search/#filters) are available in queue job [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `count.available:` | [number](/docs/search/#numbers) | Available count |
| `count.done:` | [number](/docs/search/#numbers) | Done count |
| `count.failed:` | [number](/docs/search/#numbers) | Failed count |
| `count.inflight:` | [number](/docs/search/#numbers) | In-flight count |
| `count.total:` | [number](/docs/search/#numbers) | Total count |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `queue:` | [record](/docs/search/#deep-search) | [Queue](/docs/records/types/queue/) |
| `singleton.key:` | [text](/docs/search/#text) | Singleton Key |
| `status:` | [text](/docs/search/#text) | Status (`running`, `paused`, `done`) |
| `status.id:` | [number](/docs/search/#numbers) | Status ID |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on queue job [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `qj_count_available` | Available count |
| `qj_count_done` | Done count |
| `qj_count_failed` | Failed count |
| `qj_count_inflight` | In-flight count |
| `qj_count_total` | Total count |
| `qj_created_at` | Created |
| `qj_id` | Id |
| `qj_name` | Name |
| `qj_queue_id` | Queue |
| `qj_singleton_key` | Singleton Key |
| `qj_status_id` | Status |
| `qj_updated_at` | Updated |
| `qj_worker_id` | Worker |

[\< Record Types](/docs/records/types/)

