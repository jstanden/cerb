---
id: "docs-records-types-projectboardcolumn"
title: "Project Board Column Records"
url: "https://cerb.ai/docs/records/types/project_board_column/"
summary: "This page provides detailed information about the Project Board Column records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and types of data associated with project board columns, such as board IDs, card links, names, positions, and timestamps. The page also describes how these fields can be utilized in the Records API, automation dictionaries, and search queries, offering a comprehensive guide for managing and interacting with project board columns within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Project Board Column |
| **Name (plural):** | Project Board Columns |
| **Alias (uri):** | project\_board\_column |
| **Identifier (ID):** | cerberusweb.contexts.project.board.column |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`board_id`** | [number](/docs/records/fields/types/number/) | The [project board](/docs/records/types/project_board/) containing this column |
| &nbsp; | `cards` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to add to this column |
| &nbsp; | `cards_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `functions_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this project board column |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | (0-4294967296) |
| &nbsp; | `toolbar_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `board_` | record | [Project Board](/docs/records/types/project_board/) |
| `cards_kata` | text | Cards Kata |
| `functions_kata` | text | Functions Kata |
| `id` | number | Id |
| `name` | text | Name |
| `pos` | number | Order |
| `record_url` | text | Record Url |
| `toolbar_kata` | text | Toolbar Kata |
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

These [filters](/docs/search/#filters) are available in project board column [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `board:` | [record](/docs/search/#deep-search) | [Board](/docs/records/types/project_board/) |
| `board.id:` | [chooser](/docs/search/#choosers) | [Project Board](/docs/records/types/project_board/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on project board column [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `p_board_id` | Project Board |
| `p_id` | Id |
| `p_name` | Name |
| `p_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

