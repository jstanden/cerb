---
id: "docs-records-types-webhooklistener"
title: "Webhook Listener Records"
url: "https://cerb.ai/docs/records/types/webhook_listener/"
summary: "This page provides detailed information about Webhook Listener Records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as `guid`, `name`, and `updated_at`, and explains their types and purposes. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields like `automations_kata`, `guid`, and `record_url`. Additionally, it details the search query fields that can be used to filter webhook listener records, such as `guid:`, `id:`, and `name:`, and lists the worklist columns available for organizing these records, including custom fields and update timestamps. This information is crucial for users looking to integrate and manage webhooks effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Webhook |
| **Name (plural):** | Webhooks |
| **Alias (uri):** | webhook\_listener |
| **Identifier (ID):** | cerberusweb.contexts.webhook\_listener |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Webhook Listener [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting invocations, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `automations_kata` | [object](/docs/records/fields/types/object/) | KATA object |
| **x** | **`guid`** | [text](/docs/records/fields/types/text/) | The random unique alias of the webhook used in its URL; automatically generated if blank |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this webhook |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `automations_kata` | text | Automations |
| `guid` | text | Guid |
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

These [filters](/docs/search/#filters) are available in webhook listener [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `guid:` | [text](/docs/search/#text) | Url |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on webhook listener [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_guid` | Url |
| `w_id` | Id |
| `w_name` | Name |
| `w_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

