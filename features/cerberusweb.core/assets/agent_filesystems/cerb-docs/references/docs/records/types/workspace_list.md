---
id: "docs-records-types-workspacelist"
title: "Workspace Worklist Records"
url: "https://cerb.ai/docs/records/types/workspace_list/"
summary: "This page provides detailed information about Workspace Worklist Records in Cerb, including their structure and functionality. It outlines the fields available in the Records API, such as context, name, and tab ID, and describes how these fields are used in JSON-encoded objects for managing workspace worklists. The page also explains the use of dictionary placeholders for automations, snippets, and API responses, offering a comprehensive list of fields like columns, context, and options. Additionally, it covers search query fields that can be used to filter workspace worklists, including fieldset, id, and name. Lastly, it details the available worklist columns, which include custom fields, type, id, name, and updated date, providing a robust framework for organizing and accessing workspace worklist data within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Workspace Worklist |
| **Name (plural):** | Workspace Worklists |
| **Alias (uri):** | workspace\_list |
| **Identifier (ID):** | cerberusweb.contexts.workspace.list |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `columns` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value array of column names |
| **x** | **`context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of the worklist |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this workspace worklist |
| &nbsp; | `options` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `params_required_query` | [text](/docs/records/fields/types/text/) | The [search query](/docs/search/) for required filters |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | The order of the worklist on the workspace tab; `0` is first |
| &nbsp; | `render_limit` | [number](/docs/records/fields/types/number/) | The number of records per page |
| **x** | **`tab_id`** | [number](/docs/records/fields/types/number/) | The ID of the [workspace tab](/docs/records/types/workspace_tab/) containing this worklist |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `columns` | object | Columns |
| `context` | text | Context |
| `id` | number | Id |
| `name` | text | Name |
| `options` | object | Options |
| `params` | object | Params |
| `pos` | number | Order |
| `record_url` | text | Record Url |
| `render_limit` | number | Render Limit |
| `render_sort` | object | Sort |
| `render_subtotals` | text | Subtotals |
| `tab_` | record | [Tab](/docs/records/types/workspace_tab/) |
| `tab_extension_` | record | Tab Type |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in workspace worklist [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `tab:` | [record](/docs/search/#deep-search) | [Tab](/docs/records/types/workspace_tab/) |
| `tab.id:` | [chooser](/docs/search/#choosers) | [Workspace Tab](/docs/records/types/workspace_tab/) |
| `tab.pos:` | [number](/docs/search/#numbers) | Order |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on workspace worklist [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_context` | Type |
| `w_id` | Id |
| `w_name` | Name |
| `w_updated_at` | Updated |
| `w_workspace_tab_id` | Workspace Tab |
| `w_workspace_tab_pos` | Order |

[\< Record Types](/docs/records/types/)

