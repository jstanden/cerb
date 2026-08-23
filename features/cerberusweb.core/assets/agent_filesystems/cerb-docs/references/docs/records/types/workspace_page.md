---
id: "docs-records-types-workspacepage"
title: "Workspace Page Records"
url: "https://cerb.ai/docs/records/types/workspace_page/"
summary: "This page provides detailed information about Workspace Page records in Cerb, including their structure and usage within the system. It covers the Records API, which outlines the required and optional fields for Workspace Pages, such as `extension_id`, `name`, and `owner_id`. The page also explains Dictionary Placeholders used in automations, snippets, and API responses, offering a list of available fields and their descriptions. Additionally, it details the Search Query Fields that can be used to filter Workspace Page searches, such as `id`, `name`, and `owner`. Lastly, it describes the Worklist Columns available for displaying Workspace Page data, including columns for owner, custom fields, and update timestamps. This comprehensive guide is essential for developers and users looking to manage and interact with Workspace Pages in Cerb effectively."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Workspace Page |
| **Name (plural):** | Workspace Pages |
| **Alias (uri):** | workspace\_page |
| **Identifier (ID):** | cerberusweb.contexts.workspace.page |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | [Workspace Page Type](/docs/plugins/extensions/points/cerberusweb.ui.workspace.page/) |
| &nbsp; | `extension_params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this workspace page |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this workspace page's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this workspace page's owner |
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
| `owner_` | record | Owner |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `tabs` | records | Tabs |
| `widgets` | records | Widgets |
| `worklists` | records | Worklists |

### Search Query Fields

These [filters](/docs/search/#filters) are available in workspace page [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `name:` | [text](/docs/search/#text) | Name |
| `owner:` | virtual | Owner |
| `owner.app:` | virtual | Owner |
| `owner.bot:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/bot/) |
| `owner.group:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/group/) |
| `owner.role:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/role/) |
| `owner.worker:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/worker/) |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on workspace page [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_extension_id` | Type |
| `w_name` | Name |
| `w_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

