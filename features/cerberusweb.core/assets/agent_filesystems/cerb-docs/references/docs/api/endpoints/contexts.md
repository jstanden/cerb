---
id: "docs-api-endpoints-contexts"
title: "Contexts"
url: "https://cerb.ai/docs/api/endpoints/contexts/"
summary: "This webpage provides detailed API documentation for Cerb, focusing on managing record types, activity logs, and links. It explains how to retrieve a list of object contexts, including those contributed by plugins, using the GET method. The page also covers how to list and create activity log events, detailing the necessary fields and data formats for creating log entries. Additionally, it describes how to add and remove links between context records using POST requests, with examples illustrating the process for both linking and unlinking records. The documentation is aimed at developers looking to integrate or extend Cerb's functionality through its REST API."
tags: ["docs"]
---
- [Record Types](#record-types)
  - [List](#list)

- [Activity Log](#activity-log)
  - [List Events](#list-events)
  - [Create](#create)

- [Links](#links)
  - [Link](#link)
  - [Unlink](#unlink)

# Record Types

## List

**GET /rest/contexts/list.json**

Retrieve a list of object contexts with IDs, names, custom fields and fieldsets. This includes object contexts contributed by [plugins](/docs/plugins/).

**Example:**

```
GET /rest/contexts/list.json  
Host: cerb.example  
Authorization: Bearer <token>
```

# Activity Log

## List Events

**GET /rest/contexts/activity/events.json**

Retrieve a list of activity log event IDs and names, including those contributed by [plugins](/docs/plugins/).

**Example:**

```
GET /rest/contexts/activity/events.json  
Host: cerb.example  
Authorization: Bearer <token>
```

## Create

**POST /rest/contexts/activity/create.json**

Create an activity log entry.

| Field | Type |
| --- | --- |
| `on` | string |
| `activity_point` | string |
| `variables` | JSON array |
| `urls` | JSON array |

- The `on` field should be a target record in `context:context_id` format.

- The `activity_point` field contains an activity ID from List Events request above. You can implement new Activity Log events in the `<activity_points>` section of a [plugin.xml](/docs/plugins/) manifest.

- The `variables` field should be a JSON encoded array, where the key is a placeholder in the activity log message and the value is the text to substitute.

- The `urls` field is an option JSON encoded array, where the key is a placeholder in the activity log message and the value is the URL to use for hyperlinking the text.

You do not need to provide a value for {{actor}} in variables or urls since this is handled automatically by Cerb.

**Example:**

```
POST /rest/contexts/activity/create.json
Host: cerb.example
Authorization: Bearer <token>

on=cerberusweb.contexts.worker:2
&activity_point=example.worker_high_five
&variables={"target":"Dan Hildebrandt"}
&urls={"target":"ctx://cerberusweb.contexts.worker:2"}
```

# Links

## Link

**POST /rest/contexts/link.json**

Add any number of links to one context record.

| Field | Type |
| --- | --- |
| `on` | string |
| `targets` | JSON array string |

**Example:**

```
POST /rest/contexts/link.json
Host: cerb.example
Authorization: Bearer <token>

on=cerberusweb.contexts.ticket:1148
&targets=["cerberusweb.contexts.org:17581","cerberusweb.contexts.address:5447"]
```

## Unlink

**POST /rest/contexts/unlink.json**

Remove any number of links from one context record.

| Field | Type |
| --- | --- |
| `on` | string |
| `targets` | JSON array string |

**Example:**

```
POST /rest/contexts/unlink.json
Host: cerb.example
Authorization: Bearer <token>

on=cerberusweb.contexts.ticket:1148
&targets=["cerberusweb.contexts.org:17581"]
```
