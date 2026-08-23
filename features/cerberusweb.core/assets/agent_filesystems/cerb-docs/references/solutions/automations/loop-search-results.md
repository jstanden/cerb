---
id: "solutions-automations-loop-search-results"
title: "Loop search results"
url: "https://cerb.ai/solutions/automations/loop-search-results/"
summary: "This page demonstrates how to use the repeat function in automation scripting to efficiently iterate through search results. It shows best practices for processing multiple records using record IDs, making it useful for batch operations and record management tasks."
tags: ["solutions", "solutions-automations"]
---
## Using record.search: and repeat:

Here is an example using the repeat: command for iterating through record IDs (using `records[id]` notation) in automation scripting.

This approach is more efficient than looping through records directly since it doesn't duplicate the list during iteration.

- [automation](#)
- [policy](#)

- 
```
start:
  record.search:
    output: tickets
    inputs:
      record_type: ticket
      record_query: status:o limit:10
      record_expand: customfields
  
  repeat:
    each@csv: {{tickets|keys|join(',')}}  
    as: ticket_id
    do:
      log: Editing #{{ticket_id}} {{tickets[ticket_id]._label}}
```
- 
```
commands:
  record.search:
    deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
    allow@bool: yes
```

