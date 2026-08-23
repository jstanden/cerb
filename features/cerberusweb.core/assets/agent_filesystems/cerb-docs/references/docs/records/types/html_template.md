---
id: "docs-records-types-htmltemplate"
title: "Email Template Records"
url: "https://cerb.ai/docs/records/types/html_template/"
summary: "This page provides detailed information about email template records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as content, name, owner context, and updated timestamp, which are essential for managing email templates. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like content, name, and record URL. Additionally, it covers search query fields that allow users to filter email templates based on various criteria, such as comments, content, and updated date. Lastly, it lists the worklist columns available for organizing and displaying email templates, including custom fields, content, and signature information. This comprehensive guide is crucial for users looking to effectively manage and utilize email templates within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Email Template |
| **Name (plural):** | Email Templates |
| **Alias (uri):** | html\_template |
| **Identifier (ID):** | cerberusweb.contexts.mail.html\_template |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `content` | [text](/docs/records/fields/types/text/) | The content of the template |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this email template |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this email template's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this email template's owner |
| &nbsp; | `signature_id` | [number](/docs/records/fields/types/number/) | The optional [email signature](/docs/records/types/email_signature/) of this template |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `content` | text | Content |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `signature_` | record | [Signature](/docs/records/types/email_signature/) |
| `signature_owner_` | record | Signature Owner |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `attachments` | attachments | [Attachments](/docs/guide/developers/dictionaries/#key-expansion) |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in email template [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `content:` | [text](/docs/search/#text) | Content |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `signature.id:` | [number](/docs/search/#numbers) | Signature |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on email template [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `m_content` | Content |
| `m_id` | Id |
| `m_name` | Name |
| `m_signature_id` | Signature |
| `m_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

