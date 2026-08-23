---
id: "docs-records-types-feeditem"
title: "Feed Item Records"
url: "https://cerb.ai/docs/records/types/feed_item/"
summary: "This page provides detailed information about Feed Item Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as `created_at`, `feed_id`, `guid`, `is_closed`, `title`, and `url`, which are essential for managing feed items. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like `_context`, `_label`, `created_at`, and `record_url`. Additionally, it covers search query fields that allow users to filter feed items based on criteria like comments, creation date, feed, and title. Lastly, it lists the worklist columns available for organizing feed items, including custom fields, creation date, feed ID, and title. This comprehensive guide is crucial for developers and users looking to effectively manage and interact with feed items in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Feed Item |
| **Name (plural):** | Feed Items |
| **Alias (uri):** | feed\_item |
| **Identifier (ID):** | cerberusweb.contexts.feed.item |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| **x** | **`feed_id`** | [number](/docs/records/fields/types/number/) | The ID of the [feed](/docs/records/types/feed/) containing this item |
| &nbsp; | `guid` | [text](/docs/records/fields/types/text/) | The globally unique ID of this item in the feed |
| &nbsp; | `is_closed` | [boolean](/docs/records/fields/types/boolean/) | Is this item viewed/resolved? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`title`** | [text](/docs/records/fields/types/text/) | The title of this feed item |
| **x** | **`url`** | [text](/docs/records/fields/types/text/) | The URL of this feed item |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `feed_` | record | [Feed](/docs/records/types/feed/) |
| `guid` | text | Guid |
| `id` | number | Id |
| `is_closed` | boolean | Is Closed |
| `record_url` | text | Record Url |
| `title` | text | Title |
| `url` | text | Url |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in feed item [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Created |
| `feed:` | [record](/docs/search/#deep-search) | [Feed](/docs/records/types/feed/) |
| `feed.id:` | [chooser](/docs/search/#choosers) | [Feed](/docs/records/types/feed/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isClosed:` | [boolean](/docs/search/#booleans) | Is Closed |
| `links:` | [links](/docs/search/#links) | Record Links |
| `title:` | [text](/docs/search/#text) | Title |
| `url:` | [text](/docs/search/#text) | Url |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on feed item [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `fi_created_date` | Created |
| `fi_feed_id` | Feed |
| `fi_is_closed` | Is Closed |
| `fi_title` | Title |
| `fi_url` | Url |

[\< Record Types](/docs/records/types/)

