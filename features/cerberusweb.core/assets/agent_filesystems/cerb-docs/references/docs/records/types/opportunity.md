---
id: "docs-records-types-opportunity"
title: "Opportunity Records"
url: "https://cerb.ai/docs/records/types/opportunity/"
summary: "This page provides detailed information about Opportunity Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as amount, currency, status, and timestamps for creation and updates. The page also describes dictionary placeholders for automations and API responses, offering fields like context, label, and record URL. Additionally, it covers search query fields that allow filtering opportunities by attributes like amount, status, and creation date. Lastly, it lists the worklist columns available for displaying opportunity data, including custom fields, closed date, and status."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Opportunity |
| **Name (plural):** | Opportunities |
| **Alias (uri):** | opportunity |
| **Identifier (ID):** | cerberusweb.contexts.opportunity |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `amount` | [float](/docs/records/fields/types/float/) | The amount of the opportunity in the given currency |
| &nbsp; | `amount_currency_id` | [number](/docs/records/fields/types/number/) | The ID of the [currency](/docs/records/types/currency/) |
| &nbsp; | `closed_at` | [timestamp](/docs/records/fields/types/timestamp/) | &nbsp; |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `status` | [text](/docs/records/fields/types/text/) | `open`, `closed_won`, `closed_lost`; alternative to `status_id` |
| &nbsp; | `status_id` | [number](/docs/records/fields/types/number/) | `0` (open), `1` (closed/won), `2` (closed/lost); alternaitve to `status` |
| **x** | **`title`** | [text](/docs/records/fields/types/text/) | The name of the opportunity |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `amount` | currency | Amount |
| `amount_` | record | Amount Label |
| `amount_currency_` | record | [Currency](/docs/records/types/currency/) |
| `closed_at` | date | Closed At |
| `created` | date | Created |
| `id` | number | Id |
| `record_url` | text | Record Url |
| `status` | text | Status |
| `title` | text | Title |
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

These [filters](/docs/search/#filters) are available in opportunity [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `amount:` | [number](/docs/search/#numbers) | Amount |
| `closedDate:` | [date](/docs/search/#dates) | Closed Date |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Created |
| `currency.id:` | [chooser](/docs/search/#choosers) | [Currency](/docs/records/types/currency/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Title |
| `status:` | [number](/docs/search/#numbers) | Status |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on opportunity [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `o_closed_date` | Closed Date |
| `o_created_date` | Created |
| `o_currency_amount` | Amount |
| `o_currency_id` | Currency |
| `o_id` | Id |
| `o_name` | Title |
| `o_status_id` | Status |
| `o_updated_date` | Updated |

[\< Record Types](/docs/records/types/)

