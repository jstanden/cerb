---
id: "docs-api-endpoints-groups"
title: "Groups"
url: "https://cerb.ai/docs/api/endpoints/groups/"
summary: "This page provides instructions for modifying group rosters in Cerb using the REST API. It details the use of the PUT method at the endpoint `/rest/groups/members.json` to update group memberships. The page outlines the required parameters, specifically a JSON-formatted string that specifies changes to group memberships, such as assigning roles or removing members. An example is provided to demonstrate how to structure the JSON data and make the API call to update group information."
tags: ["docs"]
---
# Members

**PUT /rest/groups/members.json**

Modify group rosters.

### Parameters

| Field | Type |
| --- | --- |
| `json` | changes string in JSON format |

### Example

```
PUT /rest/groups/123.json
Host: cerb.example
Authorization: Bearer <token>

json={"groups":{"1":{"workers":{"1":"manager","2":"remove","3":"member"}},"2":{"workers":{"2":"remove"}}}}
```
