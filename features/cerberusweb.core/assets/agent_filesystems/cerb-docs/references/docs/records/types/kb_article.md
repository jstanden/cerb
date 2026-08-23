---
id: "docs-records-types-kbarticle"
title: "Knowledgebase Article Records"
url: "https://cerb.ai/docs/records/types/kb_article/"
summary: "This page provides detailed information about Knowledgebase Article records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as categories, content, format, and title, and explains how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. The page also describes the search query fields that can be used to filter knowledgebase articles, such as category, content, format, and views. Additionally, it lists the worklist columns available for organizing and displaying knowledgebase articles, including custom fields, format, title, and updated date. This comprehensive guide is essential for managing and utilizing knowledgebase articles effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Knowledgebase Article |
| **Name (plural):** | Knowledgebase Articles |
| **Alias (uri):** | kb\_article |
| **Identifier (ID):** | cerberusweb.contexts.kb\_article |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `categories` | [text](/docs/records/fields/types/text/) | A comma-separated list of IDs of [categories](/docs/records/types/kb_category/) to assign this article to |
| &nbsp; | `content` | [text](/docs/records/fields/types/text/) | The content of the article |
| &nbsp; | `format` | [text](/docs/records/fields/types/text/) | `text`, `markdown`, or `html` |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`title`** | [text](/docs/records/fields/types/text/) | The title of the article |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `views` | [number](/docs/records/fields/types/number/) | The number of times the article has been viewed in a [community portal](/docs/portals/) |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `content` | text | Content |
| `format` | text | Format |
| `id` | number | Id |
| `record_url` | text | Record Url |
| `title` | text | Title |
| `updated` | date | Updated |
| `views` | number | Views |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `attachments` | attachments | [Attachments](/docs/guide/developers/dictionaries/#key-expansion) |
| `categories` | hashmap | Categories |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in knowledgebase article [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `category.id:` | [chooser](/docs/search/#choosers) | [Category](/docs/records/types/kb_category/) |
| `content:` | [fulltext](/docs/search/#fulltext) | Content |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `format:` | [text](/docs/search/#text) | Format |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `title:` | [text](/docs/search/#text) | Title |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `views:` | [number](/docs/search/#numbers) | Views |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on knowledgebase article [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `kb_format` | Format |
| `kb_id` | Id |
| `kb_title` | Title |
| `kb_updated` | Updated |
| `kb_views` | Views |

[\< Record Types](/docs/records/types/)

