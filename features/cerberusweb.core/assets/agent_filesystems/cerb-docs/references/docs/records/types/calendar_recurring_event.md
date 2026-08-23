---
id: "docs-records-types-calendarrecurringevent"
title: "Calendar Recurring Event Records"
url: "https://cerb.ai/docs/records/types/calendar_recurring_event/"
summary: "This page provides detailed information about Calendar Recurring Event Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing recurring events, such as calendar ID, event start and end times, availability status, and recurrence patterns. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields like event name, timezone, and record URL. Additionally, it details the search query fields that can be used to filter calendar recurring events, such as calendar ID, event name, and availability status. Lastly, it lists the worklist columns available for displaying recurring event data, including calendar ID, event name, and recurrence details. This information is crucial for developers and users who need to integrate or manage recurring events within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Calendar Recurring Event |
| **Name (plural):** | Calendar Recurring Events |
| **Alias (uri):** | calendar\_recurring\_event |
| **Identifier (ID):** | cerberusweb.contexts.calendar\_event.recurring |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`calendar_id`** | [number](/docs/records/fields/types/number/) | The parent [calendar](/docs/records/types/calendar/) of this event |
| &nbsp; | `event_end` | [text](/docs/records/fields/types/text/) | The end date/time of the event |
| &nbsp; | `event_start` | [text](/docs/records/fields/types/text/) | The start date/time of the event |
| &nbsp; | `is_available` | [boolean](/docs/records/fields/types/boolean/) | `true` for available; `false` for busy |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of the event |
| **x** | **`patterns`** | [text](/docs/records/fields/types/text/) | One pattern per line |
| &nbsp; | `recur_end` | [timestamp](/docs/records/fields/types/timestamp/) | The end date/time of the recurring range |
| &nbsp; | `recur_start` | [timestamp](/docs/records/fields/types/timestamp/) | The start date/time of the recurring range |
| &nbsp; | `tz` | [text](/docs/records/fields/types/text/) | The timezone of the recurring event (e.g. `America/Los_Angeles`) |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `calendar_` | record | [Calendar](/docs/records/types/calendar/) |
| `calendar_owner_` | record | Calendar Owner |
| `event_end` | text | End |
| `event_start` | text | Start |
| `id` | number | Id |
| `is_available` | boolean | Is Available |
| `name` | text | Name |
| `patterns` | text | Patterns |
| `record_url` | text | Record Url |
| `recur_end` | text | Recur End |
| `recur_start` | text | Recur Start |
| `tz` | text | Timezone |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in calendar recurring event [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `calendar:` | [record](/docs/search/#deep-search) | [Calendar](/docs/records/types/calendar/) |
| `calendar.id:` | [chooser](/docs/search/#choosers) | [Calendar](/docs/records/types/calendar/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Event Name |
| `status:` | [boolean](/docs/search/#booleans) | Is Available |
| `timezone:` | [text](/docs/search/#text) | Timezone |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on calendar recurring event [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_calendar_id` | Calendar |
| `c_event_end` | End |
| `c_event_name` | Event Name |
| `c_event_start` | Start |
| `c_id` | Id |
| `c_is_available` | Is Available |
| `c_patterns` | Patterns |
| `c_recur_end` | Recur End |
| `c_recur_start` | Recur Start |
| `c_tz` | Timezone |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

