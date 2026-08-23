---
id: "docs-records-types-oauthapp"
title: "OAuth App Records"
url: "https://cerb.ai/docs/records/types/oauth_app/"
summary: "This page provides detailed information about OAuth App records in Cerb, including their structure and usage within the platform. It outlines the fields available in the Records API, such as access token expiration, callback URL, client ID, and client secret, which are essential for managing OAuth applications. The page also describes dictionary placeholders for automations and API responses, search query fields for filtering OAuth app records, and worklist columns for organizing and displaying these records. This comprehensive guide is designed to help users effectively manage and integrate OAuth applications within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Oauth App |
| **Name (plural):** | Oauth Apps |
| **Alias (uri):** | oauth\_app |
| **Identifier (ID):** | cerberusweb.contexts.oauth.app |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `access_token_ttl` | [text](/docs/records/fields/types/text/) | The expiration of the access token (e.g. '1 hour') |
| **x** | **`callback_url`** | [url](/docs/records/fields/types/url/) | The OAuth2 callback URL of the app |
| **x** | **`client_id`** | [text](/docs/records/fields/types/text/) | The client identifier of the app |
| **x** | **`client_secret`** | [text](/docs/records/fields/types/text/) | The client secret of the app |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this oauth app |
| &nbsp; | `refresh_token_ttl` | [text](/docs/records/fields/types/text/) | The expiration of the refresh token (e.g. '1 month') |
| &nbsp; | `scopes` | [text](/docs/records/fields/types/text/) | The app's available scopes in YAML format |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| &nbsp; | `url` | [url](/docs/records/fields/types/url/) | The app's URL |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `access_token_ttl` | text | Access Token Expires |
| `callback_url` | text | Callback Url |
| `client_id` | text | Client Id |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `refresh_token_ttl` | text | Refresh Token Expires |
| `scopes` | text | Scopes |
| `updated_at` | date | Updated |
| `url` | text | Url |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in oauth app [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `accessTokenExpires:` | [text](/docs/search/#text) | Access Token Expires |
| `callbackUrl:` | [text](/docs/search/#text) | Callback Url |
| `clientId:` | [text](/docs/search/#text) | Client Id |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `refreshTokenExpires:` | [text](/docs/search/#text) | Refresh Token Expires |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `url:` | [text](/docs/search/#text) | Url |

### Worklist Columns

These columns are available on oauth app [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `o_access_token_ttl` | Access Token Expires |
| `o_callback_url` | Callback Url |
| `o_client_id` | Client Id |
| `o_id` | Id |
| `o_name` | Name |
| `o_refresh_token_ttl` | Refresh Token Expires |
| `o_scopes` | Scopes |
| `o_updated_at` | Updated |
| `o_url` | Url |

[\< Record Types](/docs/records/types/)

