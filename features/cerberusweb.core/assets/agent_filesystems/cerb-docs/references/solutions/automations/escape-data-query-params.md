---
id: "solutions-automations-escape-data-query-params"
title: "Escape untrusted data query parameters"
url: "https://cerb.ai/solutions/automations/escape-data-query-params/"
summary: "This page demonstrates how to securely handle user input in data queries by using query parameters. It shows how to prevent query injection attacks by using the ${...} syntax for parameter substitution instead of direct placeholder interpolation."
tags: ["solutions", "solutions-automations"]
---
## Safely handling untrusted user input in data queries

When using the [data.query:](/docs/automations/commands/data.query/) command, the `query_params:` option provides a secure way to substitute untrusted user input into queries.

Its value is a dictionary. The `${...}` placeholder syntax in a query references these sanitized keys. These placeholders aren't evaluated until the query is parsed, so they can never modify the structure of the query (e.g. changing other filters).

- [automation](#)
- [policy](#)

- 
```
start:
  data.query:
    output: results
    inputs:
      query@text:
        type:worklist.records
        of:ticket
        query:(
          participant:(email:${email})
          status:o
        )
        format:dictionaries
      query_params:
        email: customer@cerb.example
```
- 
```
commands:
  data.query:
    deny/type@bool: {{query.type != 'worklist.records'}}
    allow@bool: yes
```

## Unsafe placeholders (vulnerable to injection)

Here's an example of an unsafe data query where malicious user input in the `{{email}}` placeholder breaks out the filter and matches all records.

This happens because the placeholder is evaluated before the query is parsed.

```
start:
  set:
    email: "blah") OR (id:>0
  data.query:
    output: results
    inputs:
      query@text:
        type:worklist.records
        of:ticket
        query:(
          participant:(email:{{email}})
          status:o
        )
        format:dictionaries
```
