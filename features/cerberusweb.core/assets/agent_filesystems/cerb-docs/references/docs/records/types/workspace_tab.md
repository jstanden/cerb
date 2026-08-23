---
id: "docs-records-types-workspacetab"
title: "Workspace Tab Records"
url: "https://cerb.ai/docs/records/types/workspace_tab/"
summary: "This page provides detailed information about the structure and functionality of Workspace Tab records in Cerb. It outlines the fields available in the Records API, including required fields like `extension_id`, `name`, and `page_id`, as well as optional fields such as `links` and `params`. The page also describes dictionary placeholders used in automations, snippets, and API responses, offering a comprehensive list of fields and their types. Additionally, it covers search query fields that can be used to filter workspace tab searches, and lists the columns available in workspace tab worklists, such as custom fields, type, ID, name, order, and updated date. This information is crucial for developers and users who need to manage and interact with workspace tabs within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Workspace Tab |
| **Name (plural):** | Workspace Tabs |
| **Alias (uri):** | workspace\_tab |
| **Identifier (ID):** | cerberusweb.contexts.workspace.tab |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | [Workspace Tab Type](/docs/plugins/extensions/points/cerberusweb.ui.workspace.tab/) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this workspace tab |
| &nbsp; | `options_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| **x** | **`page_id`** | [number](/docs/records/fields/types/number/) | The ID of the [workspace page](/docs/records/types/workspace_page/) containing this tab |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | The position of this tab on the workspace page; `0` is first |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `extension_` | record | Type |
| `extension_id` | text | Extension Id |
| `id` | number | Id |
| `name` | text | Name |
| `order` | number | Order |
| `page_` | record | [Page](/docs/records/types/workspace_page/) |
| `page_extension_` | record | Page Type |
| `page_owner_` | record | Page Owner |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `widgets` | records | Widgets |
| `widgets_data` | hashmap | Widgets Data |
| `worklists` | records | Worklists |

### Search Query Fields

These [filters](/docs/search/#filters) are available in workspace tab [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `page.id:` | [chooser](/docs/search/#choosers) | [Workspace Page](/docs/records/types/workspace_page/) |
| `pos:` | [number](/docs/search/#numbers) | Order |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `workspace:` | [record](/docs/search/#deep-search) | [Workspace](/docs/records/types/workspace_page/) |
| `workspace.id:` | [chooser](/docs/search/#choosers) | [Workspace Page](/docs/records/types/workspace_page/) |

### Worklist Columns

These columns are available on workspace tab [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_extension_id` | Type |
| `w_id` | Id |
| `w_name` | Name |
| `w_pos` | Order |
| `w_updated_at` | Updated |
| `w_workspace_page_id` | Workspace Page |

[\< Record Types](/docs/records/types/)

