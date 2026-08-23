---
id: "docs-records-types-draft"
title: "Draft Records"
url: "https://cerb.ai/docs/records/types/draft/"
summary: "This page provides detailed information about the draft records in Cerb, including their structure, fields, and usage within the system. It outlines the Records API, specifying the fields available for drafts such as `is_queued`, `name`, `params`, `ticket_id`, and `worker_id`, among others. The page also describes the parameters for different types of drafts, including `mail.compose`, `mail.transactional`, and `ticket.reply/ticket.forward`, detailing the keys and values for each. Additionally, it covers dictionary placeholders for automations and API responses, search query fields for filtering drafts, and worklist columns for organizing draft records. This comprehensive guide is essential for understanding how to manage and utilize draft records within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Draft |
| **Name (plural):** | Drafts |
| **Alias (uri):** | draft |
| **Identifier (ID):** | cerberusweb.contexts.mail.draft |

- [Records API](#records-api)
  - [params (mail.compose)](#params-mailcompose)
  - [params (mail.transactional)](#params-mailtransactional)
  - [params (ticket.reply / ticket.forward)](#params-ticketreply--ticketforward)

- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `is_queued` | [boolean](/docs/records/fields/types/boolean/) | `1` for true, `0` for false |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `name` | [text](/docs/records/fields/types/text/) | The subject line of the draft message |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `queue_delivery_date` | [number](/docs/records/fields/types/number/) | (0-4294967296) |
| &nbsp; | `queue_fails` | [number](/docs/records/fields/types/number/) | (0-4294967296) |
| &nbsp; | `ticket_id` | [number](/docs/records/fields/types/number/) | The ID of the [ticket](/docs/records/types/ticket/) for `ticket.reply` or `ticket.forward` |
| &nbsp; | `to` | [text](/docs/records/fields/types/text/) | The `To:` line of the draft message |
| &nbsp; | `token` | [text](/docs/records/fields/types/text/) | A random unique token for this draft, copied to the eventual message for tracing |
| **x** | **`type`** | [text](/docs/records/fields/types/text/) | The type of draft: `mail.compose`, `mail.transactional`, `ticket.reply`, or `ticket.forward` |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `worker_id` | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) who owns the draft |

#### params (mail.compose)

| Req'd | Key | Value |
| --- | --- | --- |
| &nbsp; | `bcc` | The `Bcc:` recipients |
| &nbsp; | `bucket_id` | The [bucket](/docs/records/types/bucket/) ID to move the ticket to |
| &nbsp; | `cc` | The `Cc:` recipients |
| &nbsp; | `content` | The message content |
| &nbsp; | `custom_fields` | An object with custom field IDs as keys and their values |
| &nbsp; | `custom_fields_uri` | A read-only object with custom field URIs as keys and their values |
| &nbsp; | `format` | `parsedown` (Markdown), or blank for plaintext |
| &nbsp; | `file_ids` | An array of [attachment](/docs/records/types/attachment/) IDs |
| **x** | `group_id` | The [group](/docs/records/types/group/) ID to move the ticket to |
| &nbsp; | `headers` | An array of email headers to set, with header names as keys |
| &nbsp; | `html_template_id` | An optional [HTML template](/docs/records/types/html_template/) ID if `format` is `parsedown` |
| &nbsp; | `message_custom_fields` | An object with message custom field IDs as keys and their values |
| &nbsp; | `message_custom_fields_uri` | A read-only object with message custom field URIs as keys and their values |
| &nbsp; | `options_gpg_encrypt` | `1` to enable PGP encryption, `0` (or omit) to disable |
| &nbsp; | `options_gpg_sign` | `1` to enable PGP signatures, `0` (or omit) to disable |
| &nbsp; | `org_id` | The [org](/docs/records/types/org/) ID to assign |
| &nbsp; | `org_name` | The [org](/docs/records/types/org/) name to assign |
| &nbsp; | `owner_id` | The [worker](/docs/records/types/worker/) ID to assign |
| &nbsp; | `send_at` | The optional timestamp to deliver the message at |
| &nbsp; | `status_id` | `0` (open), `1` (waiting), `2` (closed) |
| &nbsp; | `subject` | The message `Subject:` |
| &nbsp; | `ticket_reopen` | When the status is waiting or closed, the timestamp to reopen at |
| **x** | `to` | The `To:` recipients |

#### params (mail.transactional)

| Req'd | Key | Value |
| --- | --- | --- |
| &nbsp; | `bcc` | The `Bcc:` recipients |
| &nbsp; | `cc` | The `Cc:` recipients |
| &nbsp; | `content` | The message content |
| &nbsp; | `file_ids` | An array of [attachment](/docs/records/types/attachment/) IDs |
| &nbsp; | `format` | `parsedown` (Markdown), or blank for plaintext |
| &nbsp; | `from` | The `From:` sender (uses system default if omitted) |
| &nbsp; | `from_personal` | The personal `From:` sender (uses system default if omitted) |
| &nbsp; | `headers` | An array of email headers to set, with header names as keys |
| &nbsp; | `html_template_id` | An optional [HTML template](/docs/records/types/html_template/) ID if `format` is `parsedown` |
| &nbsp; | `options_gpg_encrypt` | `1` to enable PGP encryption, `0` (or omit) to disable |
| &nbsp; | `options_gpg_sign` | `1` to enable PGP signatures, `0` (or omit) to disable |
| &nbsp; | `reply_to` | The optional `Reply-To:` |
| &nbsp; | `return_path` | The optional `Return-Path:` |
| **x** | `subject` | The message `Subject:` |
| **x** | `to` | The `To:` recipients |

#### params (ticket.reply / ticket.forward)

| Req'd | Key | Value |
| --- | --- | --- |
| &nbsp; | `bcc` | The `Bcc:` recipients |
| &nbsp; | `bucket_id` | The [bucket](/docs/records/types/bucket/) ID to move the ticket to |
| &nbsp; | `cc` | The `Cc:` recipients |
| &nbsp; | `content` | The message content |
| &nbsp; | `custom_fields` | An object with custom field IDs as keys and their values |
| &nbsp; | `custom_fields_uri` | A read-only object with custom field URIs as keys and their values |
| &nbsp; | `file_ids` | An array of [attachment](/docs/records/types/attachment/) IDs |
| &nbsp; | `format` | `parsedown` (Markdown), or blank for plaintext |
| &nbsp; | `group_id` | The [group](/docs/records/types/group/) ID to move the ticket to |
| &nbsp; | `headers` | An array of email headers to set, with header names as keys |
| &nbsp; | `html_template_id` | An optional [HTML template](/docs/records/types/html_template/) ID if `format` is `parsedown` |
| &nbsp; | `in_reply_message_id` | The [message](/docs/records/types/message/) ID being responded to |
| &nbsp; | `is_autoreply` | `1` to avoid saving a copy of the reply on the ticket, `0` (or omit) to include |
| &nbsp; | `message_custom_fields` | An object with message custom field IDs as keys and their values |
| &nbsp; | `message_custom_fields_uri` | A read-only object with message custom field URIs as keys and their values |
| &nbsp; | `options_gpg_encrypt` | `1` to enable PGP encryption, `0` (or omit) to disable |
| &nbsp; | `options_gpg_sign` | `1` to enable PGP signatures, `0` (or omit) to disable |
| &nbsp; | `owner_id` | The [worker](/docs/records/types/worker/) ID to assign |
| &nbsp; | `send_at` | The optional timestamp to deliver the message at |
| &nbsp; | `status_id` | `0` (open), `1` (waiting), `2` (closed) |
| &nbsp; | `subject` | The message `Subject:` |
| &nbsp; | `ticket_reopen` | When the status is waiting or closed, the timestamp to reopen at |
| &nbsp; | `to` | The `To:` recipients |

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
| `params` | dictionary | Params |
| `ticket_` | record | [Ticket](/docs/records/types/ticket/) |
| `to` | text | To |
| `token` | text | Token |
| `type` | text | Type |
| `updated` | date | Updated |
| `worker_` | record | [Worker](/docs/records/types/worker/) |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in draft [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `id:` | [number](/docs/search/#numbers) | Id |
| `is.queued:` | [boolean](/docs/search/#booleans) | Is Queued |
| `name:` | [text](/docs/search/#text) | Name |
| `queue.deliverAt:` | [date](/docs/search/#dates) | Delivery Date |
| `queue.fails:` | [number](/docs/search/#numbers) | # Fails |
| `ticket.id:` | [chooser](/docs/search/#choosers) | [Ticket Id](/docs/records/types/ticket/) |
| `to:` | [text](/docs/search/#text) | To |
| `token:` | [text](/docs/search/#text) | Token |
| `type:` | [text](/docs/search/#text) | Message Type |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |
| `worker.id:` | [chooser](/docs/search/#choosers) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on draft [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `m_hint_to` | To |
| `m_id` | Id |
| `m_is_queued` | Is Queued |
| `m_name` | Name |
| `m_queue_delivery_date` | Delivery Date |
| `m_queue_fails` | # Fails |
| `m_token` | Token |
| `m_type` | Message Type |
| `m_updated` | Updated |
| `m_worker_id` | Worker |

[\< Record Types](/docs/records/types/)

