---
id: "docs-records-types-kbcategory"
title: "Knowledgebase Category Records"
url: "https://cerb.ai/docs/records/types/kb_category/"
summary: "This page provides detailed information about Knowledgebase Category records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `name`, `parent_id`, and `updated_at`, and explains how these fields can be used to manage knowledgebase categories. The page also describes dictionary placeholders for automations, snippets, and API responses, offering fields like `id`, `name`, and `updated_at`, along with optional placeholders for comments, custom fields, and links. Additionally, it details search query fields that can be used to filter knowledgebase categories, including `article.id`, `name`, and `updated`. Lastly, it lists the worklist columns available for displaying knowledgebase category information, such as `kbc_id`, `kbc_name`, and `kbc_updated_at`."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Knowledgebase Category |
| **Name (plural):** | Knowledgebase Categories |
| **Alias (uri):** | kb\_category |
| **Identifier (ID):** | cerberusweb.contexts.kb\_category |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this knowledgebase category |
| &nbsp; | `parent_id` | [number](/docs/records/fields/types/number/) | The ID of the parent [category](/docs/records/types/kb_category/); if `0` this is a top-level topic |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
| `name` | text | Name |
| `parent_id` | number | Parent |
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

These [filters](/docs/search/#filters) are available in knowledgebase category [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `article.id:` | [chooser](/docs/search/#choosers) | [Knowledgebase Article](/docs/records/types/kb_article/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `parent.id:` | [chooser](/docs/search/#choosers) | [Parent](/docs/records/types/kb_category/) |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on knowledgebase category [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `katc_article_id` | Knowledgebase Article |
| `kbc_id` | Id |
| `kbc_name` | Name |
| `kbc_parent_id` | Parent |
| `kbc_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

