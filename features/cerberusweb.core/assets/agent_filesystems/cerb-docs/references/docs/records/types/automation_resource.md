---
id: "docs-records-types-automationresource"
title: "Automation Resource Records"
url: "https://cerb.ai/docs/records/types/automation_resource/"
summary: "This page provides detailed information about Automation Resource Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as links, mime type, name, token, and updated timestamp. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering fields like context, label, type, id, mime type, name, record URL, size, token, and updated date. Additionally, it lists search query fields that can filter automation resource searches, including fieldset, id, links, mimetype, name, size, token, and updated date. Lastly, it details the worklist columns available for automation resources, which include custom fields, id, mime type, name, storage extension, storage key, storage profile, size, token, and updated timestamp."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Automation Resource |
| **Name (plural):** | Automation Resources |
| **Alias (uri):** | automation\_resource |
| **Identifier (ID):** | cerb.contexts.automation.resource |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `mime_type` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `name` | [text](/docs/records/fields/types/text/) | The name of this automation resource |
| **x** | **`token`** | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
| `mime_type` | text | Mime Type |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `size` | number | Size |
| `token` | text | Token |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in automation resource [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `mimetype:` | [text](/docs/search/#text) | Mime Type |
| `name:` | [text](/docs/search/#text) | Name |
| `size:` | [number](/docs/search/#numbers) | Size |
| `token:` | [text](/docs/search/#text) | Token |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on automation resource [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `r_id` | Id |
| `r_mime_type` | Mime Type |
| `r_name` | Name |
| `r_storage_extension` | Storage Extension |
| `r_storage_key` | Storage Key |
| `r_storage_profile_id` | Storage Profile |
| `r_storage_size` | Size |
| `r_token` | Token |
| `r_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

