---
id: "docs-records-types-currency"
title: "Currency Records"
url: "https://cerb.ai/docs/records/types/currency/"
summary: "This page provides detailed information about currency records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as currency code, decimal places, default status, and symbols. The page also describes dictionary placeholders for automations and API responses, offering fields like context, label, and record URL. Additionally, it covers search query fields for filtering currency records and worklist columns for organizing and displaying currency data. The document serves as a comprehensive guide for managing and utilizing currency records within Cerb's platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Currency |
| **Name (plural):** | Currencies |
| **Alias (uri):** | currency |
| **Identifier (ID):** | cerberusweb.contexts.currency |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `code` | [text](/docs/records/fields/types/text/) | Currency code; e.g. `USD` |
| &nbsp; | `decimal_at` | [number](/docs/records/fields/types/number/) | The number of significant decimal places (0-16); e.g. `2` for `0.00` |
| &nbsp; | `is_default` | [boolean](/docs/records/fields/types/boolean/) | Is this the default currency? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `name` | [text](/docs/records/fields/types/text/) | The singular name of this currency; `Dollar` |
| &nbsp; | `name_plural` | [text](/docs/records/fields/types/text/) | The plural name of this currency; `Dollars` |
| &nbsp; | `symbol` | [text](/docs/records/fields/types/text/) | Symbol; `$`, `£`, `€` |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `code` | text | Code |
| `decimal_at` | number | Decimal Places |
| `id` | number | Id |
| `is_default` | boolean | Default |
| `name` | text | Name |
| `name_plural` | text | Plural |
| `record_url` | text | Record Url |
| `symbol` | text | Symbol |
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

These [filters](/docs/search/#filters) are available in currency [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `code:` | [text](/docs/search/#text) | Code |
| `decimalPlaces:` | [number](/docs/search/#numbers) | Decimal Places |
| `default:` | [boolean](/docs/search/#booleans) | Default |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `symbol:` | [text](/docs/search/#text) | Symbol |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on currency [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_code` | Code |
| `c_decimal_at` | Decimal Places |
| `c_id` | Id |
| `c_is_default` | Default |
| `c_name` | Name |
| `c_name_plural` | Plural |
| `c_symbol` | Symbol |
| `c_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

