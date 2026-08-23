---
id: "solutions-automations-extract-dictionary-columns"
title: "Extract dictionary columns"
url: "https://cerb.ai/solutions/automations/extract-dictionary-columns/"
summary: "This page explains how to extract a column from a list of dictionaries using Cerb filters. Two methods are provided: `|column` and `|map`. The `|column` filter extracts the specified column directly, while the `|map` filter uses an arrow function to transform each dictionary into a value for that column. Both methods can be used to extract the email columns from a list of people dictionaries, with examples demonstrating their usage."
tags: ["solutions", "solutions-automations"]
---
## Using |column

You can extract the same column from a list of dictionaries with the `|column` filter.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    people:
      kina:
        name: Kina Halpue
        email: kina@cerb.example
      milo:
        name: Milo Dade
        email: milo@cerb.example 
  return:
    emails: {{people|column('email')|join(', ')}}
```
- 
```
__return:
  emails: kina@cerb.example, milo@cerb.example
```

## Using |map

You can extract the same column from a list of dictionaries with the `|map` filter.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    people:
      kina:
        name: Kina Halpue
        email: kina@cerb.example
      milo:
        name: Milo Dade
        email: milo@cerb.example
  return:
    emails: {{people|map((v)=>v['email'])|join(', ')}}
```
- 
```
__return:
  emails: kina@cerb.example, milo@cerb.example
```

