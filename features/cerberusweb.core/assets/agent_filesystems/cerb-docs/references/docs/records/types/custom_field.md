---
id: "docs-records-types-customfield"
title: "Custom Field Records"
url: "https://cerb.ai/docs/records/types/custom_field/"
summary: "This page provides detailed information about custom field records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as context, name, type, and URI, and explains their types and requirements. The page also describes dictionary placeholders used in automations, snippets, and API responses, offering a comprehensive list of fields and their descriptions. Additionally, it covers search query fields that can be used to filter custom field records and lists the available columns for custom field worklists, providing a complete guide for managing and utilizing custom fields in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Custom Field |
| **Name (plural):** | Custom Fields |
| **Alias (uri):** | custom\_field |
| **Identifier (ID):** | cerberusweb.contexts.custom\_field |

- [Records API](#records-api)
  - [Types](#types)

- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) to add the field to |
| &nbsp; | `custom_fieldset_id` | [number](/docs/records/fields/types/number/) | The ID of the parent [custom fieldset](/docs/records/types/custom_fieldset/); if any |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this custom field |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | Display order; positive integer; `0` is first |
| **x** | **`type`** | [text](/docs/records/fields/types/text/) | See [Types](#types) below |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| **x** | **`uri`** | [text](/docs/records/fields/types/text/) | The unique alias for this custom field |

#### Types

| Type | ID | Params |
| --- | --- | --- |
| Checkbox | `C` | &nbsp; |
| Currency | `Y` | `currency_id` (record ID of a [currency](/docs/records/types/currency/) record) |
| Date | `E` | &nbsp; |
| Decimal | `O` | `decimal_at` (number of decimal places; e.g. `4` for 3.1415) |
| File | `F` | &nbsp; |
| Files | `I` | &nbsp; |
| Latitude/Longitude | `cerb.custom_field.geo.point` | &nbsp; |
| List | `M` | `context` ([record type](/docs/records/types/) alias) |
| Multiple Checkboxes | `X` | `options` (one per line, linefeed delimited) |
| Multiple Lines of Text | `T` | `format` (blank for plaintext, or `markdown`) |
| Number | `N` | &nbsp; |
| Picklist | `D` | `options` (one per line, linefeed delimited) |
| Record Link | `L` | `context` ([record type](/docs/records/types/) alias) |
| Record Links | `cerb.custom_field.record.links` | `context` ([record type](/docs/records/types/) alias) |
| Single Line of Text | `S` | &nbsp; |
| Slider | `cerb.custom_field.slider` | `value_min`, `value_max` |
| URL | `U` | &nbsp; |
| Worker | `W` | `send_notifications` (`0` disabled, `1` enabled) |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `context` | text | Context |
| `id` | number | Id |
| `name` | text | Name |
| `pos` | number | Order |
| `search_filter` | text | Search Filter Name |
| `type` | text | Type |
| `type_label` | text | Type Label |
| `updated_at` | date | Updated |
| `uri` | text | Uri |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in custom field [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `context:` | [text](/docs/search/#text) | Context |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `fieldset.id:` | [chooser](/docs/search/#choosers) | [Custom Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `pos:` | [number](/docs/search/#numbers) | Order |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `uri:` | [text](/docs/search/#text) | Uri |

### Worklist Columns

These columns are available on custom field [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_context` | Context |
| `c_custom_fieldset_id` | Custom Fieldset |
| `c_id` | Id |
| `c_name` | Name |
| `c_pos` | Order |
| `c_type` | Type |
| `c_updated_at` | Updated |
| `c_uri` | Uri |

[\< Record Types](/docs/records/types/)

