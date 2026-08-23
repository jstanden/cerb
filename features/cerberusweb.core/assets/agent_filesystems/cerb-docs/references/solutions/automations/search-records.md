---
id: "solutions-automations-search-records"
title: "Search records with a query"
url: "https://cerb.ai/solutions/automations/search-records/"
summary: "This page provides instructions on using the `record.search` command in Cerb to query and load records for automation purposes. It includes examples of how to find open tickets and workers who have been active within the past 30 minutes. The page also outlines the policies for these queries, specifying conditions under which the `record.search` command is allowed or denied based on the type of record being queried."
tags: ["solutions", "solutions-automations"]
---
record.search: can be used to load records and make them available to an automation.

## Find open tickets:

- [automation](#)
- [policy](#)

- 
```
start:
  record.search:
    inputs:
      record_type: ticket
      record_query: status:o
    output: results
```
- 
```
commands:
  record.search:
    deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
    allow@bool: yes
```

## Find workers active within the past 30 minutes:

- [automation](#)
- [policy](#)

- 
```
start:
  record.search:
    inputs:
      record_type: worker
      record_query: isDisabled:no lastActivity:${when}
      record_query_params:
        when: -30 mins
    output: results
```
- 
```
commands:
  record.search:
    deny/type@bool: {{inputs.record_type is not record type ('worker')}}
    allow@bool: yes
```

