---
id: "docs-api-endpoints-data"
title: "Data"
url: "https://cerb.ai/docs/api/endpoints/data/"
summary: "This page provides instructions on how to run a data query in Cerb using the GET method on the endpoint `/rest/data/query.json`. It includes an example of how to construct a query using PHP's `http_build_query` function to request subtotals of tickets grouped by creation year and group. The example demonstrates how to send the query to the Cerb API to retrieve the desired data."
tags: ["docs"]
---
# Run a data query

**GET /rest/data/query.json**

Run a [data query](/docs/data-queries/).

### Example

```
GET /rest/data/query.json?q=type:worklist.subtotals%20of:tickets%20by:[created@year,group]
Host: cerb.example
Authorization: Bearer <token>
```
