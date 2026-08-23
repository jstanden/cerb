---
id: "docs-records-types-resource"
title: "Resource Records"
url: "https://cerb.ai/docs/records/types/resource/"
summary: "This page provides detailed information about Resource Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing resources, such as `automation_kata`, `content`, `extension_id`, and `name`. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields like `description`, `is_dynamic`, and `record_url`. Additionally, it covers search query fields that facilitate filtering resources based on attributes like `cacheUntil`, `description`, and `type`. Lastly, it details the worklist columns available for resource management, providing insights into fields like `r_cache_until`, `r_name`, and `r_updated_at`. This resource is crucial for developers and users looking to effectively utilize and manage resources within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Resource |
| **Name (plural):** | Resources |
| **Alias (uri):** | resource |
| **Identifier (ID):** | cerb.contexts.resource |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

Resource choosers accept a scope query, so a chooser can be narrowed to one resource type and offer only relevant records.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `automation_kata` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `cache_until` | [timestamp](/docs/records/fields/types/timestamp/) | &nbsp; |
| &nbsp; | `content` | [text](/docs/records/fields/types/text/) | The optional content of this resource. For text, use a string. For binary, base64-encode in data URI format. This may also be an automation resource URI (e.g. `cerb:automation_resource:TOKEN`) |
| &nbsp; | `description` | [text](/docs/records/fields/types/text/) | &nbsp; |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | A [cerb.resource.type](/docs/plugins/extensions/points/cerb.resource.type/#extensions) extension ID. |
| &nbsp; | `is_dynamic` | [boolean](/docs/records/fields/types/boolean/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this resource |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `automation_kata` | text | Automation |
| `cache_until` | date | Cache Until |
| `description` | text | Description |
| `extension_id` | text | Type |
| `id` | number | Id |
| `is_dynamic` | boolean | Is Dynamic |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in resource [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `cacheUntil:` | [date](/docs/search/#dates) | Cache |
| `description:` | [text](/docs/search/#text) | Description |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isDynamic:` | [boolean](/docs/search/#booleans) | Is Dynamic |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `size:` | [number](/docs/search/#numbers) | Size |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on resource [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `r_cache_until` | Cache |
| `r_description` | Description |
| `r_extension_id` | Type |
| `r_id` | Id |
| `r_is_dynamic` | Is Dynamic |
| `r_name` | Name |
| `r_storage_extension` | Storage Extension |
| `r_storage_key` | Storage Key |
| `r_storage_profile_id` | Storage Profile |
| `r_storage_size` | Size |
| `r_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

