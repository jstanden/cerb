---
id: "docs-api-endpoints-automations"
title: "Automations"
url: "https://cerb.ai/docs/api/endpoints/automations/"
summary: "This page provides information on how to search automation logs in Cerb. It details the REST API endpoint `GET /rest/automations/logs/search.json` used for retrieving automation logs. The page outlines the parameters that can be used in the search, such as `limit`, `page`, and `q` for query filters. It also describes various query filters that can be applied, including filtering by automation name, node path, creation date, log entry ID, severity level, and log message content. An example of how to use the API to search for logs with a specific severity level is also provided."
tags: ["docs"]
---
# Search logs

**GET /rest/automations/logs/search.json**

Search automation logs.

## Parameters

| Field | Description | Type |
| --- | --- | --- |
| `limit` | The number of results to display per page | integer |
| `page` | The page of results to display given limit | integer |
| `q` | Filters to add using a [search query](/docs/search/) | string |

### Query filters

| Filter | Type | Description |
| --- | --- | --- |
| `automation:(name:)` | [text](/docs/search/#text) | The [automation](/docs/automations/) that output the log entry. |
| `automation:(node:)` | [text](/docs/search/#text) | The path to the `log:` action in the [automation](/docs/automations/). |
| `created:` | [date](/docs/search/#dates) | The date of the log entry. |
| `id:` | [number](/docs/search/#numbers) | The log entry ID (useful for pagination). |
| `level:` | [text](/docs/search/#text) | The severity of the log entry: `error`, `warning`, or `debug`. Match multiple like `level:[warning,error]` or exclude `level:!debug`. |
| `message:` | [text](/docs/search/#text) | Match the automation log message with `*` wildcards. |

## Example

```
GET /rest/automation/logs/search.json?q=level:error  
Host: cerb.example  
Authorization: Bearer <token>
```
