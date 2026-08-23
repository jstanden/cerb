---
id: "docs-records-types-toolbarsection"
title: "Toolbar Section Records"
url: "https://cerb.ai/docs/records/types/toolbar_section/"
summary: "This page provides detailed information about the 'Toolbar Section' records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and attributes of toolbar sections, such as their name, priority, and associated workflows. The page also explains how these records can be linked, queried, and displayed within the Cerb platform, offering a comprehensive guide for developers and users to manage and utilize toolbar sections effectively."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Toolbar Section |
| **Name (plural):** | Toolbar Sections |
| **Alias (uri):** | toolbar\_section |
| **Identifier (ID):** | cerb.contexts.toolbar.section |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this toolbar section |
| &nbsp; | `priority` | [number](/docs/records/fields/types/number/) | (0-255) |
| &nbsp; | `toolbar_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| **x** | **`toolbar_name`** | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `workflow_id` | [number](/docs/records/fields/types/number/) | &nbsp; |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `id` | number | Id |
| `name` | text | Name |
| `priority` | text | Priority |
| `record_url` | text | Record Url |
| `toolbar_kata` | text | Toolbar Kata |
| `toolbar_name` | text | Toolbar |
| `updated_at` | date | Updated |
| `workflow_id` | number | Common.workflow.id |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in toolbar section [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isDisabled:` | [boolean](/docs/search/#booleans) | Disabled |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `priority:` | [number](/docs/search/#numbers) | Priority |
| `toolbar:` | [text](/docs/search/#text) | Toolbar |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `workflow.id:` | [chooser](/docs/search/#choosers) | [Workflow](/docs/records/types/workflow/) |

### Worklist Columns

These columns are available on toolbar section [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `t_created_at` | Created |
| `t_id` | Id |
| `t_is_disabled` | Disabled |
| `t_name` | Name |
| `t_priority` | Priority |
| `t_toolbar_name` | Toolbar |
| `t_updated_at` | Updated |
| `t_workflow_id` | Workflow |

[\< Record Types](/docs/records/types/)

