---
id: "docs-records-types-call"
title: "Call Records"
url: "https://cerb.ai/docs/records/types/call/"
summary: "This page provides detailed information about the call records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as creation and update timestamps, call resolution status, and phone numbers. The page also describes dictionary placeholders for automations and API responses, offering fields like record type, subject, and links. Additionally, it lists search query fields that can be used to filter call records based on various criteria, such as comments, creation date, and call status. Lastly, it details the columns available in call worklists, which include fields like creation date, call status, and custom fields. This comprehensive guide is essential for managing and utilizing call records effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Call |
| **Name (plural):** | Calls |
| **Alias (uri):** | call |
| **Identifier (ID):** | cerberusweb.contexts.call |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `is_closed` | [boolean](/docs/records/fields/types/boolean/) | Is this call resolved? |
| &nbsp; | `is_outgoing` | [boolean](/docs/records/fields/types/boolean/) | `0` (incoming), `1` (outgoing) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `phone` | [text](/docs/records/fields/types/text/) | The phone number of the caller or target |
| **x** | **`subject`** | [text](/docs/records/fields/types/text/) | A brief summary of the call |
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
| `is_closed` | boolean | Is Closed |
| `is_outgoing` | boolean | Is Outgoing |
| `phone` | text | Phone |
| `record_url` | text | Record Url |
| `subject` | text | Subject |
| `updated` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in call [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isClosed:` | [boolean](/docs/search/#booleans) | Is Closed |
| `isOutgoing:` | [boolean](/docs/search/#booleans) | Is Outgoing |
| `links:` | [links](/docs/search/#links) | Record Links |
| `phone:` | [text](/docs/search/#text) | Phone |
| `subject:` | [text](/docs/search/#text) | Subject |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on call [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_created_date` | Created |
| `c_is_closed` | Is Closed |
| `c_is_outgoing` | Is Outgoing |
| `c_phone` | Phone |
| `c_subject` | Subject |
| `c_updated_date` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

