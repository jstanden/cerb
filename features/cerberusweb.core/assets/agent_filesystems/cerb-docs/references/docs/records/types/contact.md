---
id: "docs-records-types-contact"
title: "Contact Records"
url: "https://cerb.ai/docs/records/types/contact/"
summary: "This page provides comprehensive information about contact records in Cerb, detailing the fields available in the Records API, dictionary placeholders for automations and API responses, search query fields, and worklist columns. It outlines the structure and types of data that can be associated with a contact, such as personal information (name, email, phone, etc.), organizational details, and metadata like comments and custom fields. The page also explains how these fields can be utilized in search queries and displayed in worklists, offering a robust framework for managing and interacting with contact data within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Contact |
| **Name (plural):** | Contacts |
| **Alias (uri):** | contact |
| **Identifier (ID):** | cerberusweb.contexts.contact |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `dob` | [text](/docs/records/fields/types/text/) | Date of birth: `YYYY-MM-DD` |
| &nbsp; | `email` | [text](/docs/records/fields/types/text/) | Email address (e.g. `customer@example.com`); alternative to `email_id` |
| &nbsp; | `email_id` | [number](/docs/records/fields/types/number/) | ID of this contact's primary [email address](/docs/records/types/address/) |
| **x** | **`first_name`** | [text](/docs/records/fields/types/text/) | Given name |
| &nbsp; | `gender` | [text](/docs/records/fields/types/text/) | Gender: `F` (female), `M` (male), or blank |
| &nbsp; | `image` | [image](/docs/records/fields/types/image/) | The profile image, base64-encoded in data URI format |
| &nbsp; | `language` | [text](/docs/records/fields/types/text/) | Language: `en_US` |
| &nbsp; | `last_login_at` | [timestamp](/docs/records/fields/types/timestamp/) | Date of their last [community portal](/docs/portals/) login |
| &nbsp; | `last_name` | [text](/docs/records/fields/types/text/) | Surname |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `location` | [text](/docs/records/fields/types/text/) | Location (e.g. `Los Angeles, California, USA`) |
| &nbsp; | `mobile` | [text](/docs/records/fields/types/text/) | Mobile number |
| &nbsp; | `org` | [text](/docs/records/fields/types/text/) | Organization (e.g. `Fiaflux Software`); alternative to `org_id` |
| &nbsp; | `org_id` | [number](/docs/records/fields/types/number/) | ID of this contact's [organization](/docs/records/types/org/) |
| &nbsp; | `phone` | [text](/docs/records/fields/types/text/) | Phone number |
| &nbsp; | `timezone` | [text](/docs/records/fields/types/text/) | Timezone (e.g. `America/Los_Angeles`) |
| &nbsp; | `title` | [text](/docs/records/fields/types/text/) | Job title / Position |
| &nbsp; | `username` | [text](/docs/records/fields/types/text/) | Username for public display |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `dob` | text | Date Of Birth |
| `email_` | record | [Email](/docs/records/types/address/) |
| `first_name` | text | First Name |
| `gender` | text | Gender |
| `id` | number | Id |
| `language` | text | Language |
| `last_login_at` | date | Last Login |
| `last_name` | text | Last Name |
| `location` | text | Location |
| `mobile` | text | Mobile |
| `name` | text | Name |
| `org_` | record | [Org](/docs/records/types/org/) |
| `phone` | text | Phone |
| `record_url` | text | Record Url |
| `timezone` | text | Timezone |
| `title` | text | Title |
| `updated_at` | date | Updated |
| `username` | text | Username |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `emails` | records | Email Addresses |
| `last_recipient_message` | record | Latest [Message](/docs/records/types/message/) Received To |
| `last_sender_message` | record | Latest [Message](/docs/records/types/message/) Sent From |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in contact [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `alias:` | virtual | Aliases |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `created:` | [date](/docs/search/#dates) | Created |
| `email:` | [record](/docs/search/#deep-search) | [Email](/docs/records/types/address/) |
| `email.id:` | [chooser](/docs/search/#choosers) | [Email](/docs/records/types/address/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `firstName:` | [text](/docs/search/#text) | First Name |
| `gender:` | [text](/docs/search/#text) | Gender |
| `id:` | [number](/docs/search/#numbers) | Id |
| `lang:` | [text](/docs/search/#text) | Language |
| `lastLogin:` | [date](/docs/search/#dates) | Last Login |
| `lastName:` | [text](/docs/search/#text) | Last Name |
| `links:` | [links](/docs/search/#links) | Record Links |
| `mobile:` | [text](/docs/search/#text) | Mobile |
| `org:` | [record](/docs/search/#deep-search) | [Org](/docs/records/types/org/) |
| `org.id:` | [chooser](/docs/search/#choosers) | [Organization](/docs/records/types/org/) |
| `phone:` | [text](/docs/search/#text) | Phone |
| `timezone:` | [text](/docs/search/#text) | Timezone |
| `title:` | [text](/docs/search/#text) | Title |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `username:` | [text](/docs/search/#text) | Username |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on contact [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_created_at` | Created |
| `c_dob` | D.o.b. |
| `c_first_name` | First Name |
| `c_gender` | Gender |
| `c_id` | Id |
| `c_language` | Language |
| `c_last_login_at` | Last Login |
| `c_last_name` | Last Name |
| `c_location` | Location |
| `c_mobile` | Mobile |
| `c_org_id` | Organization |
| `c_phone` | Phone |
| `c_primary_email_id` | Email |
| `c_timezone` | Timezone |
| `c_title` | Title |
| `c_updated_at` | Updated |
| `c_username` | Username |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

