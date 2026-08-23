---
id: "docs-records-types-toolbar"
title: "Toolbar Records"
url: "https://cerb.ai/docs/records/types/toolbar/"
summary: "This page provides detailed information about toolbar records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and requirements for toolbar records, such as mandatory fields like `extension_id` and `name`, and optional fields like `links` and `description`. The page also describes how these records can be utilized in automations, snippets, and API responses through dictionary placeholders. Additionally, it lists the available filters for toolbar search queries and the columns that can be displayed in toolbar worklists, offering a comprehensive guide for managing and interacting with toolbar records in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Toolbar |
| **Name (plural):** | Toolbars |
| **Alias (uri):** | toolbar |
| **Identifier (ID):** | cerb.contexts.toolbar |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | &nbsp; |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this toolbar |
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
| `extension_id` | text | Extension |
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
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in toolbar [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `description:` | [text](/docs/search/#text) | Description |
| `extension:` | [text](/docs/search/#text) | Extension |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on toolbar [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `t_created_at` | Created |
| `t_description` | Description |
| `t_extension_id` | Extension |
| `t_id` | Id |
| `t_name` | Name |
| `t_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

