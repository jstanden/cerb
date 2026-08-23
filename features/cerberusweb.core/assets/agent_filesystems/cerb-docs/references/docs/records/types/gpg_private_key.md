---
id: "docs-records-types-gpgprivatekey"
title: "PGP Private Key Records"
url: "https://cerb.ai/docs/records/types/gpg_private_key/"
summary: "This page provides detailed information about PGP Private Key records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `fingerprint`, `name`, and `updated_at`, and describes how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. The page also details the search query fields that can be used to filter PGP private key records, such as `expires`, `fingerprint`, and `name`. Additionally, it lists the worklist columns available for organizing and displaying PGP private key data, including custom fields and key attributes like `expires_at` and `fingerprint`. This comprehensive guide is essential for managing and integrating PGP private key records within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Pgp Private Key |
| **Name (plural):** | Pgp Private Keys |
| **Alias (uri):** | gpg\_private\_key |
| **Identifier (ID):** | cerb.contexts.gpg.private.key |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `expires_at` | [timestamp](/docs/records/fields/types/timestamp/) | &nbsp; |
| **x** | **`fingerprint`** | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this pgp private key |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
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
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in pgp private key [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `expires:` | [date](/docs/search/#dates) | Expires |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `fingerprint:` | virtual | Fingerprint |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on pgp private key [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `g_expires_at` | Expires |
| `g_fingerprint` | Fingerprint |
| `g_id` | Id |
| `g_name` | Name |
| `g_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

