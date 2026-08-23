---
id: "docs-records-types-projectboard"
title: "Project Board Records"
url: "https://cerb.ai/docs/records/types/project_board/"
summary: "This page provides detailed information about the Project Board records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and types of data available for Project Boards, such as the required and optional fields in the Records API, which include attributes like `name`, `owner__context`, and `updated_at`. The page also describes the dictionary placeholders used in automations and API responses, offering a range of fields from basic identifiers to custom fields and links. Additionally, it specifies the search query filters available for Project Boards, allowing users to search by attributes like `name`, `id`, and `updated`. Lastly, it lists the columns available in worklists for Project Boards, which help in organizing and displaying data efficiently."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Project Board |
| **Name (plural):** | Project Boards |
| **Alias (uri):** | project\_board |
| **Identifier (ID):** | cerberusweb.contexts.project.board |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `cards_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this project board |
| &nbsp; | `owner__context` | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this project board's owner: `app`, `role`, `group`, or `worker` |
| &nbsp; | `owner_id` | [number](/docs/records/fields/types/number/) | The ID of this project board's owner |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `cards_kata` | text | Common.cards\_Kata |
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

These [filters](/docs/search/#filters) are available in project board [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on project board [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `p_id` | Id |
| `p_name` | Name |
| `p_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

