---
id: "docs-records-types-reminder"
title: "Reminder Records"
url: "https://cerb.ai/docs/records/types/reminder/"
summary: "This page provides detailed information about Reminder Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `name`, `remind_at`, and `worker_id`, and explains how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. The page also describes the search query fields that can be used to filter reminders, such as `closed`, `name`, and `worker`, and lists the worklist columns available for displaying reminder data, including custom fields and status indicators like `is_closed` and `remind_at`. This comprehensive guide is essential for managing and integrating reminder functionalities within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Reminder |
| **Name (plural):** | Reminders |
| **Alias (uri):** | reminder |
| **Identifier (ID):** | cerberusweb.contexts.reminder |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `is_closed` | [boolean](/docs/records/fields/types/boolean/) | Has this reminder elapsed? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this reminder |
| **x** | **`remind_at`** | [timestamp](/docs/records/fields/types/timestamp/) | The date/time of the reminder |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| **x** | **`worker_id`** | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) receiving the reminder |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
| `is_closed` | boolean | Is Closed |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `remind_at` | date | Remind At |
| `updated_at` | date | Updated |
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

These [filters](/docs/search/#filters) are available in reminder [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `closed:` | [boolean](/docs/search/#booleans) | Is Closed |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `remindAt:` | [date](/docs/search/#dates) | Remind At |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |
| `worker.id:` | [chooser](/docs/search/#choosers) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on reminder [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `r_id` | Id |
| `r_is_closed` | Is Closed |
| `r_name` | Name |
| `r_remind_at` | Remind At |
| `r_updated_at` | Updated |
| `r_worker_id` | Worker |

[\< Record Types](/docs/records/types/)

