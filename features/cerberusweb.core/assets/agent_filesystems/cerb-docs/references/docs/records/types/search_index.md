---
id: "docs-records-types-searchindex"
title: "Search Index Records"
url: "https://cerb.ai/docs/records/types/search_index/"
summary: "A search index manages a custom search filter on any record type. Each index is backed by a search extension (e.g. local full-text, TF-IDF, BM25, vector embeddings, Elasticsearch, Qdrant, Pinecone). This page documents the Records API fields, dictionary placeholders, search filters, and worklist columns available on search index records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Search Index |
| **Name (plural):** | Search Indexes |
| **Alias (uri):** | search\_index |
| **Identifier (ID):** | cerb.contexts.search.index |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

https://www.youtube.com/embed/a2IhZPPLWls

### Usage tracking

Search Index [worklists](/docs/worklists/) offer a 'Records' [sparklines column](/docs/worklists/#sparkline-columns) charting indexed record count, with a 2h/1d/30d range toggle. A matching `records:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `records:(since:today)`.

Search indexes provide configurable, plugin-driven [search](/docs/search/) on any [record type](/docs/records/types/). Each search index manages a linked custom filter, exposed as a `filter:` keyword on the record type's worklists.

A search index has:

- A **type** (an extension that provides the indexing strategy – local full-text, vector embeddings, Elasticsearch, etc.)
- A **record type** the index applies to
- A **filter query** that constrains which records are included (e.g. "open tickets updated in the last year")
- A **content template** that formats the indexable text per record (e.g. `{{title}} {{content}}`). Since it's a [scripting](/docs/scripting/) template, it can also strip noise before indexing – [`strip_lines`](/docs/scripting/filters/#strip_lines) for quoted replies, [`strip_pem_blocks`](/docs/scripting/filters/#strip_pem_blocks) for keys and signatures, [`strip_data_uris`](/docs/scripting/filters/#strip_data_uris) for inline images, and [`strip_url_querystrings`](/docs/scripting/filters/#strip_url_querystrings) for tracking parameters – which keeps the index smaller and its matches more relevant. The built-in message index does exactly this.
- A **priority** that controls autocompletion order; setting priority to `0` makes the index act as the default search filter when no explicit `filter:` is provided

The **type** and **record type** are set when the index is created and can't be changed afterward – the editor shows them as fixed values. Everything else stays editable.

The **filter name** and the **filter query** are separate things, and it's easy to conflate them. The filter name is the keyword you type (`filter:header.from`), and it's optional – leave it blank and the index has no keyword of its own. The filter query constrains which records get indexed at all, and leaving it blank indexes every record of that type.

New index types can be implemented via [plugins](/docs/plugins/).

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | The ID of the [search index extension](/docs/plugins/) (e.g. `cerb.search.engine.fulltext`) |
| &nbsp; | `extension_params_json` | [text](/docs/records/fields/types/text/) | Extension parameters serialized as JSON (e.g. content template, boosting weights) |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this search index |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this search index |
| &nbsp; | `priority` | [number](/docs/records/fields/types/number/) | Sort order in `filter:` autocompletion; `0` makes this the default filter when no `filter:` is specified |
| **x** | **`record_filter`** | [text](/docs/records/fields/types/text/) | A search query that constrains which records are indexed |
| **x** | **`record_type`** | [text](/docs/records/fields/types/text/) | The [record type](/docs/records/types/) alias this index applies to |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| **x** | **`uri`** | [text](/docs/records/fields/types/text/) | A short identifier used as the `filter:` keyword (e.g. `filter:by_title`) |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `extension_id` | text | Type |
| `id` | number | Id |
| `name` | text | Name |
| `priority` | number | Priority |
| `record_filter` | text | Filter query |
| `record_type` | text | Record type |
| `record_url` | text | Record URL |
| `updated_at` | date | Updated |
| `uri` | text | Filter URI |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in search index [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `priority:` | [number](/docs/search/#numbers) | Priority |
| `record_filter:` | [text](/docs/search/#text) | Filter query |
| `record_type:` | [text](/docs/search/#text) | Record type alias |
| `type:` | [text](/docs/search/#text) | Extension ID |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `uri:` | [text](/docs/search/#text) | Filter URI |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on search index [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `r_created_at` | Created |
| `r_extension_id` | Type |
| `r_id` | Id |
| `r_name` | Name |
| `r_priority` | Priority |
| `r_record_filter` | Filter query |
| `r_record_type` | Record type |
| `r_updated_at` | Updated |
| `r_uri` | Filter URI |

[\< Record Types](/docs/records/types/)

