---
id: "docs-records-types-bot"
title: "Bot Records"
url: "https://cerb.ai/docs/records/types/bot/"
summary: "This page provides detailed information about the bot records in Cerb, including their API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and types of data associated with bot records, such as creation and update timestamps, owner information, and status indicators like whether a bot is disabled. The page also describes how these fields can be used in various contexts, such as automations, snippets, and API responses, and provides guidance on how to filter and display bot records using search queries and worklist columns."
tags: ["docs", "docs-records-types"]
---
**Moved to an optional plugin in [12.0](/releases/12.0/), and deprecated.** Legacy bot behaviors now live in the `cerb.behaviors.legacy` plugin. On upgrade it is enabled automatically only if active behaviors exist, so installations without them stay clean. They will not be retired during 12.x, and gained improvements in 12.0. New work should use [automations](/docs/automations/).

| **Name (singular):** | Bot |
| **Name (plural):** | Bots |
| **Alias (uri):** | bot |
| **Identifier (ID):** | cerberusweb.contexts.bot |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `image` | [image](/docs/records/fields/types/image/) | The profile image, base64-encoded in data URI format |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | Is this bot disabled? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `mention_name` | [text](/docs/records/fields/types/text/) | (deprecated) |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this bot |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this bot's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this bot's owner |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `config` | object | Configuration |
| `created_at` | date | Created |
| `id` | number | Id |
| `is_disabled` | boolean | Disabled |
| `mention_name` | text | @Mention |
| `name` | text | Name |
| `owner_` | record | Owner |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `behaviors` | records | Behaviors |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in bot [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `disabled:` | [boolean](/docs/search/#booleans) | Disabled |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `mentionName:` | [text](/docs/search/#text) | @Mention |
| `name:` | [text](/docs/search/#text) | Name |
| `owner:` | [text](/docs/search/#text) | Owner Type |
| `owner.<type>:` | [record](/docs/search/#deep-search) | Owner |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on bot [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `v_at_mention_name` | @Mention |
| `v_created_at` | Created |
| `v_id` | Id |
| `v_is_disabled` | Disabled |
| `v_name` | Name |
| `v_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

