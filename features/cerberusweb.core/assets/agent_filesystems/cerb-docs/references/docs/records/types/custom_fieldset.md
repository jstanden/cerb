---
id: "docs-records-types-customfieldset"
title: "Custom Fieldset Records"
url: "https://cerb.ai/docs/records/types/custom_fieldset/"
summary: "This page provides detailed information about Custom Fieldset records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as context, name, owner context, and updated timestamp, and explains how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. Additionally, it describes the search query fields that can be used to filter custom fieldset records and the worklist columns available for organizing and displaying these records. The page serves as a comprehensive guide for managing and interacting with custom fieldsets in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Custom Fieldset |
| **Name (plural):** | Custom Fieldsets |
| **Alias (uri):** | custom\_fieldset |
| **Identifier (ID):** | cerberusweb.contexts.custom\_fieldset |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of the fieldset |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this custom fieldset |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this custom fieldset's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this custom fieldset's owner |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `context` | text | Context |
| `id` | number | Id |
| `name` | text | Name |
| `owner_` | record | Owner |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in custom fieldset [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `context:` | [text](/docs/search/#text) | Context |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `owner:` | virtual | Owner |
| `owner.app:` | virtual | Owner |
| `owner.bot:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/bot/) |
| `owner.group:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/group/) |
| `owner.role:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/role/) |
| `owner.worker:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/worker/) |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on custom fieldset [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `c_context` | Context |
| `c_id` | Id |
| `c_name` | Name |
| `c_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

