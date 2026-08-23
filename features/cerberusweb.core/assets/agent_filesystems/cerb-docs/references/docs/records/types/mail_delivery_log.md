---
id: "docs-records-types-maildeliverylog"
title: "Email Delivery Log Records"
url: "https://cerb.ai/docs/records/types/mail_delivery_log/"
summary: "This page provides detailed information about the Email Delivery Log records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for creating and managing email delivery logs. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a comprehensive guide to the available fields and their types. Additionally, it details the search query fields that can be used to filter email delivery logs, as well as the worklist columns that can be displayed in email delivery log worklists. This information is crucial for users looking to effectively manage and utilize email delivery logs within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Email Delivery Log |
| **Name (plural):** | Email Delivery Logs |
| **Alias (uri):** | mail\_delivery\_log |
| **Identifier (ID):** | cerb.contexts.mail.delivery.log |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `from_id` | [number](/docs/records/fields/types/number/) | &nbsp; |
| &nbsp; | `header_message_id` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `mail_transport_id` | [number](/docs/records/fields/types/number/) | &nbsp; |
| &nbsp; | `status_id` | [number](/docs/records/fields/types/number/) | (0-2) |
| &nbsp; | `status_message` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `subject` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `to` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `type` | [text](/docs/records/fields/types/text/) | &nbsp; |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `header_message_id` | text | Header Message-Id |
| `id` | number | Id |
| `record_url` | text | Record Url |
| `status_id` | number | Status |
| `status_message` | text | Status Message |
| `subject` | text | Subject |
| `to` | text | To |
| `type` | text | Type |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `properties` | object | Properties |

### Search Query Fields

These [filters](/docs/search/#filters) are available in email delivery log [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `from:` | [record](/docs/search/#deep-search) | [From](/docs/records/types/address/) |
| `from.id:` | [chooser](/docs/search/#choosers) | [From](/docs/records/types/address/) |
| `header.messageId:` | [text](/docs/search/#text) | Header Message-Id |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `mailTransport:` | [record](/docs/search/#deep-search) | [Mailtransport](/docs/records/types/mail_transport/) |
| `mailTransport.id:` | [chooser](/docs/search/#choosers) | [Email Transport](/docs/records/types/mail_transport/) |
| `status:` | virtual | Status |
| `status.id:` | [number](/docs/search/#numbers) | Status |
| `subject:` | [text](/docs/search/#text) | Subject |
| `to:` | [text](/docs/search/#text) | To |
| `type:` | [text](/docs/search/#text) | Type |

### Worklist Columns

These columns are available on email delivery log [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `m_created_at` | Created |
| `m_from_id` | From |
| `m_header_message_id` | Header Message-Id |
| `m_id` | Id |
| `m_mail_transport_id` | Email Transport |
| `m_status_id` | Status |
| `m_status_message` | Status Message |
| `m_subject` | Subject |
| `m_to` | To |
| `m_type` | Type |

[\< Record Types](/docs/records/types/)

