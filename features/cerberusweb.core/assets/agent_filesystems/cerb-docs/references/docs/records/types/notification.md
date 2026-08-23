---
id: "docs-records-types-notification"
title: "Notification Records"
url: "https://cerb.ai/docs/records/types/notification/"
summary: "This page provides detailed information about notification records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as activity points, creation timestamps, read status, and worker IDs. The page also describes the parameters for notifications, including customizable messages and variable URLs. Additionally, it covers dictionary placeholders for automations and API responses, search query fields for filtering notifications, and worklist columns for organizing notification data. This comprehensive guide is essential for understanding how notifications are managed and utilized within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Notification |
| **Name (plural):** | Notifications |
| **Alias (uri):** | notification |
| **Identifier (ID):** | cerberusweb.contexts.notification |

- [Records API](#records-api)
  - [params](#params)

- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`activity_point`** | [text](/docs/records/fields/types/text/) | The event that triggered the notification (or `custom.other`) |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `is_read` | [boolean](/docs/records/fields/types/boolean/) | Has this been read by the worker? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`params`** | [object](/docs/records/fields/types/object/) | A key/value object of notification properties |
| &nbsp; | `target__context` | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of the target record |
| &nbsp; | `target_id` | [number](/docs/records/fields/types/number/) | The ID of the target record |
| **x** | **`worker_id`** | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) who received the notification |

#### params

| Key | Value |
| --- | --- |
| `message` | The notification message with your own `{{variables}}` |
| `variables` | A key/value object of placeholder values |
| `urls` | A key/value object of optional variable urls in the format `ctx://record_type:123` |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `activity_point` | text | Activity |
| `assignee_` | record | [Assignee](/docs/records/types/worker/) |
| `created` | date | Created |
| `event_json` | text | Event Json |
| `id` | number | Id |
| `is_read` | boolean | Is Read |
| `message` | text | Message |
| `message_html` | text | Message (Html) |
| `target_` | record | Target |
| `url` | text | Url |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in notification [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `activity:` | [text](/docs/search/#text) | Activity |
| `created:` | [date](/docs/search/#dates) | Created |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isRead:` | [boolean](/docs/search/#booleans) | Is Read |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |
| `worker.id:` | [chooser](/docs/search/#choosers) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on notification [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `we_activity_point` | Activity |
| `we_created_date` | Created |
| `we_id` | Id |
| `we_is_read` | Is Read |
| `we_worker_id` | Worker |

[\< Record Types](/docs/records/types/)

