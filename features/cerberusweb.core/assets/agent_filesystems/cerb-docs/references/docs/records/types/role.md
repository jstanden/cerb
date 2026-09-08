---
id: "docs-records-types-role"
title: "Role Records"
url: "https://cerb.ai/docs/records/types/role/"
summary: "This page provides detailed information about role records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing roles, such as `name`, `privs_mode`, and `updated_at`. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields like `editor_query_worker`, `member_query_worker`, and `reader_query_worker`. Additionally, it covers search query fields that facilitate role searches, including filters like `editor:`, `member:`, and `name:`. Lastly, it details the worklist columns available for roles, which help in organizing and displaying role data effectively, with columns such as `w_name`, `w_privs_mode`, and `w_updated_at`."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Role |
| **Name (plural):** | Roles |
| **Alias (uri):** | role |
| **Identifier (ID):** | cerberusweb.contexts.role |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `editor_query_worker` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `image` | [image](/docs/records/fields/types/image/) | The profile image, base64-encoded in data URI format |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `member_query_worker` | [text](/docs/records/fields/types/text/) | &nbsp; |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this role |
| &nbsp; | `privs_mode` | [text](/docs/records/fields/types/text/) | ["", all, itemized] |
| &nbsp; | `reader_query_worker` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `editor_query_worker` | text | Editor Query |
| `id` | number | Id |
| `member_query_worker` | text | Member Query |
| `name` | text | Name |
| `privs_mode` | text | Privileges Mode |
| `reader_query_worker` | text | Reader Query |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in role [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `editor:` | [record](/docs/search/#deep-search) | [Editor](/docs/records/types/worker/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `member:` | [record](/docs/search/#deep-search) | [Member](/docs/records/types/worker/) |
| `name:` | [text](/docs/search/#text) | Name |
| `privsMode:` | [text](/docs/search/#text) | Privileges Mode |
| `reader:` | [record](/docs/search/#deep-search) | [Reader](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on role [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_editor_query_worker` | Editor Query |
| `w_id` | Id |
| `w_member_query_worker` | Member Query |
| `w_name` | Name |
| `w_privs_mode` | Privileges Mode |
| `w_reader_query_worker` | Reader Query |
| `w_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

