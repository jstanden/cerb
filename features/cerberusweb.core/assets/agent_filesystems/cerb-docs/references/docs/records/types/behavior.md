---
id: "docs-records-types-behavior"
title: "Behavior Records"
url: "https://cerb.ai/docs/records/types/behavior/"
summary: "This page provides detailed information about behavior records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as bot ID, event point, and priority, and explains how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. The page also describes the search query fields that can be used to filter behaviors, such as by bot, event, and priority, and lists the columns available in behavior worklists for organizing and displaying behavior data. The document serves as a comprehensive guide for managing and interacting with behavior records in Cerb."
tags: ["docs", "docs-records-types"]
---
**Moved to an optional plugin in [12.0](/releases/12.0/), and deprecated.** Legacy bot behaviors now live in the `cerb.behaviors.legacy` plugin. On upgrade it is enabled automatically only if active behaviors exist, so installations without them stay clean. They will not be retired during 12.x, and gained improvements in 12.0. New work should use [automations](/docs/automations/).

### Change history

Legacy behaviors now track a change history the way [automations](/docs/automations/) do. From a behavior tree, click the event and choose **Edit History** to compare any two revisions in the diff viewer.

| **Name (singular):** | Behavior |
| **Name (plural):** | Behaviors |
| **Alias (uri):** | behavior |
| **Identifier (ID):** | cerberusweb.contexts.behavior |

- [Change history](#change-history)
- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Behavior [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting runs and duration, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`bot_id`** | [number](/docs/records/fields/types/number/) | [Bot](/docs/records/types/bot/) |
| **x** | **`event_point`** | [text](/docs/records/fields/types/text/) | The event of the behavior |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | Is this behavior disabled? |
| &nbsp; | `is_private` | [boolean](/docs/records/fields/types/boolean/) | Is this behavior only visible to the parent bot? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The behavior's name |
| &nbsp; | `priority` | [number](/docs/records/fields/types/number/) | Any positive number; `0` is highest priority |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `uri` | [text](/docs/records/fields/types/text/) | &nbsp; |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `bot_` | record | [Bot](/docs/records/types/bot/) |
| `bot_owner_` | record | Bot Owner |
| `event_point` | text | Event |
| `event_point_name` | text | Event |
| `id` | number | Id |
| `is_disabled` | boolean | Is Disabled |
| `is_private` | boolean | Is Private |
| `name` | text | Name |
| `priority` | number | Priority |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |
| `uri` | text | Uri |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in behavior [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `bot:` | [record](/docs/search/#deep-search) | [Bot](/docs/records/types/bot/) |
| `bot.id:` | [chooser](/docs/search/#choosers) | [Bot](/docs/records/types/bot/) |
| `disabled:` | [boolean](/docs/search/#booleans) | Is Disabled |
| `event:` | [text](/docs/search/#text) | Event |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Title |
| `priority:` | [number](/docs/search/#numbers) | Priority |
| `private:` | [boolean](/docs/search/#booleans) | Is Private |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `uri:` | [text](/docs/search/#text) | Uri |
| `usableBy.bot:` | [chooser](/docs/search/#choosers) | [Usableby Bot](/docs/records/types/bot/) |

### Worklist Columns

These columns are available on behavior [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_has_fieldset` | Fieldset |
| `*_workers` | Watchers |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `t_bot_id` | Bot |
| `t_event_point` | Event |
| `t_id` | Id |
| `t_is_disabled` | Is Disabled |
| `t_is_private` | Is Private |
| `t_priority` | Priority |
| `t_title` | Title |
| `t_updated_at` | Updated |
| `t_uri` | Uri |

[\< Record Types](/docs/records/types/)

