---
id: "docs-records-types-comment"
title: "Comment Records"
url: "https://cerb.ai/docs/records/types/comment/"
summary: "This page provides detailed information about the structure and functionality of comment records in Cerb. It outlines the fields available in the Records API, including required fields such as author context, author ID, comment text, target context, and target ID. The page also describes dictionary placeholders used in automations, snippets, and API responses, highlighting fields like author, comment content, and creation date. Additionally, it covers search query fields that allow filtering comments based on various criteria such as attachments, author, comment content, creation date, and more. Lastly, it lists the worklist columns available for organizing comment data, including target, creation date, ID, markdown status, and custom fields. This comprehensive guide is essential for understanding and utilizing comment records within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Comment |
| **Name (plural):** | Comments |
| **Alias (uri):** | comment |
| **Identifier (ID):** | cerberusweb.contexts.comment |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`author__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of the comment's author |
| **x** | **`author_id`** | [number](/docs/records/fields/types/number/) | The ID of the comment's author |
| **x** | **`comment`** | [text](/docs/records/fields/types/text/) | The text of the comment |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `is_markdown` | [boolean](/docs/records/fields/types/boolean/) | `0`=plaintext, `1`=Markdown |
| &nbsp; | `is_pinned` | [boolean](/docs/records/fields/types/boolean/) | `0`=not pinned, `1`=pinned |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`target__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of the target record |
| **x** | **`target_id`** | [number](/docs/records/fields/types/number/) | The ID of the target record |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `author_` | record | Author |
| `comment` | text | Content |
| `created` | date | Created |
| `id` | number | Id |
| `target_` | record | Target |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `attachments` | attachments | [Attachments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in comment [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `attachments:` | [record](/docs/search/#deep-search) | [Attachments](/docs/records/types/attachment/) |
| `author:` | [text](/docs/search/#text) | Actor |
| `author.<type>:` | [record](/docs/search/#deep-search) | Actor |
| `comment:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isMarkdown:` | [boolean](/docs/search/#booleans) | Markdown |
| `isPinned:` | [boolean](/docs/search/#booleans) | Is Pinned |
| `links:` | [links](/docs/search/#links) | Record Links |
| `on:` | [text](/docs/search/#text) | On Type |
| `on.<type>:` | [record](/docs/search/#deep-search) | Target |

### Worklist Columns

These columns are available on comment [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_target` | Target |
| `c_created` | Created |
| `c_id` | Id |
| `c_is_markdown` | Markdown |
| `c_is_pinned` | Is Pinned |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

