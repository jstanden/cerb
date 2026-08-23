---
id: "docs-records-types-connectedaccount"
title: "Connected Account Records"
url: "https://cerb.ai/docs/records/types/connected_account/"
summary: "This page provides detailed information about Connected Account Records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, which are essential for linking, identifying, and managing connected accounts. The page also describes dictionary placeholders that can be used in automations, snippets, and API responses, offering a range of fields such as record type, owner, and service provider. Additionally, it lists search query fields that facilitate filtering connected accounts based on various criteria like creation date, owner, and service. Lastly, it details the worklist columns available for organizing and displaying connected account data, highlighting key attributes such as owner, creation date, and service provider."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Connected Account |
| **Name (plural):** | Connected Accounts |
| **Alias (uri):** | connected\_account |
| **Identifier (ID):** | cerberusweb.contexts.connected\_account |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this connected account |
| **x** | **`owner__context`** | [context](/docs/records/fields/types/context/) | The [record type](/docs/records/types/) of this connected account's owner: `app`, `role`, `group`, or `worker` |
| **x** | **`owner_id`** | [number](/docs/records/fields/types/number/) | The ID of this connected account's owner |
| &nbsp; | `params` | [object](/docs/records/fields/types/object/) | JSON-encoded key/value object |
| &nbsp; | `service_id` | [number](/docs/records/fields/types/number/) | [Service Provider](/docs/plugins/extensions/points/cerb.connected_service.provider/) |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `uri` | [text](/docs/records/fields/types/text/) | &nbsp; |

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
| `service` | text | Service Provider |
| `service_` | record | [Service](/docs/records/types/connected_service/) |
| `updated_at` | date | Updated |
| `uri` | text | Uri |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in connected account [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
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
| `service:` | [record](/docs/search/#deep-search) | [Service](/docs/records/types/connected_service/) |
| `service.id:` | [chooser](/docs/search/#choosers) | [Service Provider](/docs/records/types/connected_service/) |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `uri:` | [text](/docs/search/#text) | Uri |

### Worklist Columns

These columns are available on connected account [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_owner` | Owner |
| `c_created_at` | Created |
| `c_id` | Id |
| `c_name` | Name |
| `c_service_id` | Service Provider |
| `c_updated_at` | Updated |
| `c_uri` | Uri |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

