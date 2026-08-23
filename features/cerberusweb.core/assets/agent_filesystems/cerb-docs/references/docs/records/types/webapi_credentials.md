---
id: "docs-records-types-webapicredentials"
title: "Web Api Credentials Records"
url: "https://cerb.ai/docs/records/types/webapi_credentials/"
summary: "This page provides detailed information about Web API Credentials Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `name`, `worker_id`, and `updated_at`, and explains how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. The page also describes the search query fields that can be used to filter web API credentials, such as `accessKey`, `name`, and `worker`, and lists the worklist columns available for organizing and displaying these records, including custom fields and worker information."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Api Key |
| **Name (plural):** | Api Keys |
| **Alias (uri):** | webapi\_credentials |
| **Identifier (ID):** | cerberusweb.contexts.webapi.credential |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this api key |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| **x** | **`worker_id`** | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) who owns these API credentials |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `access_key` | text | Access Key |
| `id` | number | Id |
| `name` | text | Name |
| `params` | object | Params |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |
| `worker_` | record | [Worker](/docs/records/types/worker/) |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in web api credentials [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `accessKey:` | [text](/docs/search/#text) | Access Key |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |
| `worker.id:` | [chooser](/docs/search/#choosers) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on web api credentials [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_access_key` | Access Key |
| `w_name` | Name |
| `w_updated_at` | Updated |
| `w_worker_id` | Worker |

[\< Record Types](/docs/records/types/)

