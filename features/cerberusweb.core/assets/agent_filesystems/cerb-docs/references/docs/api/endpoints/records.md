---
id: "docs-api-endpoints-records"
title: "Records"
url: "https://cerb.ai/docs/api/endpoints/records/"
summary: "This page provides comprehensive documentation for the Cerb records API, detailing how to create, retrieve, update, upsert, search, and delete records through a single endpoint. It explains the use of specific HTTP methods for each operation, such as GET for retrieval, POST for creation, PUT for updates, PATCH for upserts, and DELETE for record removal. The page also covers how to manage record links, including linking and unlinking records, with examples for each operation. Parameters for each API call are outlined, including fields for setting record attributes and search queries for filtering results. The documentation is designed to guide users in effectively managing records within Cerb using the API."
tags: ["docs"]
---
The **records** API can abstractly create, retrieve, update, upsert, search, and delete all records in Cerb from a single [endpoint](/docs/api/endpoints/).

This endpoint target a specific [record type](/docs/records/types/) as `<uri>` in the path.

- [Retrieve](#retrieve)
- [Create](#create)
- [Update](#update)
- [Upsert](#upsert)
- [Search](#search)
- [Delete](#delete)
- [Links](#links)
  - [Unlink](#unlink)

# Retrieve

**GET /rest/records/`<uri>`/`<id>`.json**

Retrieve a record [dictionary](/docs/guide/developers/dictionaries/).

### Example

```
GET /rest/records/asset/1.json
Host: cerb.example
Authorization: Bearer <token>
```

# Create

**POST /rest/records/`<uri>`/create.json**

Create a new record.

### Parameters

| Field | Type | &nbsp; |
| --- | --- | --- |
| `fields[]` | mixed | **required** This field can be provided multiple times for each field to set. The field names are found on each [record type](/docs/records/types/). |

### Example

```
POST /rest/records/address/create.json
Host: cerb.example
Authorization: Bearer <token>

expand=custom_
&fields[email]=customer@cerb.example
&fields[is_banned]=0
&fields[custom_123]=Some+value
```

# Update

**PUT /rest/records/`<uri>`/`<id>`.json**

Update a record object.

### Parameters

| Field | Type | &nbsp; |
| --- | --- | --- |
| `fields[]` | mixed | **required** This field can be provided multiple times for each field to set. The field names are found on each [record type](/docs/records/types/). |

### Example

```
PUT /rest/records/group/1.json
Host: cerb.example
Authorization: Bearer <token>

fields[is_private]=0
&fields[name]=Support
&fields[reply_personal]=Example+Support+Team
```

# Upsert

**PATCH /rest/records/`<uri>`/upsert.json**

Update an existing record if matched, or create a new record otherwise.

### Parameters

| Field | Type | &nbsp; |
| --- | --- | --- |
| `fields[]` | mixed | **required** This field can be provided multiple times for each field to set. The field names are found on each [record type](/docs/records/types/). |
| `query` | string | The [search query](/docs/search/) for detecting a match. This must return either `0` (create) or `1` (update) results. Anything else returns an error. |

### Example

```
PATCH /rest/records/org/upsert.json
Host: cerb.example
Authorization: Bearer <token>

fields[country]=United+States
&fields[name]=Apple%2C+Inc.
&fields[website]=https%3A%2F%2Fapple.com
&query=name%3A%22Apple%2C+Inc.%22
```

# Search

**GET /rest/records/`<uri>`/search.json**

Search for matching records.

### Parameters

| Field | Description | Type |
| --- | --- | --- |
| `expand` | The keys to expand for each object as a comma-separated list | string |
| `limit` | The number of results to display per page | integer |
| `page` | The page of results to display given limit | integer |
| `q` | Filters to add using a [search query](/docs/search/) | string |
| `subtotals[]` | Multiple subtotal sets can be returned | string |

**expand**

Includes additional information in the response. These options vary depending on the [record type](/docs/records/types/).

| Field | Description |
| --- | --- |
| `custom_` | Include custom field values for each ticket. |
| `watchers` | Include watcher records for each ticket. |

**subtotals[]**

Return subtotal results based on the given fields. These options vary depending on the [record type](/docs/records/types/).

### Example

```
GET /rest/records/attachments/search.json?q=mimetype%3A%22image%2Fpng%22+size%3A%3E200kb+sort%3A-size+page%3A1
Host: cerb.example
Authorization: Bearer <token>
```

# Delete

**DELETE /rest/records/`<uri>`/`<id>`.json**

Delete a record.

### Example

```
DELETE /rest/records/call/1.json
Host: cerb.example
Authorization: Bearer <token>
```

# Links

You can create record [links](/docs/records/fields/types/links/) with the `links` field key in a create, update, or upsert request.

The value must be a `context:id` tuple identifying the record to link to.

The key can be provided multiple times to link multiple records. Be sure to append `[]` to the field key, like the example below.

### Example

```
PUT /rest/records/task/1.json
Host: cerb.example
Authorization: Bearer <token>

fields[links][]=org%3A123
&fields[links][]=ticket%3A456
```

## Unlink

You can remove record links by prefixing the tuple with a `-` (dash).

You may mix link additions and removals in the same request.

### Example

```
PUT /rest/records/task/1.json
Host: cerb.example
Authorization: Bearer <token>

fields[links][]=-org%3A123
&fields[links][]=ticket%3A456
```
