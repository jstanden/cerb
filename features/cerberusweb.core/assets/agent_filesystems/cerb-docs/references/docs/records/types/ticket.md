---
id: "docs-records-types-ticket"
title: "Ticket Records"
url: "https://cerb.ai/docs/records/types/ticket/"
summary: "This page provides comprehensive information about ticket records in Cerb, detailing the fields and functionalities available through the Records API, dictionary placeholders, search query fields, and worklist columns. It outlines the structure and attributes of ticket records, including identifiers, timestamps, group and bucket associations, importance levels, and status indicators. The page also explains how to utilize these fields in automations, snippets, and API responses, and offers guidance on constructing search queries and organizing worklists to efficiently manage and analyze ticket data."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Ticket |
| **Name (plural):** | Tickets |
| **Alias (uri):** | ticket |
| **Identifier (ID):** | cerberusweb.contexts.ticket |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `bucket` | [text](/docs/records/fields/types/text/) | The [bucket](/docs/records/types/bucket/) name of the ticket; alternative to `bucket_id`. If used, a `group` or `group_id` must also be provided at the same time. |
| &nbsp; | `bucket_id` | [number](/docs/records/fields/types/number/) | The ID of the [bucket](/docs/records/types/bucket/) containing this ticket |
| &nbsp; | `closed` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time this ticket was first set to status `closed` |
| &nbsp; | `created` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `fieldsets` | fieldsets | An array or comma-separated list of [custom fieldset](/docs/records/types/custom_fieldset/) IDs. Prefix an ID with `-` to remove. |
| &nbsp; | `group` | [text](/docs/records/fields/types/text/) | The [group](/docs/records/types/group/) of the ticket; alternative to `group_id` |
| **x** | **`group_id`** | [number](/docs/records/fields/types/number/) | The ID of the [group](/docs/records/types/group/) containing this ticket |
| &nbsp; | `importance` | [number](/docs/records/fields/types/number/) | A number from `0` (least) to `100` (most) |
| &nbsp; | `last_opened_at` | [timestamp](/docs/records/fields/types/timestamp/) | &nbsp; |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `mask` | [text](/docs/records/fields/types/text/) | The randomized reference number for this ticket; auto-generated if blank |
| &nbsp; | `org` | [text](/docs/records/fields/types/text/) | The exact name of the [organization](/docs/records/types/org/) linked to this ticket; alternative to `org_id` |
| &nbsp; | `org_id` | [number](/docs/records/fields/types/number/) | The ID of the [organization](/docs/records/types/org/) linked to this ticket; alternative to `org` |
| &nbsp; | `owner_id` | [number](/docs/records/fields/types/number/) | The ID of the [worker](/docs/records/types/worker/) responsible for this ticket |
| &nbsp; | `participant_ids` | [text](/docs/records/fields/types/text/) | A comma-separated list of email addresses IDs to add or remove as participants. Prefix an ID with `-` to remove |
| &nbsp; | `participants` | [text](/docs/records/fields/types/text/) | A comma-separated list of email addresses to add as participants |
| &nbsp; | `reopen_date` | [timestamp](/docs/records/fields/types/timestamp/) | If status `waiting`, the date/time to automatically change the status back to `open` |
| &nbsp; | `spam_score` | [float](/docs/records/fields/types/float/) | `0.0001` (not spam) to `0.9999` (spam); automatically generated |
| &nbsp; | `spam_training` | [text](/docs/records/fields/types/text/) | `S` (spam), `N` (not spam); blank for non-trained |
| &nbsp; | `status` | [text](/docs/records/fields/types/text/) | `o` (open), `w` (waiting), `c` (closed), `d` (deleted); alternative to `status_id` |
| &nbsp; | `status_id` | [number](/docs/records/fields/types/number/) | `0` (open), `1` (waiting), `2` (closed), `3` (deleted); alternative to `status` |
| **x** | **`subject`** | [text](/docs/records/fields/types/text/) | The subject of the ticket |
| &nbsp; | `updated` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `bucket_` | record | [Bucket](/docs/records/types/bucket/) |
| `closed` | date | Closed |
| `created` | date | Created |
| `elapsed_resolution_first` | seconds | First Resolution |
| `elapsed_response_first` | seconds | First Response |
| `elapsed_status_open` | seconds | Time Spent Open |
| `group_` | record | [Group](/docs/records/types/group/) |
| `id` | number | Id |
| `importance` | number | Importance |
| `initial_message_` | record | [Initial Message](/docs/records/types/message/) |
| `initial_response_message_` | record | [Initial Response Message](/docs/records/types/message/) |
| `latest_message_` | record | [Latest Message](/docs/records/types/message/) |
| `mask` | text | Mask |
| `num_messages` | number | # Messages |
| `num_messages_in` | number | # Messages In |
| `num_messages_out` | number | # Messages Out |
| `org_` | record | [Org](/docs/records/types/org/) |
| `owner_` | record | [Owner](/docs/records/types/worker/) |
| `reopen_date` | date | Reopen At |
| `spam_score` | percent | Spam Score |
| `spam_training` | text | Spam Training |
| `status` | text | Status |
| `subject` | text | Subject |
| `updated` | date | Updated |
| `url` | text | Url |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `_messages` | records | Messages |
| `attachments` | attachments | [Attachments](/docs/guide/developers/dictionaries/#key-expansion) |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `latest_incoming_activity` | date | Latest Incoming Activity |
| `latest_outgoing_activity` | date | Latest Outgoing Activity |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |
| `participants` | records | Participants |
| `requester_emails` | text | Requester Emails (Comma-Separated) |
| `requesters` | hashmap | Requesters |
| `signature` | text | Signature |
| `watchers` | watchers | [Watchers](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in ticket [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `bucket:` | [record](/docs/search/#deep-search) | [Bucket](/docs/records/types/bucket/) |
| `bucket.id:` | [chooser](/docs/search/#choosers) | [Bucket](/docs/records/types/bucket/) |
| `closed:` | [date](/docs/search/#dates) | Closed |
| `comments:` | [record](/docs/search/#deep-search) | [Comments](/docs/records/types/comment/) |
| `comments.first:` | [record](/docs/search/#deep-search) | [Comments First](/docs/records/types/comment/) |
| `comments.last:` | [record](/docs/search/#deep-search) | [Comments Last](/docs/records/types/comment/) |
| `created:` | [date](/docs/search/#dates) | Created |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `group:` | [record](/docs/search/#deep-search) | [Group](/docs/records/types/group/) |
| `group.id:` | [chooser](/docs/search/#choosers) | [Group](/docs/records/types/group/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `importance:` | [number](/docs/search/#numbers) | Importance |
| `inGroupsOf:` | virtual | In Groups Of Worker |
| `lastOpenedAt:` | [date](/docs/search/#dates) | Last Opened At |
| `links:` | [links](/docs/search/#links) | Record Links |
| `mask:` | [text](/docs/search/#text) | Mask |
| `mask.merged:` | [text](/docs/search/#text) | Masks Previously Merged Into Ticket |
| `messages:` | [record](/docs/search/#deep-search) | [Messages](/docs/records/types/message/) |
| `messages.count:` | [number](/docs/search/#numbers) | # Messages |
| `messages.count.in:` | [number](/docs/search/#numbers) | # Messages In |
| `messages.count.out:` | [number](/docs/search/#numbers) | # Messages Out |
| `messages.first:` | [record](/docs/search/#deep-search) | [Messages First](/docs/records/types/message/) |
| `messages.firstOutgoing:` | [record](/docs/search/#deep-search) | [Messages First Outgoing](/docs/records/types/message/) |
| `messages.last:` | [record](/docs/search/#deep-search) | [Messages Last](/docs/records/types/message/) |
| `org:` | [record](/docs/search/#deep-search) | [Org](/docs/records/types/org/) |
| `org.id:` | [chooser](/docs/search/#choosers) | [Organization](/docs/records/types/org/) |
| `owner:` | [record](/docs/search/#deep-search) | [Owner](/docs/records/types/worker/) |
| `owner.id:` | [chooser](/docs/search/#choosers) | [Owner](/docs/records/types/worker/) |
| `participant:` | [record](/docs/search/#deep-search) | [Participant](/docs/records/types/address/) |
| `participant.id:` | [chooser](/docs/search/#choosers) | [Participant Id](/docs/records/types/address/) |
| `reopen:` | [date](/docs/search/#dates) | Reopen At |
| `resolution.first:` | [number](/docs/search/#numbers) | First Resolution |
| `response.first:` | [number](/docs/search/#numbers) | First Response |
| `responsibility:` | [number](/docs/search/#numbers) | Responsibility |
| `sender.first:` | [record](/docs/search/#deep-search) | [Sender First](/docs/records/types/address/) |
| `sender.last:` | [record](/docs/search/#deep-search) | [Sender Last](/docs/records/types/address/) |
| `spam.score:` | [number](/docs/search/#numbers) | Spam Score |
| `spam.training:` | virtual | Spam Training |
| `status:` | virtual | Status |
| `status.id:` | [number](/docs/search/#numbers) | Status |
| `subject:` | [text](/docs/search/#text) | Subject |
| `timeSpentOpen:` | [number](/docs/search/#numbers) | Time Spent Open |
| `updated:` | [date](/docs/search/#dates) | Updated |
| `watchers:` | [record](/docs/search/#deep-search) | [Watchers](/docs/records/types/worker/) |
| `watchers.count:` | virtual | Watchers Count |
| `worker.commented:` | virtual | Worker Commented |
| `worker.replied:` | virtual | Worker Replied |

### Worklist Columns

These columns are available on ticket [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `*_participant_search` | Participants |
| `*_status` | Status |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |
| `t_bucket_id` | Bucket |
| `t_closed_at` | Closed |
| `t_created_date` | Created |
| `t_elapsed_resolution_first` | First Resolution |
| `t_elapsed_response_first` | First Response |
| `t_elapsed_status_open` | Time Spent Open |
| `t_first_wrote_address_id` | First Wrote |
| `t_group_id` | Group |
| `t_id` | Id |
| `t_importance` | Importance |
| `t_last_opened_at` | Last Opened At |
| `t_last_wrote_address_id` | Last Wrote |
| `t_mask` | Mask |
| `t_num_messages` | # Messages |
| `t_num_messages_in` | # Messages In |
| `t_num_messages_out` | # Messages Out |
| `t_org_id` | Organization |
| `t_owner_id` | Owner |
| `t_reopen_at` | Reopen At |
| `t_spam_score` | Spam Score |
| `t_spam_training` | Spam Training |
| `t_subject` | Subject |
| `t_updated_date` | Updated |
| `wtb_responsibility` | Responsibility |

[\< Record Types](/docs/records/types/)

