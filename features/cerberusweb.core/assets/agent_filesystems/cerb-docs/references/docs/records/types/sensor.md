---
id: "docs-records-types-sensor"
title: "Sensor Records"
url: "https://cerb.ai/docs/records/types/sensor/"
summary: "This page provides detailed information about sensor records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and types of data associated with sensors, such as metrics, status, and tags, and explains how these can be utilized in various Cerb functionalities like automations and API responses. The page also describes how to filter and display sensor data using search queries and worklist columns, offering a comprehensive guide for managing sensor records within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Sensor |
| **Name (plural):** | Sensors |
| **Alias (uri):** | sensor |
| **Identifier (ID):** | cerberusweb.contexts.datacenter.sensor |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `metric` | [text](/docs/records/fields/types/text/) | The metric's raw value |
| &nbsp; | `metric_delta` | [text](/docs/records/fields/types/text/) | The change in the metric between the last two samples |
| &nbsp; | `metric_type` | [text](/docs/records/fields/types/text/) | The metric's type |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this sensor |
| &nbsp; | `output` | [text](/docs/records/fields/types/text/) | The metric's displayed value |
| &nbsp; | `status` | [text](/docs/records/fields/types/text/) | `O` (OK), `W` (Warning), `C` (Critical) |
| &nbsp; | `tag` | [text](/docs/records/fields/types/text/) | A human-friendly nickname for this sensor |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_label` | text | Label |
| `id` | number | Id |
| `metric` | text | Metric |
| `metric_delta` | number | Change |
| `metric_type` | text | Metric Type |
| `name` | text | Name |
| `output` | text | Output |
| `status` | text | Status |
| `tag` | text | Tag |
| `updated` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in sensor [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `change:` | [number](/docs/search/#numbers) | Change |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `fail.count:` | [number](/docs/search/#numbers) | Fail Count |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isDisabled:` | [boolean](/docs/search/#booleans) | Is Disabled |
| `links:` | [links](/docs/search/#links) | Record Links |
| `metric:` | [number](/docs/search/#numbers) | Metric |
| `metricType:` | [text](/docs/search/#text) | Metric Type |
| `name:` | [text](/docs/search/#text) | Name |
| `output:` | [text](/docs/search/#text) | Output |
| `status:` | virtual | Status |
| `tag:` | [text](/docs/search/#text) | Tag |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [watchers](/docs/search/#watchers) | Watchers |

### Worklist Columns

These columns are available on sensor [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `ftcc_content` | Comment Content |
| `p_extension_id` | Type |
| `p_fail_count` | Fail Count |
| `p_is_disabled` | Is Disabled |
| `p_metric` | Metric |
| `p_metric_delta` | Change |
| `p_metric_type` | Metric Type |
| `p_name` | Name |
| `p_output` | Output |
| `p_status` | Status |
| `p_tag` | Tag |
| `p_updated` | Updated |

[\< Record Types](/docs/records/types/)

