---
id: "docs-records-types-profiletab"
title: "Profile Tab Records"
url: "https://cerb.ai/docs/records/types/profile_tab/"
summary: "This page provides detailed information about Profile Tab records in Cerb, including their structure and usage within the system. It covers the fields available in the Records API, which are essential for adding profile tabs to specific record types, and includes required fields such as context, extension ID, and name. The page also outlines dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like context, extension ID, and updated timestamps. Additionally, it describes search query fields that facilitate filtering profile tabs based on criteria like fieldset, ID, and name. Lastly, it lists the worklist columns available for organizing and displaying profile tab data, including custom fields and update timestamps. This comprehensive guide is crucial for developers and users looking to integrate and manage profile tabs within Cerb effectively."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Profile Tab |
| **Name (plural):** | Profile Tabs |
| **Alias (uri):** | profile\_tab |
| **Identifier (ID):** | cerberusweb.contexts.profile.tab |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) to add the profile tab to |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | [Profile Tab Type](/docs/plugins/extensions/points/cerb.profile.tab/) |
| &nbsp; | `extension_params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this profile tab |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `context` | context | Record |
| `extension_id` | extension | Type |
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

These [filters](/docs/search/#filters) are available in profile tab [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `record:` | [text](/docs/search/#text) | Record |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on profile tab [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `p_context` | Record |
| `p_extension_id` | Type |
| `p_id` | Id |
| `p_name` | Name |
| `p_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

