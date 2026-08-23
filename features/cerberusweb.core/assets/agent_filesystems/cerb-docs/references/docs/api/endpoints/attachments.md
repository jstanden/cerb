---
id: "docs-api-endpoints-attachments"
title: "Attachments"
url: "https://cerb.ai/docs/api/endpoints/attachments/"
summary: "This page provides information on how to download attachments using the Cerb API. It includes the specific endpoint for downloading an attachment by its ID and provides an example of how to execute this action using a GET request in a script."
tags: ["docs"]
---
# Download

**GET /rest/attachments/`<id>`/download.json**

Download an attachment.

**Example:**

```
GET /rest/attachments/1/download.json
Host: cerb.example
Authorization: Bearer <token>
```
