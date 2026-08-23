---
id: "docs-records-types-task"
title: "Task Records"
url: "https://cerb.ai/docs/records/types/task/"
summary: "This page provides detailed information about task records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as timestamps for creation, completion, and deadlines, as well as fields for task importance, ownership, and status. The page also describes dictionary placeholders for automations and API responses, offering a range of fields like task title, status, and owner. Additionally, it covers search query fields that allow filtering tasks based on various criteria, and it lists worklist columns that can be used to display task information in a structured format. This comprehensive guide is essential for users looking to manage and automate tasks effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Task |
| **Name (plural):** | Tasks |
| **Alias (uri):** | task |
| **Identifier (ID):** | cerberusweb.contexts.task |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `completed` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time this task was completed |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `due` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time of this task's deadline |
| &nbsp; | `fieldsets` | fieldsets | An array or comma-separated list of [custom fieldset](/docs/records/types/custom_fieldset/) IDs. Prefix an ID with `-` to remove. |
| &nbsp; | `importance` | [number](/docs/records/fields/types/number/) | A number from `0` (least) to `100` (most) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `owner_id` | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) responsible for this task |
| &nbsp; | `reopen` | [timestamp](/docs/records/fields/types/timestamp/) | If the status is `waiting`, the date/time to automatically change the status back to `open` |
| &nbsp; | `status` | [text](/docs/records/fields/types/text/) | `o` (open), `w` (waiting), `c` (closed); alternative to `status_id` |
| &nbsp; | `status_id` | [number](/docs/records/fields/types/number/) | `0` (open), `1` (closed), `2` (waiting); alternative to `status` |
| &nbsp; | `is_active` | [boolean](/docs/records/fields/types/boolean/) | Is this task actively being worked on? (`0` or `1`) |
| &nbsp; | `project_id` | [number](/docs/records/fields/types/number/) | The ID of the parent [task project](/docs/records/types/task_project/). |
| **x** | **`title`** | [text](/docs/records/fields/types/text/) | The name of this task. May contain 4-byte emoji. |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `completed` | date | Completed Date |
| `created` | date | Created |
| `due` | date | Due Date |
| `id` | number | Id |
| `importance` | number | Importance |
| `owner_` | record | [Owner](/docs/records/types/worker/) |
| `record_url` | text | Record Url |
| `reopen` | date | Reopen At |
| `status` | text | Status |
| `title` | text | Title |
| `updated` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in task [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `completed:` | [date](/docs/search/#dates) | Completed Date |
| `created:` | [date](/docs/search/#dates) | Created |
| `due:` | [date](/docs/search/#dates) | Due Date |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `importance:` | [number](/docs/search/#numbers) | Importance |
| `links:` | [links](/docs/search/#links) | Record Links |
| `owner:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/worker/) |
| `owner.id:` | [chooser](/docs/search/#choosers) | [Owner](/docs/records/types/worker/) |
| `reopen:` | [date](/docs/search/#dates) | Reopen At |
| `status:` | virtual | Status |
| `title:` | [text](/docs/search/#text) | Title |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on task [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `t_completed_date` | Completed Date |
| `t_created_at` | Created |
| `t_due_date` | Due Date |
| `t_importance` | Importance |
| `t_owner_id` | Owner |
| `t_reopen_at` | Reopen At |
| `t_status_id` | Status |
| `t_title` | Title |
| `t_updated_date` | Updated |

[\< Record Types](/docs/records/types/)

