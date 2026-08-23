---
id: "docs-api-endpoints-workers"
title: "Workers"
url: "https://cerb.ai/docs/api/endpoints/workers/"
summary: "This page provides information on how to retrieve the current worker's object in Cerb using the API endpoint `GET /rest/workers/me.json`. It includes an example of how to make this API call using a GET request, demonstrating how to access the current worker's data based on the provided API credentials."
tags: ["docs"]
---
# Current Worker

**GET /rest/workers/me.json**

Retrieve the object for the current worker based on the given API credentials.

### Example

```
GET /rest/workers/me.json
Host: cerb.example
Authorization: Bearer <token>
```
