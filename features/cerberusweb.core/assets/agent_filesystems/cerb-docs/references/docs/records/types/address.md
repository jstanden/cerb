---
id: "docs-records-types-address"
title: "Email Address Records"
url: "https://cerb.ai/docs/records/types/address/"
summary: "This page provides detailed information about email address records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, such as contact ID, email, host, and various status indicators like whether an email is banned, defunct, or trusted. The page also describes dictionary placeholders for automations and API responses, offering fields like address, contact, and organization details. Additionally, it covers search query fields that allow filtering email addresses based on criteria like creation date, email content, and associated records. Lastly, it lists worklist columns that can be used to organize and display email address data, including fields for contact, email, host, and custom fields."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Email Address |
| **Name (plural):** | Email Addresses |
| **Alias (uri):** | address |
| **Identifier (ID):** | cerberusweb.contexts.address |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `contact_id` | [number](/docs/records/fields/types/number/) | The [contact](/docs/records/types/contact/) linked to this email |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| **x** | **`email`** | [text](/docs/records/fields/types/text/) | An email address |
| &nbsp; | `host` | [text](/docs/records/fields/types/text/) | The hostname of the email address |
| &nbsp; | `is_banned` | [boolean](/docs/records/fields/types/boolean/) | Is incoming email blocked? |
| &nbsp; | `is_defunct` | [boolean](/docs/records/fields/types/boolean/) | Is this address non-deliverable? |
| &nbsp; | `is_trusted` | [boolean](/docs/records/fields/types/boolean/) | Is this sender trusted to display external images and links? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `mail_transport_id` | [number](/docs/records/fields/types/number/) | If this address is used for outgoing mail, the [mail transport](/docs/records/types/mail_transport/) to use; otherwise empty |
| &nbsp; | `org` | [text](/docs/records/fields/types/text/) | The exact name of the [organization](/docs/records/types/org/) linked to this email address; alternative to `org_id` |
| &nbsp; | `org_id` | [number](/docs/records/fields/types/number/) | The [organization](/docs/records/types/org/) linked to this email |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `worker_id` | [number](/docs/records/fields/types/number/) | Is this address owned by a [worker](/docs/records/types/worker/)? |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `address` | text | Address |
| `contact_` | record | [Contact](/docs/records/types/contact/) |
| `created_at` | date | Created |
| `full_name` | text | Full Name |
| `host` | text | Host |
| `id` | number | Id |
| `is_banned` | boolean | Is Banned |
| `is_contact` | boolean | Is Contact |
| `is_defunct` | boolean | Is Defunct |
| `is_trusted` | boolean | Is Trusted |
| `mail_transport_` | record | [Mail Transport](/docs/records/types/mail_transport/) |
| `num_nonspam` | number | # Nonspam |
| `num_spam` | number | # Spam |
| `org_` | record | [Org](/docs/records/types/org/) |
| `record_url` | text | Record Url |
| `updated` | date | Updated |
| `worker_` | record | [Worker](/docs/records/types/worker/) |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `last_recipient_message` | record | Latest [Message](/docs/records/types/message/) Received To |
| `last_sender_message` | record | Latest [Message](/docs/records/types/message/) Sent From |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in email address [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `contact:` | [record](/docs/search/#deep-search) | [Contact](/docs/records/types/contact/) |
| `contact.id:` | [chooser](/docs/search/#choosers) | [Contact](/docs/records/types/contact/) |
| `created:` | [date](/docs/search/#dates) | Created |
| `email:` | [text](/docs/search/#text) | Email |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `host:` | [text](/docs/search/#text) | Host |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isBanned:` | [boolean](/docs/search/#booleans) | Is Banned |
| `isDefunct:` | [boolean](/docs/search/#booleans) | Is Defunct |
| `isTrusted:` | [boolean](/docs/search/#booleans) | Is Trusted |
| `links:` | [links](/docs/search/#links) | Record Links |
| `mailTransport.id:` | [chooser](/docs/search/#choosers) | [Email Transport](/docs/records/types/mail_transport/) |
| `nonspam:` | [number](/docs/search/#numbers) | # Nonspam |
| `org:` | [record](/docs/search/#deep-search) | [Org](/docs/records/types/org/) |
| `org.id:` | [chooser](/docs/search/#choosers) | [Organization Id](/docs/records/types/org/) |
| `spam:` | [number](/docs/search/#numbers) | # Spam |
| `ticket:` | [record](/docs/search/#deep-search) | [Ticket](/docs/records/types/ticket/) |
| `ticket.id:` | [chooser](/docs/search/#choosers) | [Ticket](/docs/records/types/ticket/) |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |
| `worker:` | [record](/docs/search/#deep-search) | [Worker](/docs/records/types/worker/) |
| `worker.id:` | [chooser](/docs/search/#choosers) | [Worker](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on email address [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `a_contact_id` | Contact |
| `a_created_at` | Created |
| `a_email` | Email |
| `a_host` | Host |
| `a_id` | Id |
| `a_is_banned` | Is Banned |
| `a_is_defunct` | Is Defunct |
| `a_is_trusted` | Is Trusted |
| `a_mail_transport_id` | Email Transport |
| `a_num_nonspam` | # Nonspam |
| `a_num_spam` | # Spam |
| `a_updated` | Updated |
| `a_worker_id` | Worker |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `o_name` | Organization |

[\< Record Types](/docs/records/types/)

