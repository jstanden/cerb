---
id: "docs-data-queries-record-fields"
title: "Data Queries: Record Fields"
url: "https://cerb.ai/docs/data-queries/record/fields/"
summary: "This page provides detailed information on `record.fields` data queries in Cerb, which are used to retrieve a list of fields from a specified record type. It outlines the required and optional inputs for these queries, such as the record type, filters, result limits, and pagination. The response format is primarily in dictionaries, suitable for integration with sheets and APIs. The page includes an example query for ticket records and a comprehensive response detailing various field attributes like key, immutability, requirement status, notes, and data types. This information is crucial for users looking to understand and utilize the `record.fields` query functionality effectively."
tags: ["docs"]
---
# record.fields

`record.fields` data queries return a filterable and pageable list of fields from a [record type](/docs/records/types/).

### Inputs

| Req'd | Key | Notes |
| --- | --- | --- |
| **x** | `of:` | The [record type](/docs/records/types/) |
| &nbsp; | `filter:` | An optional keyword used to filter the results |
| &nbsp; | `limit:` | The desired number of results per page |
| &nbsp; | `page:` | The desired starting page (zero-based) |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

#### Query:

```
type:record.fields
of:ticket
format:dictionaries
```

#### Response:

```
{
  "data": {
    "bucket_id": {
      "key": "bucket_id",
      "is_immutable": false,
      "is_required": false,
      "notes": "The ID of the [bucket](/docs/records/types/bucket/) containing this ticket",
      "type": "id"
    },
    "closed": {
      "key": "closed",
      "is_immutable": false,
      "is_required": false,
      "notes": "The date/time this ticket was first set to status `closed`",
      "type": "timestamp"
    },
    "created": {
      "key": "created",
      "is_immutable": false,
      "is_required": false,
      "notes": "The date/time when this record was created",
      "type": "timestamp"
    },
    "elapsed_resolution_first": {
      "notes": "The number of seconds between the creation of this ticket and its first resolution"
    },
    "elapsed_response_first": {
      "notes": "The number of seconds between the creation of this ticket and its first worker response"
    },
    "fieldsets": {
      "key": "fieldsets",
      "is_immutable": false,
      "is_required": false,
      "notes": "An array or comma-separated list of [custom fieldset](/docs/records/types/custom_fieldset/) IDs",
      "type": "fieldsets"
    },
    "group": {
      "key": "group",
      "is_immutable": false,
      "is_required": false,
      "notes": "The [group](/docs/records/types/group/) of the ticket; alternative to `group_id`",
      "type": "string"
    },
    "group_id": {
      "key": "group_id",
      "is_immutable": false,
      "is_required": true,
      "notes": "The ID of the [group](/docs/records/types/group/) containing this ticket",
      "type": "id"
    },
    "importance": {
      "key": "importance",
      "is_immutable": false,
      "is_required": false,
      "notes": "A number from `0` (least) to `100` (most)",
      "type": "number"
    },
    "links": {
      "key": "links",
      "is_immutable": false,
      "is_required": false,
      "notes": "An array of record `type:id` tuples to link to. Prefix with `-` to unlink.",
      "type": "links"
    },
    "mask": {
      "key": "mask",
      "is_immutable": false,
      "is_required": false,
      "notes": "The randomized reference number for this ticket; auto-generated if blank",
      "type": "string"
    },
    "org": {
      "key": "org",
      "is_immutable": false,
      "is_required": false,
      "notes": "The exact name of the [organization](/docs/records/types/org/) linked to this ticket; alternative to `org_id`",
      "type": "string"
    },
    "org_id": {
      "key": "org_id",
      "is_immutable": false,
      "is_required": false,
      "notes": "The ID of the [organization](/docs/records/types/org/) linked to this ticket; alternative to `org`",
      "type": "id"
    },
    "owner_id": {
      "key": "owner_id",
      "is_immutable": false,
      "is_required": false,
      "notes": "The ID of the [worker](/docs/records/types/worker/) responsible for this ticket",
      "type": "id"
    },
    "participant_ids": {
      "key": "participant_ids",
      "is_immutable": false,
      "is_required": false,
      "notes": "A comma-separated list of email addresses IDs to add or remove as participants. Prefix an ID with `-` to remove",
      "type": "string"
    },
    "participants": {
      "key": "participants",
      "is_immutable": false,
      "is_required": false,
      "notes": "A comma-separated list of email addresses to add as participants",
      "type": "string"
    },
    "reopen_date": {
      "key": "reopen_date",
      "is_immutable": false,
      "is_required": false,
      "notes": "If status `waiting`, the date/time to automatically change the status back to `open`",
      "type": "timestamp"
    },
    "spam_score": {
      "key": "spam_score",
      "is_immutable": false,
      "is_required": false,
      "notes": "`0.0001` (not spam) to `0.9999` (spam); automatically generated",
      "type": "float"
    },
    "spam_training": {
      "key": "spam_training",
      "is_immutable": false,
      "is_required": false,
      "notes": "`S` (spam), `N` (not spam); blank for non-trained",
      "type": "string"
    },
    "status": {
      "key": "status",
      "is_immutable": false,
      "is_required": false,
      "notes": "`o` (open), `w` (waiting), `c` (closed), `d` (deleted); alternative to `status_id`",
      "type": "string"
    },
    "status_id": {
      "key": "status_id",
      "is_immutable": false,
      "is_required": false,
      "notes": "`0` (open), `1` (waiting), `2` (closed), `3` (deleted); alternative to `status`",
      "type": "number"
    },
    "subject": {
      "key": "subject",
      "is_immutable": false,
      "is_required": true,
      "notes": "The subject of the ticket",
      "type": "string"
    },
    "updated": {
      "key": "updated",
      "is_immutable": false,
      "is_required": false,
      "notes": "The date/time when this record was last modified",
      "type": "timestamp"
    },
    "priority": {
      "key": "priority",
      "is_custom": 180,
      "is_immutable": false,
      "is_required": false,
      "notes": "Priority",
      "type": "string"
    }
  },
  "_": {
    "type": "record.fields",
    "format": "dictionaries"
  }
}
```
