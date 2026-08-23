---
id: "docs-api-requests"
title: "API: Requests"
url: "https://cerb.ai/docs/api/requests/"
summary: "This page provides a detailed overview of how to interact with Cerb's web API using various HTTP verbs, including GET, PUT, POST, PATCH, and DELETE. It explains how each verb is used for different actions such as retrieving, updating, creating, partially modifying, and deleting records. The page also describes how to format requests, including specifying the response format with extensions like .json or .xml and using payloads for certain actions. Examples are provided for each verb to illustrate how to perform specific tasks, such as retrieving a ticket record, updating a subject, searching for tickets, upserting an organization, and deleting a ticket. The page emphasizes the automatic handling of payload encoding when using official libraries and references additional documentation for detailed module and action-specific information."
tags: ["docs"]
---
The web API is organized into [endpoints](/docs/api/endpoints/). For instance, ticket-related actions are found in the [tickets](/docs/api/endpoints/tickets/) module.

A request is performed by pairing an HTTP **verb** with the appropriate **path** as a URL.

An **extension** of `.json` or `.xml` is added to the path to specify the format that the server should use for the response.

Some actions require a **payload**, which uses the standard HTTP POST web form format[1](#fn:http-post) of key/value pairs.

- [Verbs](#verbs)
  - [GET](#get)
  - [PUT](#put)
  - [POST](#post)
  - [PATCH](#patch)
  - [DELETE](#delete)

- [References](#references)

# Verbs

There are five verbs: `GET`, `PUT`, `POST`, `PATCH`, and `DELETE`.

## GET

A `GET` request performs simple retrieval actions. It may only specify options in the URL (i.e. path and query string).

For instance, this request retrieves the record for ticket #5 in JSON format:

```
GET /rest/records/ticket/5.json
```

Similarly, this request retrieves the record for organization #9 in XML format, including custom fields:

```
GET /rest/records/org/9.xml?expand=custom_
```

## PUT

A `PUT` request updates an existing record. It may specify options in the URL and must provide a payload.

For instance, this request updates the subject on ticket record #5 using a form-encoded payload:

```
PUT /rest/records/ticket/5.json
Content-Type: application/x-www-form-urlencoded; charset=utf-8

fields[subject]=This+is+a+new+subject
```

Notice how the path above is exactly the same as the first `GET` example? The path refers to a specific record, and the verb describes what is being done to it. The `PUT` verb performs a different action.

When using the official libraries, payload encoding is performed automatically. In this situation, you would just provide an associative array of key/value pairs instead. The possible keys are available in the documentation for each module and action.

## POST

A `POST` request performs complex actions that require a payload, such as record creation and searching.

For instance, this request searches tickets for masks that start with `ABC` and returns results in JSON format:

```
POST /rest/records/ticket/search.json
Content-Type: application/x-www-form-urlencoded; charset=utf-8

q=mask%3AABC%2A
```

Like with a PUT request, the payload encoding of a POST is handled automatically by the official libraries. The payload format is described by the documentation for each module and action.

## PATCH

A `PATCH` request partially modifies a record. Currently, patching is only used by [upserts](/docs/api/endpoints/records/#upsert).

For instance, this request upserts (updates or inserts) an organization record and returns results in JSON format:

```
PATCH /rest/records/org/upsert.json
Content-Type: application/x-www-form-urlencoded; charset=utf-8

query=name:"Apple"&fields[name]=Apple
```

Like with a PUT request, the payload encoding of a PATCH is handled automatically by the official libraries. The payload format is described by the documentation for each module and action.

## DELETE

A `DELETE` request permanently removes the record specified in the URL.

For instance, this request deletes ticket record #5:

```
DELETE /rest/records/ticket/5.json
```

# References

1. Wikipedia: HTTP POST - http://en.wikipedia.org/wiki/POST\_(HTTP))&nbsp;[↩](#fnref:http-post)

