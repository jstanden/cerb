---
id: "docs-records-types-calendar"
title: "Calendar Records"
url: "https://cerb.ai/docs/records/types/calendar/"
summary: "This page provides detailed information about Calendar Records in Cerb, including their structure, fields, and functionalities. It outlines the Records API, specifying required and optional fields such as `name`, `owner__context`, and `owner_id`, along with parameters for customization like event colors and synchronization settings. The page also describes dictionary placeholders available for automations and API responses, offering fields like `id`, `name`, and `timezone`. Additionally, it covers search query fields for filtering calendar records and lists available worklist columns for organizing calendar data. The document serves as a comprehensive guide for managing and utilizing calendar records within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Calendar |
| **Name (plural):** | Calendars |
| **Alias (uri):** | calendar |
| **Identifier (ID):** | cerberusweb.contexts.calendar |

- [Records API](#records-api)
  - [params](#params)
  - [series](#series)

- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this calendar |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this calendar's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this calendar's owner |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `timezone` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

#### params

| Key | Value |
| --- | --- |
| `color_available` | The hex color code for available events (e.g. `#a0d95b`) |
| `color_busy` | The hex color code for busy events (e.g. `#c8c8c8`) |
| `hide_start_time` | `0` to show event start times, `1` to disable |
| `manual_disabled` | `0` to enable manual event creation, `1` to disable |
| `series` | An optional array of **series** objects |
| `start_on_mon` | `0` to start weeks on Sunday, `1` to start on Monday |
| `sync_enabled` | `0` to disable event synchronization, `1` to enable |

#### series

| Key | Value |
| --- | --- |
| `datasource` | `calendar.datasource.worklist` |
| `color` | &nbsp; |
| `field_end_date` | &nbsp; |
| `field_end_date_offset` | &nbsp; |
| `field_start_date` | &nbsp; |
| `field_start_date_offset` | &nbsp; |
| `is_available` | &nbsp; |
| `label` | &nbsp; |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `id` | number | Id |
| `name` | text | Name |
| `owner_` | record | Owner |
| `record_url` | text | Record Url |
| `timezone` | text | Timezone |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `events` | &nbsp; | Events |
| `events_occluded` | &nbsp; | Events (Occluded) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `scope` | &nbsp; | Scope |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |
| `weeks` | &nbsp; | Weeks |
| `weeks_events` | &nbsp; | Weeks Events |
| `weeks_events_occluded` | &nbsp; | Weeks Events (Occluded) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in calendar [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `owner:` | virtual | Owner |
| `owner.app:` | virtual | Owner |
| `owner.bot:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/bot/) |
| `owner.group:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/group/) |
| `owner.role:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/role/) |
| `owner.worker:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/worker/) |
| `timezone:` | [text](/docs/search/#text) | Timezone |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |
| `workerAvailability:` | [record](/docs/search/#deep-search) | [Workers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on calendar [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `c_id` | Id |
| `c_name` | Name |
| `c_timezone` | Timezone |
| `c_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

