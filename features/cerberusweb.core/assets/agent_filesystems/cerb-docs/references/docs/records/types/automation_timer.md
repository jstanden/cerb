---
id: "docs-records-types-automationtimer"
title: "Automation Timer Records"
url: "https://cerb.ai/docs/records/types/automation_timer/"
summary: "This page provides detailed information about Automation Timer records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and attributes of Automation Timer records, such as creation and update timestamps, recurring patterns, and links. The page also describes how these records can be queried and displayed in worklists, offering a comprehensive guide for managing and utilizing Automation Timers within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Automation Timer |
| **Name (plural):** | Automation Timers |
| **Alias (uri):** | automation\_timer |
| **Identifier (ID):** | cerb.contexts.automation.timer |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `automations_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | &nbsp; |
| &nbsp; | `is_recurring` | [boolean](/docs/records/fields/types/boolean/) | &nbsp; |
| &nbsp; | `last_ran_at` | [timestamp](/docs/records/fields/types/timestamp/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this automation timer |
| &nbsp; | `next_run_at` | [timestamp](/docs/records/fields/types/timestamp/) | &nbsp; |
| &nbsp; | `recurring_patterns` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `recurring_timezone` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `id` | number | Id |
| `is_disabled` | boolean | Disabled |
| `is_recurring` | boolean | Is Recurring |
| `last_ran_at` | date | Last Ran At |
| `name` | text | Name |
| `next_run_at` | date | Next Run At |
| `record_url` | text | Record Url |
| `recurring_patterns` | text | Recurring Patterns |
| `recurring_timezone` | text | Timezone |
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

These [filters](/docs/search/#filters) are available in automation timer [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isRecurring:` | [boolean](/docs/search/#booleans) | Is Recurring |
| `lastRanAt:` | [date](/docs/search/#dates) | Last Ran At |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `nextRunAt:` | [date](/docs/search/#dates) | Next Run At |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on automation timer [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `a_created_at` | Created |
| `a_id` | Id |
| `a_is_disabled` | Disabled |
| `a_is_recurring` | Is Recurring |
| `a_last_ran_at` | Last Ran At |
| `a_name` | Name |
| `a_next_run_at` | Next Run At |
| `a_recurring_patterns` | Recurring Patterns |
| `a_recurring_timezone` | Timezone |
| `a_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

