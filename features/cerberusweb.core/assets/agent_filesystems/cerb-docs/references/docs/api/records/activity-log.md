---
id: "docs-api-records-activity-log"
title: "Activity Log"
url: "https://cerb.ai/docs/api/records/activity-log/"
summary: "This page provides detailed examples of how to interact with the activity log in Cerb through its REST API. It includes instructions on retrieving an activity log record by its ID, searching for activity log entries related to a specific record, and creating a new activity log entry. Each example includes the necessary HTTP requests and expected JSON responses, demonstrating how to authenticate and format data for these operations. The page is a practical guide for developers looking to manage activity logs programmatically within the Cerb platform."
tags: ["docs"]
---
- [Examples](#examples)
  - [Get an activity log record by ID](#get-an-activity-log-record-by-id)
  - [Search activity log entries on a specific record](#search-activity-log-entries-on-a-specific-record)
  - [Create an activity log entry](#create-an-activity-log-entry)

# Examples

## Get an activity log record by ID

**Request:**

```
GET /rest/records/activity_log/1.json?expand=custom_&show_meta=0 HTTP/1.1
Cerb-Auth: XXXX:XXXX
Date: Sun, 23 Aug 2026 03:15:26 America
Host: cerb.example
Content-Type: application/x-www-form-urlencoded; charset=utf-8
```

**Response:**

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.activity_log",
  "_label": "Kina Halpue logged in from ::1 using Safari 10.1.1 for Macintosh",
  "activity_point": "worker.logged_in",
  "actor__context": "cerberusweb.contexts.worker",
  "actor_id": "1",
  "created": "1497056804",
  "event": "Worker Logged In",
  "id": "1",
  "target__context": "",
  "target_id": "0"
}
```

## Search activity log entries on a specific record

**Request:**

```
GET /rest/records/activity_log/search.json?q=activity:worker.logged_in&show_meta=0 HTTP/1.1
Cerb-Auth: XXXX:XXXX
Date: Sun, 23 Aug 2026 03:15:26 America
Content-Type: application/x-www-form-urlencoded; charset=utf-8
Host: cerb.example
```

**Response:**

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "count": 10,
  "page": 1,
  "results": [
    {
      "_context": "cerberusweb.contexts.activity_log",
      "_label": "Kina Halpue logged in from ::1 using Safari 10.1.1 for Macintosh",
      "id": "1",
      "activity_point": "worker.logged_in",
      "created": "1497056804",
      "event": "Worker Logged In",
      "actor__context": "cerberusweb.contexts.worker",
      "actor_id": "1",
      "target__context": "",
      "target_id": "0"
    },
    ...
  ],
  "total": "1000"
}
```

## Create an activity log entry

**Request:**

```
POST /rest/records/activity_log/create.json?show_meta=0 HTTP/1.1
Cerb-Auth: XXXX:XXXX
Date: Sun, 23 Aug 2026 03:15:26 America
Content-Type: application/x-www-form-urlencoded; charset=utf-8
Host: cerb.example
```

```
fields[activity_point]=custom.other
&fields[actor__context]=worker
&fields[actor_id]=1
&fields[created]=1510354073
&fields[params][message]=This is a custom message on another worker.
&fields[target__context]=worker
&fields[target_id]=3
```

- The `POST` fields should be URL-encoded. They are decoded here for readability.

**Response:**

```
{
  "__build": 2017110901,
  "__status": "success",
  "__version": "8.2.2",
  "_context": "cerberusweb.contexts.activity_log",
  "_label": "This is a custom message on another worker.",
  "activity_point": "worker.logged_in",
  "actor__context": "cerberusweb.contexts.worker",
  "actor_id": "1",
  "created": "1510354073",
  "event": "(Other)",
  "id": "2615",
  "target__context": "cerberusweb.contexts.worker",
  "target_id": "3"
}
```
