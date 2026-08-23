---
id: "solutions-automations-debug-search-query"
title: "Debug search query SQL"
url: "https://cerb.ai/solutions/automations/debug-search-query/"
summary: "This page demonstrates how to use the `cerb.commands.worklist.query.debug` command to expose the SQL query that powers any record search. This is valuable for debugging performance issues, understanding how queries are constructed, and optimizing search operations."
tags: ["solutions", "solutions-automations"]
---
You can use the `cerb.commands.worklist.query.debug` command to view the SQL statement that will be executed for any record search query.

## Debug a ticket search query

- [automation](#)
- [policy](#)
- [output](#)

- 
```
start:
  api.command:
    output: results
    inputs:
      name: cerb.commands.worklist.query.debug
      params:
        record_type: ticket
        query: status:o created:"-1 week" group:(name:"Support")
```
- 
```
commands:
  api.command:
    deny/name@bool: {{inputs.name not in ['cerb.commands.worklist.query.debug']}}
    allow@bool: yes
```
- 
```
results:
  sql: SELECT t.id AS t_id , t.updated_date AS t_updated_date FROM ticket t WHERE
    t.status_id IN (0) AND t.created_date BETWEEN 1736496000 and 1737155624 AND t.group_id
    IN (SELECT g.id FROM worker_group g WHERE (g.name = 'Support') ) ORDER BY t_updated_date
    DESC LIMIT 0,10
```

