---
id: "docs-api-endpoints-bots"
title: "Bots"
url: "https://cerb.ai/docs/api/endpoints/bots/"
summary: "This page provides information on how to execute a specific bot behavior in Cerb using a custom API request. It details the process of running a bot behavior by sending a POST request to a specified endpoint, which returns the values from the behavior's dictionary upon completion. The page outlines the parameters required, which are the public variables defined on the behavior, and provides an example of how to structure the request with various data types such as strings, numbers, dates, booleans, and JSON-encoded arrays. The example demonstrates how to use the API to interact with the bot behavior, including setting custom placeholders to send information back to the API caller."
tags: ["docs"]
---
# Run Bot Behavior

**POST /rest/bots/behavior/`<id>`/run.json**

Execute the specified bot _Custom API Request_ behavior. The response will provide the values from the behavior's dictionary at conclusion. A common strategy is to use the _Set custom placeholder_ action in the bot behavior in order to send information back to the API caller.

The parameters are the public variables defined on the behavior (if any).

| Field | Type |
| --- | --- |
| `var_*` | mixed |

**Example:**

```
POST /rest/bots/behavior/123/run.json  
Host: cerb.example  
Authorization: Bearer <token>  
Content-Type: application/x-www-form-urlencoded  

var_name=Jeff%40WGM  
&var_picklist=Red  
&var_number=1234  
&var_date=tomorrow+5pm  
&var_bool=1  
&var_worker=1  
&var_tickets=%5B1024%2C1025%2C1026%5D
```
