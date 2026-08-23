---
id: "docs-records-types-queue"
title: "Queue Records"
url: "https://cerb.ai/docs/records/types/queue/"
summary: "This page provides detailed information about Queue Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as creation and update timestamps, links, the queue name, and the retry and claim window settings that govern how failed and stalled messages are handled. The page also describes dictionary placeholders used in automations, snippets, and API responses, offering a range of fields like context, label, and record URL. Additionally, it covers search query fields that can be used to filter queue records based on criteria like creation date, fieldset, and watchers. Lastly, it lists the worklist columns available for displaying queue records, including custom fields and standard attributes like ID, name, and timestamps."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Queue |
| **Name (plural):** | Queues |
| **Alias (uri):** | queue |
| **Identifier (ID):** | cerb.contexts.queue |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Queue [worklists](/docs/worklists/) offer a 'Activity' [sparklines column](/docs/worklists/#sparkline-columns) charting done, failed, and open messages, with a 2h/1d/30d range toggle. A matching `activity:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `activity:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `claim_window_secs` | [number](/docs/records/fields/types/number/) | The number of seconds before a stalled `in_flight` message is [reclaimed](/docs/queues/#claims). Default: `3600` (1 hour). Use `0` to never reclaim. |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | The ID of the [consumer extension](/docs/queues/#consumer-extensions) that routes messages from this queue (e.g. `cerb.queue.consumer.internal`) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this queue |
| &nbsp; | `retry_max` | [number](/docs/records/fields/types/number/) | The maximum number of [retries](/docs/queues/#retries) for a failed message (`0` to `16`). Default: `0` (never retry). |
| &nbsp; | `retry_window_secs` | [number](/docs/records/fields/types/number/) | The total number of seconds that [retries](/docs/queues/#retries) are spread across. Default: `86400` (24 hours). |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `claim_window_secs` | number | [Claim window](/docs/queues/#claims) in seconds |
| `created_at` | date | Created |
| `extension_id` | text | [Consumer extension](/docs/queues/#consumer-extensions) |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `retry_max` | number | Maximum [retries](/docs/queues/#retries) |
| `retry_window_secs` | number | [Retry window](/docs/queues/#retries) in seconds |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in queue [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `claim.window:` | [number](/docs/search/#numbers) | [Claim window](/docs/queues/#claims) in seconds. Accepts durations (e.g. `claim.window:>1h`, `claim.window:0`) |
| `created:` | [date](/docs/search/#dates) | Created |
| `extension:` | [text](/docs/search/#text) | [Consumer extension](/docs/queues/#consumer-extensions) ID |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `retry.max:` | [number](/docs/search/#numbers) | Maximum [retries](/docs/queues/#retries) (e.g. `retry.max:0`, `retry.max:>0`) |
| `retry.window:` | [number](/docs/search/#numbers) | [Retry window](/docs/queues/#retries) in seconds. Accepts durations (e.g. `retry.window:<1d`, `retry.window:>30m`) |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on queue [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `q_claim_window_secs` | Claim window |
| `q_created_at` | Created |
| `q_extension_id` | Consumer extension |
| `q_id` | Id |
| `q_name` | Name |
| `q_retry_max` | Retry max |
| `q_retry_window_secs` | Retry window |
| `q_updated_at` | Updated |

`q_retry_max` and `q_retry_window_secs` are part of the default column set. `q_claim_window_secs` is available but isn't displayed by default – add it from the column picker.

[\< Record Types](/docs/records/types/)

