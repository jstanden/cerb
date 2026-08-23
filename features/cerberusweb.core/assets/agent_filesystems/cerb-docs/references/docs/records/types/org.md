---
id: "docs-records-types-org"
title: "Organization Records"
url: "https://cerb.ai/docs/records/types/org/"
summary: "This page provides detailed information about organization records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as city, country, email, and website, and specifies which fields are required. The page also describes dictionary placeholders for automations, snippets, and API responses, offering a comprehensive list of fields like name, phone, and address. Additionally, it details search query fields that can be used to filter organization records, such as city, country, and email, and lists the columns available in organization worklists, which include city, country, and created date. The page serves as a guide for managing and utilizing organization records effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Organization |
| **Name (plural):** | Organizations |
| **Alias (uri):** | org |
| **Identifier (ID):** | cerberusweb.contexts.org |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `aliases` | [text](/docs/records/fields/types/text/) | Alias names as a CRLF-delimited list |
| &nbsp; | `city` | [text](/docs/records/fields/types/text/) | City |
| &nbsp; | `country` | [text](/docs/records/fields/types/text/) | Country |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `email_id` | [number](/docs/records/fields/types/number/) | Primary [email address](/docs/records/types/address/) |
| &nbsp; | `image` | [image](/docs/records/fields/types/image/) | The profile image, base64-encoded in data URI format |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this organization |
| &nbsp; | `phone` | [text](/docs/records/fields/types/text/) | Phone |
| &nbsp; | `postal` | [text](/docs/records/fields/types/text/) | Postal code / ZIP |
| &nbsp; | `province` | [text](/docs/records/fields/types/text/) | State / Province |
| &nbsp; | `street` | [text](/docs/records/fields/types/text/) | Street address |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `website` | [url](/docs/records/fields/types/url/) | Website |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `aliases` | array | Alias names (e.g. `["Acme Corp", "Acme Inc"]`); use `{{record.aliases|join(', ')}}` in scripting |
| `city` | text | City |
| `country` | text | Country |
| `created` | date | Created |
| `email_` | record | [Email](/docs/records/types/address/) |
| `id` | number | Id |
| `name` | text | Name |
| `phone` | text | Phone |
| `postal` | text | Postal |
| `province` | text | State/Prov |
| `record_url` | text | Record Url |
| `street` | text | Street |
| `updated` | date | Updated |
| `website` | text | Website |

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

These [filters](/docs/search/#filters) are available in organization [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `alias:` | [text](/docs/search/#text) | Aliases (e.g. `alias:Acme` or `alias:["Acme Corp","Acme Inc"]`) |
| `city:` | [text](/docs/search/#text) | City |
| `comments:` | [fulltext](/docs/search/#fulltext) | Comment Content |
| `contacts:` | [record](/docs/search/#deep-search) | [Contacts](/docs/records/types/contact/) |
| `country:` | [text](/docs/search/#text) | Country |
| `created:` | [date](/docs/search/#dates) | Created |
| `email:` | [record](/docs/search/#deep-search) | [Email](/docs/records/types/address/) |
| `email.id:` | [chooser](/docs/search/#choosers) | [Email](/docs/records/types/address/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `phone:` | [text](/docs/search/#text) | Phone |
| `postal:` | [text](/docs/search/#text) | Postal |
| `state:` | [text](/docs/search/#text) | State/Prov |
| `street:` | [text](/docs/search/#text) | Street |
| `ticket:` | [record](/docs/search/#deep-search) | [Ticket](/docs/records/types/ticket/) |
| `ticket.id:` | [chooser](/docs/search/#choosers) | [Ticket](/docs/records/types/ticket/) |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |
| `website:` | [text](/docs/search/#text) | Website |

### Worklist Columns

These columns are available on organization [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_city` | City |
| `c_country` | Country |
| `c_created` | Created |
| `c_email_id` | Email |
| `c_id` | Id |
| `c_name` | Name |
| `c_phone` | Phone |
| `c_postal` | Postal |
| `c_province` | State/Prov |
| `c_street` | Street |
| `c_updated` | Updated |
| `c_website` | Website |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

