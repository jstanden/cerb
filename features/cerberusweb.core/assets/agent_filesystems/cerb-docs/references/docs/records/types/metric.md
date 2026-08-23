---
id: "docs-records-types-metric"
title: "Metric Records"
url: "https://cerb.ai/docs/records/types/metric/"
summary: "This page provides detailed information about Metric Records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as creation and update timestamps, descriptions, and types. The page also describes dictionary placeholders for automations, snippets, and API responses, offering a comprehensive list of fields like context, label, and record URL. Additionally, it covers search query fields that can be used to filter metrics based on various criteria, and lists the columns available in metric worklists for organizing and displaying metric data. The document serves as a guide for understanding and utilizing metric records within Cerb's ecosystem."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Metric |
| **Name (plural):** | Metrics |
| **Alias (uri):** | metric |
| **Identifier (ID):** | cerb.contexts.metric |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Metric [worklists](/docs/worklists/) offer a 'Dataset' [sparklines column](/docs/worklists/#sparkline-columns) charting min, max, average, sum, and count, with a 2h/1d/30d range toggle.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `dimensions_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this metric |
| &nbsp; | `type` | [text](/docs/records/fields/types/text/) | [counter, gauge] |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `description` | text | Description |
| `dimensions_kata` | text | Dao.metric.dimensions\_Kata |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `type` | text | Type |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `dimensions` | hashmap | Dimensions |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in metric [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `description:` | [text](/docs/search/#text) | Description |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on metric [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `m_created_at` | Created |
| `m_description` | Description |
| `m_id` | Id |
| `m_name` | Name |
| `m_type` | Type |
| `m_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

