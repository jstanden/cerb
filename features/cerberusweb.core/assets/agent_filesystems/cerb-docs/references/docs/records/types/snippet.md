---
id: "docs-records-types-snippet"
title: "Snippet Records"
url: "https://cerb.ai/docs/records/types/snippet/"
summary: "This page provides detailed information about Snippet Records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, which are essential for managing snippets, such as content, context, owner details, and usage statistics. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive list of fields and their descriptions. Additionally, it covers search query fields that allow users to filter snippets based on various criteria, such as content, owner, and usage. Lastly, it details the worklist columns available for organizing and displaying snippet information, emphasizing the flexibility and customization options for managing snippets in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Snippet |
| **Name (plural):** | Snippets |
| **Alias (uri):** | snippet |
| **Identifier (ID):** | cerberusweb.contexts.snippet |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Snippet [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting uses, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`content`** | [text](/docs/records/fields/types/text/) | The [template](/docs/scripting/) of the snippet |
| &nbsp; | `context` | [text](/docs/records/fields/types/text/) | The [record type](/docs/records/types/) to add the profile tab to |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this snippet's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this snippet's owner |
| &nbsp; | `prompts_kata` | [text](/docs/records/fields/types/text/) | Prompted placeholders in [KATA](/docs/snippets/#prompts) format |
| **x** | **`title`** | [text](/docs/records/fields/types/text/) | The name of the snippet |
| &nbsp; | `total_uses` | [number](/docs/records/fields/types/number/) | The total number of times this snippet has been used by all workers |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `content` | text | Content |
| `context` | text | Context |
| `id` | number | Id |
| `owner_` | record | Owner |
| `title` | text | Title |
| `total_uses` | number | All Uses |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in snippet [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `content:` | [text](/docs/search/#text) | Content |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `myUses:` | [number](/docs/search/#numbers) | My Uses |
| `owner:` | virtual | Owner |
| `owner.app:` | virtual | Owner |
| `owner.bot:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/bot/) |
| `owner.group:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/group/) |
| `owner.role:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/role/) |
| `owner.worker:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/worker/) |
| `title:` | [text](/docs/search/#text) | Title |
| `totalUses:` | [number](/docs/search/#numbers) | All Uses |
| `type:` | virtual | Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `usableBy.worker:` | virtual | Usable by [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on snippet [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `s_context` | Type |
| `s_title` | Title |
| `s_total_uses` | All Uses |
| `s_updated_at` | Updated |
| `suh_my_uses` | My Uses |

[\< Record Types](/docs/records/types/)

