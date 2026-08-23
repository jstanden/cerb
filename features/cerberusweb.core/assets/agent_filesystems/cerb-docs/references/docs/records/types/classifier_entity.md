---
id: "docs-records-types-classifierentity"
title: "Classifier Entity Records"
url: "https://cerb.ai/docs/records/types/classifier_entity/"
summary: "This page provides detailed information about Classifier Entity Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as name, type, description, and links, and specifies which fields are required. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like context, label, and record URL. Additionally, it lists search query fields that can be used to filter classifier entities, such as description, fieldset, and updated date. Lastly, it details the worklist columns available for classifier entities, including description, ID, name, type, and updated date, providing a comprehensive guide for managing and utilizing classifier entities within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Classifier Entity |
| **Name (plural):** | Classifier Entities |
| **Alias (uri):** | classifier\_entity |
| **Identifier (ID):** | cerberusweb.contexts.classifier.entity |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | A description of this entity |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this classifier entity |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| **x** | **`type`** | [text](/docs/records/fields/types/text/) | The type of this entity: `list`, `regexp`, or `text` |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `description` | text | Description |
| `id` | number | Id |
| `name` | text | Name |
| `params` | &nbsp; | Params |
| `record_url` | text | Record Url |
| `type` | text | Type |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in classifier entity [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `description:` | [text](/docs/search/#text) | Description |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on classifier entity [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_description` | Description |
| `c_id` | Id |
| `c_name` | Name |
| `c_type` | Type |
| `c_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

