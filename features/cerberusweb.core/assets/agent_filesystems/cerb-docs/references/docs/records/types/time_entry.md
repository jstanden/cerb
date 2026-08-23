---
id: "docs-records-types-timeentry"
title: "Time Tracking Records"
url: "https://cerb.ai/docs/records/types/time_entry/"
summary: "This page provides detailed information about time tracking records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing time tracking entries, such as activity ID, log date, and worker ID. The page also describes dictionary placeholders used in automations and API responses, offering a range of fields like record type, log date, and time spent. Additionally, it covers search query fields that facilitate filtering time tracking data based on various criteria, such as activity, comments, and worker details. Lastly, it lists the worklist columns available for displaying time tracking information, including custom fields, activity, log date, and time spent. This comprehensive guide is crucial for users looking to effectively manage and query time tracking data within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Time Tracking Entry |
| **Name (plural):** | Time Tracking Entries |
| **Alias (uri):** | time\_entry |
| **Identifier (ID):** | cerberusweb.contexts.timetracking |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `activity_id` | [number](/docs/records/fields/types/number/) | The ID of the [activity](/docs/records/types/timetracking_activity/) for the work |
| &nbsp; | `is_closed` | [boolean](/docs/records/fields/types/boolean/) | Is this time entry archived? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `log_date` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time of the work |
| **x** | **`mins`** | [number](/docs/records/fields/types/number/) | The number of minutes worked (alternative to `secs`) |
| **x** | **`secs`** | [number](/docs/records/fields/types/number/) | The number of seconds worked (alternative to `mins`) |
| **x** | **`worker_id`** | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) who completed the work |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
| `is_closed` | boolean | Is Closed |
| `log_date` | date | Log Date |
| `mins` | minutes | Time Spent |
| `record_url` | text | Record Url |
| `summary` | text | Summary |
| `worker_` | record | [Worker](/docs/records/types/worker/) |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in time tracking [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `activity.id:` | [chooser](/docs/search/#choosers) | [Activity](/docs/records/types/timetracking_activity/) |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Log Date |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isClosed:` | [boolean](/docs/search/#booleans) | Is Closed |
| `links:` | [links](/docs/search/#links) | Record Links |
| `timeSpent:` | [number](/docs/search/#numbers) | Time Spent |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |
| `worker.id:` | [chooser](/docs/search/#choosers) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on time tracking [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `tt_activity_id` | Activity |
| `tt_is_closed` | Is Closed |
| `tt_log_date` | Log Date |
| `tt_time_actual_mins` | Time Spent |
| `tt_time_actual_secs` | Time Spent |
| `tt_worker_id` | Worker |

[\< Record Types](/docs/records/types/)

