---
id: "docs-records-types-worker"
title: "Worker Records"
url: "https://cerb.ai/docs/records/types/worker/"
summary: "This page provides detailed information about worker records in Cerb, including the fields available in the Records API, dictionary placeholders for automations and API responses, search query fields, and worklist columns. It outlines the various attributes associated with worker records, such as personal information (e.g., name, email, date of birth), account settings (e.g., language, timezone, MFA requirements), and administrative roles. The page also describes how these fields can be used in search queries and displayed in worklists, offering a comprehensive guide for managing and utilizing worker data within the Cerb platform."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Worker |
| **Name (plural):** | Workers |
| **Alias (uri):** | worker |
| **Identifier (ID):** | cerberusweb.contexts.worker |

- [AI workers](#ai-workers)
- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### AI workers

An [AI agent](/docs/agents/) is an ordinary worker record with `is_ai` set, rather than a separate record type. That means an agent can own a [ticket](/docs/records/types/ticket/), be `@mentioned`, belong to a [group](/docs/groups/), and hold [API](/docs/api/) credentials, because those already belong to workers.

An AI worker can never sign in – interactive logins and SSO are refused – and doesn't require an email address.

To list only AI workers, search workers with `isAi:y`. A [search facet](/docs/search/#search-facets) can give that query its own entry in the **Search** menu.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `at_mention_name` | [text](/docs/records/fields/types/text/) | The nickname used for `@mention` notifications in comments |
| &nbsp; | `calendar_id` | [number](/docs/records/fields/types/number/) | The ID of the [calendar](/docs/records/types/calendar/) used to compute worker availability |
| &nbsp; | `dob` | [text](/docs/records/fields/types/text/) | Date of birth in `YYYY-MM-DD` format |
| &nbsp; | `email` | [text](/docs/records/fields/types/text/) | The primary email address of the worker; alternative to `email_id` |
| **x** | **`email_id`** | [number](/docs/records/fields/types/number/) | The ID of the primary [email address](/docs/records/types/address/); alternative to `email` |
| &nbsp; | `email_ids` | [text](/docs/records/fields/types/text/) | A comma-separated list of IDs for alternative [email addresses](/docs/records/types/address/) |
| **x** | **`first_name`** | [text](/docs/records/fields/types/text/) | Given name |
| &nbsp; | `gender` | [text](/docs/records/fields/types/text/) | `F` (female), `M` (male), or blank or unknown |
| &nbsp; | `image` | [image](/docs/records/fields/types/image/) | The profile image, base64-encoded in data URI format |
| &nbsp; | `is_ai` | [boolean](/docs/records/fields/types/boolean/) | Is this an [AI worker](/docs/agents/)? An automated identity that can never log in. |
| &nbsp; | `is_disabled` | [boolean](/docs/records/fields/types/boolean/) | Is this worker deactivated and prevented from logging in? |
| &nbsp; | `is_mfa_required` | [boolean](/docs/records/fields/types/boolean/) | Is this worker required to use multi-factor authentication? |
| &nbsp; | `is_password_disabled` | [boolean](/docs/records/fields/types/boolean/) | Is this worker allowed to log in with a password? |
| &nbsp; | `is_superuser` | [boolean](/docs/records/fields/types/boolean/) | Is this worker an administrator with full privileges? |
| **x** | **`language`** | [text](/docs/records/fields/types/text/) | ISO-639 language code and ISO-3166 country code; e.g. `en_US` |
| &nbsp; | `last_name` | [text](/docs/records/fields/types/text/) | Surname |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `location` | [text](/docs/records/fields/types/text/) | Location description; `Los Angeles, CA, USA` |
| &nbsp; | `mobile` | [text](/docs/records/fields/types/text/) | Mobile number |
| &nbsp; | `password` | [text](/docs/records/fields/types/text/) | The worker's password, if applicable; stored security; will be automatically generated if blank |
| &nbsp; | `phone` | [text](/docs/records/fields/types/text/) | &nbsp; |
| &nbsp; | `time_format` | [text](/docs/records/fields/types/text/) | Preference for displaying timestamps, `DateTime()` syntax |
| &nbsp; | `timeout_idle_secs` | [number](/docs/records/fields/types/number/) | Consider a session idle after this many seconds of inactivity |
| **x** | **`timezone`** | [text](/docs/records/fields/types/text/) | IANA tz/zoneinfo timezone; `America/Los_Angeles` |
| &nbsp; | `title` | [text](/docs/records/fields/types/text/) | Job title / Position |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `address_` | record | [Email](/docs/records/types/address/) |
| `at_mention_name` | text | @Mention |
| `calendar_` | record | [Calendar](/docs/records/types/calendar/) |
| `calendar_owner_` | record | Calendar Owner |
| `dob` | text | Date Of Birth |
| `first_name` | text | First Name |
| `full_name` | text | Full Name |
| `gender` | text | Gender |
| `id` | worker | Id |
| `is_ai` | boolean | AI |
| `is_disabled` | boolean | Disabled |
| `is_superuser` | boolean | Administrator |
| `language` | text | Language |
| `last_name` | text | Last Name |
| `location` | text | Location |
| `mobile` | text | Mobile |
| `phone` | text | Phone |
| `record_url` | text | Record Url |
| `time_format` | text | Time Format |
| `timeout_idle_secs` | number | Idle Timeout (Secs) |
| `timezone` | text | Timezone |
| `title` | text | Title |
| `updated` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `emails` | records | Emails |
| `groups` | records | Groups |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `roles` | records | Roles |

### Search Query Fields

These [filters](/docs/search/#filters) are available in worker [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `alias:` | virtual | Aliases |
| `calendar:` | [record](/docs/search/#deep-search) | [Calendar](/docs/records/types/calendar/) |
| `calendar.id:` | [chooser](/docs/search/#choosers) | [Calendar](/docs/records/types/calendar/) |
| `email:` | [record](/docs/search/#deep-search) | [Email](/docs/records/types/address/) |
| `email.id:` | [chooser](/docs/search/#choosers) | [Email](/docs/records/types/address/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `firstName:` | [text](/docs/search/#text) | First Name |
| `gender:` | [text](/docs/search/#text) | Gender |
| `group:` | [record](/docs/search/#deep-search) | [Groups](/docs/records/types/group/) |
| `group.manager:` | [record](/docs/search/#deep-search) | [Group Manager](/docs/records/types/group/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `isAdmin:` | [boolean](/docs/search/#booleans) | Administrator |
| `isAi:` | [boolean](/docs/search/#booleans) | AI |
| `isAvailable:` | virtual | Calendar Availability |
| `isBusy:` | virtual | Calendar Availability |
| `isDisabled:` | [boolean](/docs/search/#booleans) | Disabled |
| `isMfaRequired:` | [boolean](/docs/search/#booleans) | Mfa Required |
| `isPasswordDisabled:` | [boolean](/docs/search/#booleans) | Password Disabled |
| `language:` | [text](/docs/search/#text) | Language |
| `lastActivity:` | [date](/docs/search/#dates) | Last Activity |
| `lastName:` | [text](/docs/search/#text) | Last Name |
| `links:` | [links](/docs/search/#links) | Record Links |
| `location:` | [text](/docs/search/#text) | Location |
| `mention:` | [text](/docs/search/#text) | @Mention |
| `mobile:` | [text](/docs/search/#text) | Mobile |
| `phone:` | [text](/docs/search/#text) | Phone |
| `role:` | [record](/docs/search/#deep-search) | [Role](/docs/records/types/role/) |
| `role.editor:` | [record](/docs/search/#deep-search) | [Role Editor](/docs/records/types/role/) |
| `role.reader:` | [record](/docs/search/#deep-search) | [Role Reader](/docs/records/types/role/) |
| `timezone:` | [text](/docs/search/#text) | Timezone |
| `title:` | [text](/docs/search/#text) | Title |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `using.workspace:` | [record](/docs/search/#deep-search) | [Using Workspace](/docs/records/types/workspace_page/) |

### Worklist Columns

These columns are available on worker [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_calendar_availability` | Calendar Availability |
| `a_address_email` | Email Address |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `w_at_mention_name` | @Mention |
| `w_calendar_id` | Calendar |
| `w_dob` | D.o.b. |
| `w_first_name` | First Name |
| `w_gender` | Gender |
| `w_id` | Id |
| `w_is_ai` | AI |
| `w_is_disabled` | Disabled |
| `w_is_mfa_required` | Mfa Required |
| `w_is_password_disabled` | Password Disabled |
| `w_is_superuser` | Administrator |
| `w_language` | Language |
| `w_last_name` | Last Name |
| `w_location` | Location |
| `w_mobile` | Mobile |
| `w_phone` | Phone |
| `w_time_format` | Time Format |
| `w_timeout_idle_secs` | Idle Timeout (Secs) |
| `w_timezone` | Timezone |
| `w_title` | Title |
| `w_updated` | Updated |

[\< Record Types](/docs/records/types/)

