---
id: "docs-records-types-connectedservice"
title: "Connected Service Records"
url: "https://cerb.ai/docs/records/types/connected_service/"
summary: "This page provides detailed information about Connected Service records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing connected services, such as `extension_id`, `name`, and `updated_at`. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like `id`, `name`, and `record_url`. Additionally, it covers search query fields that facilitate filtering connected services based on criteria like `id`, `name`, and `updated`. Lastly, it lists the worklist columns available for displaying connected service data, including `c_extension_id`, `c_name`, and custom fields. This comprehensive guide is crucial for developers and users who need to integrate and manage connected services within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Connected Service |
| **Name (plural):** | Connected Services |
| **Alias (uri):** | connected\_service |
| **Identifier (ID):** | cerberusweb.contexts.connected\_service |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this connected service |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `uri` | [text](/docs/records/fields/types/text/) | &nbsp; |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `extension_id` | text | Type Common.id |
| `extension_name` | text | Type |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |
| `uri` | text | Uri |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in connected service [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `uri:` | [text](/docs/search/#text) | Uri |

### Worklist Columns

These columns are available on connected service [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_extension_id` | Type |
| `c_id` | Id |
| `c_name` | Name |
| `c_updated_at` | Updated |
| `c_uri` | Uri |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

