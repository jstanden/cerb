---
id: "docs-records-types-workspacewidget"
title: "Workspace Widget Records"
url: "https://cerb.ai/docs/records/types/workspace_widget/"
summary: "This page provides detailed information about Workspace Widget Records in Cerb, including their structure and usage within the system. It covers the fields available in the Records API, which are essential for defining and managing workspace widgets, such as `extension_id`, `label`, `tab_id`, and `updated_at`. The page also explains the Dictionary Placeholders that can be used in automations, snippets, and API responses, offering a range of fields like `id`, `label`, and `zone`. Additionally, it outlines the Search Query Fields that can be used to filter workspace widget searches, including `id`, `name`, and `updated`. Lastly, it lists the Worklist Columns available for workspace widget worklists, providing options for displaying information like `w_label`, `w_pos`, and `w_zone`. This comprehensive guide is crucial for users looking to effectively utilize and customize workspace widgets within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Workspace Widget |
| **Name (plural):** | Workspace Widgets |
| **Alias (uri):** | workspace\_widget |
| **Identifier (ID):** | cerberusweb.contexts.workspace.widget |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | [Workspace Widget Type](/docs/plugins/extensions/points/cerberusweb.ui.workspace.widget/) |
| **x** | **`label`** | [text](/docs/records/fields/types/text/) | The human-friendly name of the widget |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `options_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | The position of the widget on the dashboard; `0` is first (top-right); rows before columns |
| **x** | **`tab_id`** | [number](/docs/records/fields/types/number/) | The ID of the [workspace tab](/docs/records/types/workspace_tab/) containing this widget |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `width_units` | [number](/docs/records/fields/types/number/) | `1` (25%), `2` (50%), `3` (75%), `4` (100%) |
| &nbsp; | `zone` | [text](/docs/records/fields/types/text/) | The name of the dashboard zone containing the widget; this varies by layout; generally `sidebar` and `content` |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `extension_id` | text | Type |
| `id` | number | Id |
| `label` | text | Label |
| `params` | object | Params |
| `pos` | text | Order |
| `tab_` | record | [Tab](/docs/records/types/workspace_tab/) |
| `tab_extension_` | record | Tab Type |
| `updated_at` | date | Updated |
| `width_units` | number | Width |
| `zone` | text | Zone |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `data` | hashmap | Data |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in workspace widget [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Label |
| `tab:` | [record](/docs/search/#deep-search) | [Tab](/docs/records/types/workspace_tab/) |
| `tab.id:` | [chooser](/docs/search/#choosers) | [Workspace Tab](/docs/records/types/workspace_tab/) |
| `tab.pos:` | [number](/docs/search/#numbers) | Order |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `width:` | [text](/docs/search/#text) | Width |
| `zone:` | [text](/docs/search/#text) | Zone |

### Worklist Columns

These columns are available on workspace widget [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_extension_id` | Type |
| `w_id` | Id |
| `w_label` | Label |
| `w_pos` | Order |
| `w_updated_at` | Updated |
| `w_width_units` | Width |
| `w_workspace_tab_id` | Workspace Tab |
| `w_zone` | Zone |

[\< Record Types](/docs/records/types/)

