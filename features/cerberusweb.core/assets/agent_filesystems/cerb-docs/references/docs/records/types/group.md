---
id: "docs-records-types-group"
title: "Group Records"
url: "https://cerb.ai/docs/records/types/group/"
summary: "This page provides detailed information about the 'Group' record type in Cerb, including its API fields, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and attributes of group records, such as creation and update timestamps, privacy settings, member lists, and email reply configurations. The page also describes how these fields can be used in the Records API, automation dictionaries, and search queries, offering a comprehensive guide for managing group records within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Group |
| **Name (plural):** | Groups |
| **Alias (uri):** | group |
| **Identifier (ID):** | cerberusweb.contexts.group |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `image` | [image](/docs/records/fields/types/image/) | The profile image, base64-encoded in data URI format |
| &nbsp; | `is_default` | [boolean](/docs/records/fields/types/boolean/) | [Tickets](/docs/tickets/) are assigned to the default group when no other routing rules match |
| &nbsp; | `is_private` | [boolean](/docs/records/fields/types/boolean/) | The content in public (`0`) groups is visible to everyone; in private (`1`) groups content is only visible to members |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `members` | [text](/docs/records/fields/types/text/) | JSON-encoded array of [worker](/docs/records/types/worker/) IDs; `[1,2,3]` |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this group |
| &nbsp; | `reply_address_id` | [number](/docs/records/fields/types/number/) | The ID of the [email address](/docs/records/types/address/) used when sending replies from this group |
| &nbsp; | `reply_html_template_id` | [number](/docs/records/fields/types/number/) | The ID of the default [mail template](/docs/records/types/html_template/) used when sending HTML mail from this group |
| &nbsp; | `reply_personal` | [text](/docs/records/fields/types/text/) | The default personal name in the `From:` of replies |
| &nbsp; | `reply_signature_id` | [number](/docs/records/fields/types/number/) | The ID of the default [signature](/docs/records/types/email_signature/) used when sending replies from this group |
| &nbsp; | `reply_signing_key_id` | [number](/docs/records/fields/types/number/) | The [private key](/docs/records/types/gpg_private_key/) used to cryptographically sign outgoing mail |
| &nbsp; | `routing_kata` | [text](/docs/records/fields/types/text/) | Routing rules in KATA format |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created` | date | Created |
| `id` | number | Id |
| `is_default` | boolean | Default |
| `is_private` | boolean | Private |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `reply_html_template_` | record | [Email Template](/docs/records/types/html_template/) |
| `reply_personal` | text | Send As |
| `reply_signature_` | record | [Signature](/docs/records/types/email_signature/) |
| `reply_signature_owner_` | record | Signature Owner |
| `reply_signing_key_` | record | [Signing Key](/docs/records/types/gpg_private_key/) |
| `replyto_` | record | [Send From](/docs/records/types/address/) |
| `routing_kata` | text | Dao.bucket.routing\_Kata |
| `updated` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `buckets` | records | Buckets |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `default_bucket_` | record | The group's default [bucket](/docs/records/types/bucket/) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `members` | records | Members |

### Search Query Fields

These [filters](/docs/search/#filters) are available in group [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `default:` | [boolean](/docs/search/#booleans) | Default |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `manager:` | [record](/docs/search/#deep-search) | [Manager](/docs/records/types/worker/) |
| `member:` | [record](/docs/search/#deep-search) | [Member](/docs/records/types/worker/) |
| `name:` | [text](/docs/search/#text) | Name |
| `private:` | [boolean](/docs/search/#booleans) | Private |
| `send.as:` | [text](/docs/search/#text) | Send As |
| `send.from.id:` | [chooser](/docs/search/#choosers) | [Send From](/docs/records/types/address/) |
| `signature.id:` | [chooser](/docs/search/#choosers) | [Signature](/docs/records/types/email_signature/) |
| `signing.key.id:` | [chooser](/docs/search/#choosers) | [Signing Key](/docs/records/types/gpg_private_key/) |
| `template.id:` | [chooser](/docs/search/#choosers) | [Email Template](/docs/records/types/html_template/) |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on group [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `g_created` | Created |
| `g_id` | Id |
| `g_is_default` | Default |
| `g_is_private` | Private |
| `g_name` | Name |
| `g_reply_address_id` | Send From |
| `g_reply_html_template_id` | Email Template |
| `g_reply_personal` | Send As |
| `g_reply_signature_id` | Signature |
| `g_reply_signing_key_id` | Signing Key |
| `g_updated` | Updated |

[\< Record Types](/docs/records/types/)

