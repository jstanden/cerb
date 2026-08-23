---
id: "docs-records-types-filebundle"
title: "File Bundle Records"
url: "https://cerb.ai/docs/records/types/file_bundle/"
summary: "This page provides detailed information about File Bundle Records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as name, owner context, and tags, and explains how these fields can be utilized in automations, snippets, and API responses through dictionary placeholders. The page also describes the search query fields that can be used to filter file bundles, such as by comments, owner, and tags. Additionally, it lists the worklist columns available for organizing file bundle data, including owner, custom fields, and update timestamps. This comprehensive guide is essential for users looking to manage and interact with file bundles effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
**Moved to an optional plugin in [12.0](/releases/12.0/).** File bundles now live in the `cerb.file_bundles` plugin, which is **disabled by default**. On upgrade the plugin is enabled automatically only if you already have file bundle records; otherwise the empty table is dropped and the plugin recreates it if an administrator enables it later. Installations that never used file bundles stay clean.

| **Name (singular):** | File Bundle |
| **Name (plural):** | File Bundles |
| **Alias (uri):** | file\_bundle |
| **Identifier (ID):** | cerberusweb.contexts.file\_bundle |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this file bundle |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this file bundle's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this file bundle's owner |
| &nbsp; | `tag` | [text](/docs/records/fields/types/text/) | A human-friendly nickname for the bundle; e.g. `tax_forms` |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

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
| `tag` | text | Tag |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `attachments` | attachments | [Attachments](/docs/guide/developers/dictionaries/#key-expansion) |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in file bundle [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
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
| `tag:` | [text](/docs/search/#text) | Tag |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `usableBy.worker:` | virtual | Usable by [Worker](/docs/records/types/worker/) |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on file bundle [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `f_id` | Id |
| `f_name` | Name |
| `f_tag` | Tag |
| `f_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

