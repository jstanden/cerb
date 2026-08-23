---
id: "docs-records-types-cardwidget"
title: "Card Widget Records"
url: "https://cerb.ai/docs/records/types/card_widget/"
summary: "This page provides detailed information about Card Widget records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and attributes of Card Widgets, such as their extension ID, name, position, record type, and update timestamp. The page also describes how these widgets can be linked to other records and customized with JSON-encoded parameters. Additionally, it explains the available dictionary placeholders for automations and API responses, as well as the search filters and worklist columns that can be used to manage and organize Card Widgets effectively."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Card Widget |
| **Name (plural):** | Card Widgets |
| **Alias (uri):** | card\_widget |
| **Identifier (ID):** | cerb.contexts.card.widget |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | [Card Widget Type](/docs/plugins/extensions/points/cerb.card.widget/) |
| &nbsp; | `extension_params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this card widget |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | The order of the widget on the card; `0` is first (top-left) proceeding in rows then columns |
| **x** | **`record_type`** | [context](/docs/records/fields/types/context/) | The record type of the card containing this widget |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `width_units` | [number](/docs/records/fields/types/number/) | `1` (25%), `2` (50%), `3` (75%), `4` (100%) |
| &nbsp; | `zone` | [text](/docs/records/fields/types/text/) | The name of the dashboard zone containing the widget; this varies by layout; generally `sidebar` and `content` |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
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

These [filters](/docs/search/#filters) are available in card widget [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `pos:` | [number](/docs/search/#numbers) | Order |
| `type:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `width:` | [number](/docs/search/#numbers) | Width |
| `zone:` | [text](/docs/search/#text) | Zone |

### Worklist Columns

These columns are available on card widget [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_created_at` | Created |
| `c_extension_id` | Type |
| `c_id` | Id |
| `c_name` | Name |
| `c_pos` | Order |
| `c_record_type` | Record Type |
| `c_updated_at` | Updated |
| `c_width_units` | Width |
| `c_zone` | Zone |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

