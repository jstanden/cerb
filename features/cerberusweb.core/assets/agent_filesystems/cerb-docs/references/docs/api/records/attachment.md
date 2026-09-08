---
id: "docs-api-records-attachment"
title: "Attachment"
url: "https://cerb.ai/docs/api/records/attachment/"
summary: "This page provides detailed examples of how to create attachments using the Cerb API. It includes instructions for creating attachments with both plaintext and binary content. The examples demonstrate the necessary HTTP POST requests, including the required headers and URL-encoded fields, such as the attachment's name, MIME type, and content. The page also explains how to optionally link attachments to specific contexts using context:id tuples. The responses from the API are shown, indicating successful creation with details like the attachment's ID, MIME type, name, size, storage information, and update timestamp."
tags: ["docs"]
---
- [Examples](#examples)
  - [Create an attachment with plaintext content](#create-an-attachment-with-plaintext-content)
  - [Create an attachment with binary content](#create-an-attachment-with-binary-content)

# Examples

## Create an attachment with plaintext content

**Request:**

```
POST /rest/records/attachment/create.json HTTP/1.1
Cerb-Auth: XXXX:XXXX
Date: Tue, 08 Sep 2026 02:55:17 America
Content-Type: application/x-www-form-urlencoded; charset=utf-8
Host: cerb.example

fields[name]=filename.txt
&fields[mime_type]=text/plain
&fields[content]=This is some plaintext content created through the API.
&fields[attach][]=message:123
&fields[attach][]=comment:123
```

- `&fields[attach][]` is an optional list of `context:id` tuples to link the attachment to.
- The `POST` fields should be URL-encoded. They are decoded here for readability.

**Response:**

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.attachment",
  "_label": "filename.txt",
  "id": 123,
  "mime_type": "text/plain",
  "name": "filename.txt",
  "size": 55,
  "storage_extension": "devblocks.storage.engine.disk",
  "storage_key": "a/b/123",
  "storage_sha1hash": "260588f317aec33c59534dddfa91da68e841c424",
  "updated": 1510680491
}
```

## Create an attachment with binary content

**Request:**

```
POST /rest/records/attachment/create.json?expand= HTTP/1.1
Cerb-Auth: XXXX:XXXX
Date: Tue, 08 Sep 2026 02:55:17 America
Content-Type: application/x-www-form-urlencoded; charset=utf-8
Host: cerb.example

fields[name]=cerby.png
&fields[mime_type]=image/png
&fields[content]=data:application/octet-stream;base64,[BASE64-ENCODED-CONTENT]
&fields[attach][]=message:123
&fields[attach][]=comment:123
```

- Replace `[BASE64-ENCODED-CONTENT]` above with your Base64-encoded binary content.
- The `POST` fields should be URL-encoded. They are decoded here for readability.

**Response:**

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.attachment",
  "_label": "cerby.png",
  "custom": [],
  "id": 123,
  "mime_type": "image/png",
  "name": "cerby.png",
  "size": 15037,
  "storage_extension": "devblocks.storage.engine.disk",
  "storage_key": "a/b/123",
  "storage_sha1hash": "c44ebaf197155c080ae47809dc5cd51c7715fd7c",
  "updated": 1510681295
}
```
