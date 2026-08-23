---
id: "docs-records-types-savedsearch"
title: "Saved Search Records"
url: "https://cerb.ai/docs/records/types/saved_search/"
summary: "This page provides detailed information about Saved Search records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as context, name, owner, and query, and explains how these fields can be utilized in packages. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields and their types. Additionally, it covers search query fields that can be used to filter saved searches and lists the worklist columns available for displaying saved search data. This information is crucial for users looking to manage and utilize saved searches effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Saved Search |
| **Name (plural):** | Saved Searches |
| **Alias (uri):** | saved\_search |
| **Identifier (ID):** | cerberusweb.contexts.context.saved.search |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this search query; e.g. `ticket` |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this saved search |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this saved search's owner: `app`, `role`, `group`, or `worker` |
| &nbsp; | `owner_id` | [number](/docs/records/fields/types/number/) | The ID of this saved search's owner |
| **x** | **`query`** | [text](/docs/records/fields/types/text/) | The [search query](/docs/search/); e.g. `status:o` |
| &nbsp; | `tag` | [text](/docs/records/fields/types/text/) | A human-friendly nickname for this search (e.g. `open_tickets`) |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `context` | text | Type |
| `id` | number | Id |
| `name` | text | Name |
| `owner_` | record | Owner |
| `query` | text | Query |
| `record_url` | text | Record Url |
| `tag` | text | Tag |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in saved search [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `context:` | [text](/docs/search/#text) | Record Type |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `name:` | [text](/docs/search/#text) | Name |
| `query:` | [text](/docs/search/#text) | Query |
| `tag:` | [text](/docs/search/#text) | Tag |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on saved search [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_context` | Record Type |
| `c_id` | Id |
| `c_name` | Name |
| `c_query` | Query |
| `c_tag` | Tag |
| `c_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

