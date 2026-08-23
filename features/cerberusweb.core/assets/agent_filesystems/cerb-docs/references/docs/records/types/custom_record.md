---
id: "docs-records-types-customrecord"
title: "Custom Record Records"
url: "https://cerb.ai/docs/records/types/custom_record/"
summary: "This page provides detailed information about Custom Records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as `name`, `name_plural`, `uri`, and `updated_at`, and explains how to use these fields for linking and managing records. The page also describes the parameters for record ownership and options, as well as dictionary placeholders for automations, snippets, and API responses. Additionally, it covers search query fields and worklist columns, which are essential for filtering and displaying custom records in Cerb. The document serves as a comprehensive guide for managing and utilizing custom records effectively within the Cerb environment."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Custom Record |
| **Name (plural):** | Custom Records |
| **Alias (uri):** | custom\_record |
| **Identifier (ID):** | cerberusweb.contexts.custom\_record |

- [Records API](#records-api)
  - [params](#params)

- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The singular name of the record; `Issue` |
| **x** | **`name_plural`** | [text](/docs/records/fields/types/text/) | The plural name of the record; `Issues` |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| **x** | **`uri`** | [text](/docs/records/fields/types/text/) | The alias of the record (e.g. `issue`); used in URLs, API, etc. |

#### params

| Key | Value |
| --- | --- |
| `owners[contexts]` | An optional array with one or more of: `cerberusweb.contexts.app`, `cerberusweb.contexts.group`, `cerberusweb.contexts.role`, `cerberusweb.contexts.worker` |
| `options` | An optional array with one or more of: `hide_search`, `avatars`, `attachments`, `comments` |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
| `name` | text | Name |
| `name_plural` | text | Plural |
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
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in custom record [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `name.plural:` | [text](/docs/search/#text) | Plural |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `uri:` | [text](/docs/search/#text) | Uri |

### Worklist Columns

These columns are available on custom record [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_id` | Id |
| `c_name` | Name |
| `c_name_plural` | Plural |
| `c_updated_at` | Updated |
| `c_uri` | Uri |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

