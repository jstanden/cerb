---
id: "docs-records-types-profilewidget"
title: "Profile Widget Records"
url: "https://cerb.ai/docs/records/types/profile_widget/"
summary: "This page provides detailed information about Profile Widget records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `extension_id`, `name`, and `profile_tab_id`, and describes their types and purposes. The page also explains the dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields like `id`, `name`, and `zone`. Additionally, it details the search query fields that can be used to filter profile widget records, such as `id:`, `name:`, and `updated:`, and lists the worklist columns available for organizing these records, including `p_name`, `p_pos`, and `p_zone`. This information is crucial for developers and users who need to manage and customize profile widgets within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Profile Widget |
| **Name (plural):** | Profile Widgets |
| **Alias (uri):** | profile\_widget |
| **Identifier (ID):** | cerberusweb.contexts.profile.widget |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`extension_id`** | [text](/docs/records/fields/types/text/) | [Profile Widget Type](/docs/plugins/extensions/points/cerb.profile.tab.widget/) |
| &nbsp; | `extension_params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this profile widget |
| &nbsp; | `pos` | [number](/docs/records/fields/types/number/) | The order of the widget on the profile; `0` is first (top-left) proceeding in rows then columns |
| **x** | **`profile_tab_id`** | [number](/docs/records/fields/types/number/) | The ID of the [profile tab](/docs/records/types/profile_tab/) dashboard containing this widget |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `width_units` | [number](/docs/records/fields/types/number/) | `1` (25%), `2` (50%), `3` (75%), `4` (100%) |
| &nbsp; | `zone` | [text](/docs/records/fields/types/text/) | The name of the dashboard zone containing the widget; this varies by layout; generally `sidebar` and `content` |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `extension_id` | extension | Extension |
| `id` | number | Id |
| `name` | text | Name |
| `pos` | number | Order |
| `profile_tab_` | record | [Tab](/docs/records/types/profile_tab/) |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |
| `width_units` | number | Width |
| `zone` | text | Zone |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in profile widget [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `pos:` | [number](/docs/search/#numbers) | Order |
| `tab:` | [record](/docs/search/#deep-search) | [Tab](/docs/records/types/profile_tab/) |
| `tab.id:` | [chooser](/docs/search/#choosers) | [Tab](/docs/records/types/profile_tab/) |
| `type:` | [text](/docs/search/#text) | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `width:` | [number](/docs/search/#numbers) | Width Units |
| `zone:` | [text](/docs/search/#text) | Zone |

### Worklist Columns

These columns are available on profile widget [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `p_extension_id` | Type |
| `p_id` | Id |
| `p_name` | Name |
| `p_pos` | Order |
| `p_profile_tab_id` | Tab |
| `p_updated_at` | Updated |
| `p_width_units` | Width Units |
| `p_zone` | Zone |

[\< Record Types](/docs/records/types/)

