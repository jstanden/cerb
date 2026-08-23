---
id: "docs-records-types-servicetoken"
title: "Service Token Records"
url: "https://cerb.ai/docs/records/types/service_token/"
summary: "Service tokens authenticate anonymous, privileged access to endpoints like /cron, /debug, and /update without requiring a worker session. Tokens can be scoped to specific endpoints, are passed via Authorization Bearer header or the _authorization POST parameter, and replace the AUTHORIZED_IPS_DEFAULTS allowlist. This page documents the Records API fields, dictionary placeholders, search filters, and worklist columns available on service token records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Service Token |
| **Name (plural):** | Service Tokens |
| **Alias (uri):** | service\_token |
| **Identifier (ID):** | cerb.contexts.service.token |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Service Token [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting authentications, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

A service token authenticates anonymous, privileged access to endpoints like `/cron`, `/debug`, and `/update`. Service tokens replace the [`AUTHORIZED_IPS_DEFAULTS`](/docs/config-file/#common-settings) IP allowlist – and the now-removed `DEVELOPMENT_MODE_ALLOW_DEBUG` flag – with a more flexible, auditable mechanism that works regardless of where requests originate.

Tokens are passed either in an HTTP `Authorization: Bearer <token>` header or as an `_authorization` POST parameter – for instance, from a cronjob, monitoring tool, or deploy script. Each token can be restricted to specific endpoint **scopes** (e.g. `cron:*`, `debug:*`, `update`). When viewing a protected endpoint in the browser, a token can be entered through a login prompt to continue.

A master service token may be configured in `framework.config.php` using [`APP_SERVICE_TOKEN`](/docs/config-file/#optional-settings) – particularly useful for `/update`, since worker logins are blocked until the update finishes. The master token's scope defaults to `*` (all endpoints) but may be restricted with [`APP_SERVICE_TOKEN_SCOPE`](/docs/config-file/#optional-settings).

Service tokens are managed from [Setup » Configure » Security](/docs/setup/configure/security/).

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `expires_at` | [timestamp](/docs/records/fields/types/timestamp/) | Optional expiration date; `0` for no expiration |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this service token |
| &nbsp; | `last_accessed_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this token was last used |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | A human-readable label for this token |
| &nbsp; | `scopes` | [text](/docs/records/fields/types/text/) | A space-separated list of endpoint scopes (e.g. `cron:* debug:status`) |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `created_at` | date | Created |
| `expires_at` | date | Expires |
| `id` | number | Id |
| `last_accessed_at` | date | Last accessed |
| `name` | text | Name |
| `record_url` | text | Record URL |
| `scopes` | text | Scopes |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in service token [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `created:` | [date](/docs/search/#dates) | Created |
| `expires:` | [date](/docs/search/#dates) | Expires |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `lastAccessed:` | [date](/docs/search/#dates) | Last accessed |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `scopes:` | [text](/docs/search/#text) | Scopes |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on service token [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `s_created_at` | Created |
| `s_expires_at` | Expires |
| `s_id` | Id |
| `s_last_accessed_at` | Last accessed |
| `s_name` | Name |
| `s_scopes` | Scopes |
| `s_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

