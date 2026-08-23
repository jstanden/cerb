---
id: "docs-records-types-mailbox"
title: "Mailbox Account Records"
url: "https://cerb.ai/docs/records/types/mailbox/"
summary: "This page provides detailed information about the structure and functionality of email mailbox account records in Cerb. It outlines the fields available in the Records API, including essential fields like host, name, and username, and describes their types and purposes. The page also explains dictionary placeholders used in automations and API responses, offering a comprehensive list of fields and their descriptions. Additionally, it covers search query fields that can be used to filter mailbox accounts and lists the columns available in mailbox account worklists, which help in organizing and managing email mailbox data effectively."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Email Mailbox |
| **Name (plural):** | Email Mailboxes |
| **Alias (uri):** | mailbox |
| **Identifier (ID):** | cerberusweb.contexts.mailbox |

- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Usage tracking

Mailbox [worklists](/docs/worklists/) offer a 'Usage' [sparklines column](/docs/worklists/#sparkline-columns) charting messages received and errors, with a 2h/1d/30d range toggle. A matching `usage:` [quick search filter](/docs/search/#parameterized-metrics-filters) queries the same data – for instance, `usage:(since:today)`.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `checked_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time this mailbox was last checked for new messages |
| &nbsp; | `connected_account_id` | [number](/docs/records/fields/types/number/) | The optional connected account to use for XOAUTH2 |
| **x** | **`host`** | [text](/docs/records/fields/types/text/) | The mail server hostname |
| &nbsp; | `is_enabled` | [boolean](/docs/records/fields/types/boolean/) | Is this mailbox enabled? `1` for true and `0` for false |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `max_msg_size_kb` | [number](/docs/records/fields/types/number/) | The maximum message size to download (in kilobytes); `0` to disable limits |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this email mailbox |
| &nbsp; | `num_fails` | [number](/docs/records/fields/types/number/) | The number of consecutive failures |
| &nbsp; | `password` | [text](/docs/records/fields/types/text/) | The mailbox password |
| &nbsp; | `port` | [number](/docs/records/fields/types/number/) | The port to connect to; e.g. `587` |
| &nbsp; | `protocol` | [text](/docs/records/fields/types/text/) | The protocol to use: `pop3`, `pop3-ssl`, `imap`, `imap-ssl` |
| &nbsp; | `timeout_secs` | [number](/docs/records/fields/types/number/) | The socket timeout in seconds when downloading mail |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |
| **x** | **`username`** | [text](/docs/records/fields/types/text/) | The mailbox username |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `checked_at` | date | Checked At |
| `connected_account_id` | number | Connected Account |
| `host` | text | Host |
| `id` | number | Id |
| `is_enabled` | boolean | Enabled |
| `max_msg_size_kb` | number | Max Msg Size |
| `name` | text | Name |
| `num_fails` | number | Num Fails |
| `port` | number | Port |
| `protocol` | text | Protocol |
| `record_url` | text | Record Url |
| `timeout_secs` | number | Timeout Secs |
| `updated_at` | date | Updated |
| `username` | text | Username |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in mailbox account [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `checkedAt:` | [date](/docs/search/#dates) | Checked At |
| `enabled:` | [boolean](/docs/search/#booleans) | Enabled |
| `fail.count:` | [number](/docs/search/#numbers) | Num Fails |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `host:` | [text](/docs/search/#text) | Host |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `protocol:` | [text](/docs/search/#text) | Protocol |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |

### Worklist Columns

These columns are available on mailbox account [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `p_checked_at` | Checked At |
| `p_connected_account_id` | Connected Account |
| `p_delay_until` | Delay Until |
| `p_enabled` | Enabled |
| `p_host` | Host |
| `p_id` | Id |
| `p_max_msg_size_kb` | Max Msg Size |
| `p_name` | Name |
| `p_num_fails` | Num Fails |
| `p_port` | Port |
| `p_protocol` | Protocol |
| `p_timeout_secs` | Timeout Secs |
| `p_updated_at` | Updated |
| `p_username` | User |

[\< Record Types](/docs/records/types/)

