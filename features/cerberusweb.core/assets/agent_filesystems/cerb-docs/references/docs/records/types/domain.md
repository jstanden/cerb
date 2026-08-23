---
id: "docs-records-types-domain"
title: "Domain Records"
url: "https://cerb.ai/docs/records/types/domain/"
summary: "This page provides detailed information about domain records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as creation and update timestamps, domain name, and server ID. The page also describes dictionary placeholders for automations and API responses, offering fields like context, label, and record URL. Additionally, it lists search query fields for filtering domain records based on attributes like comments, creation date, and server details. Lastly, it specifies worklist columns for displaying domain information, including custom fields and timestamps. This comprehensive guide is essential for managing and interacting with domain records in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Domain |
| **Name (plural):** | Domains |
| **Alias (uri):** | domain |
| **Identifier (ID):** | cerberusweb.contexts.datacenter.domain |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this domain |
| &nbsp; | `server_id` | [number](/docs/records/fields/types/number/) | The ID of the [server](/docs/records/types/server/) linked to this domain |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created` | date | Created |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `server_` | record | [Server](/docs/records/types/server/) |
| `updated` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `contacts` | records | Contacts |
| `contacts_list` | text | Contacts List |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in domain [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `server:` | [record](/docs/search/#deep-search) | [Server](/docs/records/types/server/) |
| `server.id:` | [chooser](/docs/search/#choosers) | [Server](/docs/records/types/server/) |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on domain [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_created` | Created |
| `w_id` | Id |
| `w_name` | Name |
| `w_server_id` | Server |
| `w_updated` | Updated |

[\< Record Types](/docs/records/types/)

