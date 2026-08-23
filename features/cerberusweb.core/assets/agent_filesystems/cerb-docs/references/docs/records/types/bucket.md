---
id: "docs-records-types-bucket"
title: "Bucket Records"
url: "https://cerb.ai/docs/records/types/bucket/"
summary: "This page provides detailed information about Bucket records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing bucket records, such as group ID, name, and reply settings. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields like record type, name, and updated timestamp. Additionally, it covers search query fields that allow users to filter bucket records based on various criteria, such as group, name, and email template. Lastly, it lists the worklist columns available for displaying bucket records, including group ID, name, and custom fields, providing a comprehensive guide for managing and utilizing bucket records in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Bucket |
| **Name (plural):** | Buckets |
| **Alias (uri):** | bucket |
| **Identifier (ID):** | cerberusweb.contexts.bucket |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`group_id`** | [number](/docs/records/fields/types/number/) | The ID of the parent [group](/docs/records/types/group/) containing this bucket |
| &nbsp; | `is_default` | [boolean](/docs/records/fields/types/boolean/) | Is this the default (inbox) bucket of the group? |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this bucket |
| &nbsp; | `reply_address_id` | [number](/docs/records/fields/types/number/) | The ID of the [email address](/docs/records/types/address/) used when sending replies from this bucket |
| &nbsp; | `reply_html_template_id` | [number](/docs/records/fields/types/number/) | The ID of the default [mail template](/docs/records/types/html_template/) used when sending HTML mail from this bucket |
| &nbsp; | `reply_personal` | [text](/docs/records/fields/types/text/) | The default personal name in the `From:` of replies |
| &nbsp; | `reply_signature_id` | [number](/docs/records/fields/types/number/) | The ID of the default [signature](/docs/records/types/email_signature/) used when sending replies from this bucket |
| &nbsp; | `reply_signing_key_id` | [number](/docs/records/fields/types/number/) | The [private key](/docs/records/types/gpg_private_key/) used when signing outgoing mail from this bucket |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `group_` | record | [Group](/docs/records/types/group/) |
| `id` | number | Id |
| `is_default` | boolean | Default |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `reply_html_template_` | record | [Email Template](/docs/records/types/html_template/) |
| `reply_personal` | text | Send As |
| `reply_signature_` | record | [Signature](/docs/records/types/email_signature/) |
| `reply_signature_owner_` | record | Signature Owner |
| `reply_signing_key_` | record | [Signing Key](/docs/records/types/gpg_private_key/) |
| `replyto_` | record | [Send From](/docs/records/types/address/) |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in bucket [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `group:` | [record](/docs/search/#deep-search) | [Group](/docs/records/types/group/) |
| `group.id:` | [chooser](/docs/search/#choosers) | [Group](/docs/records/types/group/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `send.as:` | [text](/docs/search/#text) | Send As |
| `send.from.id:` | [chooser](/docs/search/#choosers) | [Send From](/docs/records/types/address/) |
| `signature.id:` | [chooser](/docs/search/#choosers) | [Signature](/docs/records/types/email_signature/) |
| `signing.key.id:` | [chooser](/docs/search/#choosers) | [Signing Key](/docs/records/types/gpg_private_key/) |
| `template.id:` | [chooser](/docs/search/#choosers) | [Email Template](/docs/records/types/html_template/) |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on bucket [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `b_group_id` | Group |
| `b_id` | Id |
| `b_is_default` | Default |
| `b_name` | Name |
| `b_reply_address_id` | Send From |
| `b_reply_html_template_id` | Email Template |
| `b_reply_personal` | Send As |
| `b_reply_signature_id` | Signature |
| `b_reply_signing_key_id` | Signing Key |
| `b_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

