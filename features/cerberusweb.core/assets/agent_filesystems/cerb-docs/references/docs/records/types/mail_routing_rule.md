---
id: "docs-records-types-mailroutingrule"
title: "Mail Routing Rule Records"
url: "https://cerb.ai/docs/records/types/mail_routing_rule/"
summary: "This page provides detailed information about the structure and functionality of Email Routing Rule records in Cerb. It outlines the fields available in the Records API, including required and optional fields such as `name`, `priority`, and `workflow_id`. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like `created_at`, `id`, and `routing_kata`. Additionally, it lists search query fields that can be used to filter mail routing rules, such as `created:`, `isDisabled:`, and `workflow.id:`. Lastly, it details the worklist columns available for displaying mail routing rules, including custom fields and standard fields like `m_name`, `m_priority`, and `m_updated_at`. This comprehensive guide is essential for managing and utilizing email routing rules within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Email Routing Rule |
| **Name (plural):** | Email Routing Rules |
| **Alias (uri):** | mail\_routing\_rule |
| **Identifier (ID):** | cerb.contexts.mail.routing.rule |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Mail Routing Rule [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting rule matches, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this email routing rule |
| &nbsp; | `priority` | [number](/docs/records/fields/types/number/) | (0-255) |
| &nbsp; | `routing_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
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
| `routing_kata` | text | Routing Kata |
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

These [filters](/docs/search/#filters) are available in mail routing rule [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isDisabled:` | [boolean](/docs/search/#booleans) | Disabled |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `priority:` | [number](/docs/search/#numbers) | Priority |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `workflow.id:` | [chooser](/docs/search/#choosers) | [Workflow](/docs/records/types/workflow/) |

### Worklist Columns

These columns are available on mail routing rule [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `m_created_at` | Created |
| `m_id` | Id |
| `m_is_disabled` | Disabled |
| `m_name` | Name |
| `m_priority` | Priority |
| `m_updated_at` | Updated |
| `m_workflow_id` | Workflow |

[\< Record Types](/docs/records/types/)

