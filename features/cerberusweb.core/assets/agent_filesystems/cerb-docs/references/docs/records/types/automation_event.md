---
id: "docs-records-types-automationevent"
title: "Automation Event Records"
url: "https://cerb.ai/docs/records/types/automation_event/"
summary: "This page provides detailed information about Automation Event Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `description`, `extension_id`, `name`, and `updated_at`, and explains how these fields can be utilized in packages. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like `id`, `name`, and `record_url`. Additionally, it details the search query fields that can be used to filter automation events, such as `description`, `extension`, and `name`. Lastly, it lists the worklist columns available for automation events, which include `a_description`, `a_extension_id`, and `a_name`, among others. This comprehensive guide is essential for understanding and managing automation events within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Automation Event |
| **Name (plural):** | Automation Events |
| **Alias (uri):** | automation\_event |
| **Identifier (ID):** | cerb.contexts.automation.event |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Automation Event [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting runs, errors, and duration, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | &nbsp; |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this automation event |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `description` | text | Description |
| `extension_id` | &nbsp; | Extension |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in automation event [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `description:` | [text](/docs/search/#text) | Description |
| `extension:` | [text](/docs/search/#text) | Extension |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on automation event [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `a_description` | Description |
| `a_extension_id` | Extension |
| `a_id` | Id |
| `a_name` | Name |
| `a_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

