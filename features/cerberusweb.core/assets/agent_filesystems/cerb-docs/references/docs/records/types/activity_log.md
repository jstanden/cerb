---
id: "docs-records-types-activitylog"
title: "Activity Log Records"
url: "https://cerb.ai/docs/records/types/activity_log/"
summary: "This page provides detailed information about the Activity Log records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `activity_point`, `actor__context`, and `target__context`, and explains their types and requirements. The page also describes the parameters for logging messages, including placeholders and variable URLs. Additionally, it covers dictionary placeholders for automations and API responses, search query fields for filtering activity logs, and worklist columns for organizing and displaying log data. This comprehensive guide is essential for understanding how to manage and utilize activity logs within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Activity Log |
| **Name (plural):** | Activity Logs |
| **Alias (uri):** | activity\_log |
| **Identifier (ID):** | cerberusweb.contexts.activity\_log |

- [Records API](#records-api)
  - [params](#params)

- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`activity_point`** | [text](/docs/records/fields/types/text/) | The event ID that occurred (or `custom.other`) |
| **x** | **`actor__context`** | [context](/docs/records/fields/types/context/) | The actor's record type |
| **x** | **`actor_id`** | [number](/docs/records/fields/types/number/) | The actor's record ID |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| **x** | **`target__context`** | [context](/docs/records/fields/types/context/) | The target's record type |
| **x** | **`target_id`** | [number](/docs/records/fields/types/number/) | The target's record ID |

#### params

| Key | Value |
| --- | --- |
| `message` | The log message with your own `{{variables}}` |
| `variables` | A key/value object of placeholder values |
| `urls` | A key/value object of optional variable urls in the format `ctx://record_type:123` |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `activity_point` | text | Event Id |
| `actor_` | record | Actor |
| `created` | date | Created |
| `event` | text | Event |
| `id` | number | Id |
| `target_` | record | Target |

### Search Query Fields

These [filters](/docs/search/#filters) are available in activity log [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `activity:` | [text](/docs/search/#text) | Activity |
| `actor:` | [text](/docs/search/#text) | Actor Type |
| `actor.<type>:` | [record](/docs/search/#deep-search) | Actor |
| `created:` | [date](/docs/search/#dates) | Created |
| `id:` | [number](/docs/search/#numbers) | Id |
| `target:` | [text](/docs/search/#text) | Target Type |
| `target.<type>:` | [record](/docs/search/#deep-search) | Target |

### Worklist Columns

These columns are available on activity log [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_actor` | Actor |
| `*_target` | Target |
| `c_activity_point` | Activity |
| `c_actor_context` | Actor Context |
| `c_created` | Created |
| `c_entry_json` | Entry |
| `c_id` | Id |
| `c_target_context` | Target Context |

[\< Record Types](/docs/records/types/)

