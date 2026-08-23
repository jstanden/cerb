---
id: "solutions-automations-return-dictionary-keys"
title: "Return dictionary keys"
url: "https://cerb.ai/solutions/automations/return-dictionary-keys/"
summary: "This page demonstrates how to use the `|keys` filter to extract property names from dictionaries and arrays. The example shows how to retrieve and format a list of keys from a dictionary containing contact information."
tags: ["solutions", "solutions-automations"]
---
## Using |keys filter

Here is an example of using the [|keys](/docs/scripting/filters/#keys) filter to get a list of property names from a dictionary.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    person:
      name_first: Kina
      name_last: Halpue
      email: kina.halpue@cerb.example
  return:
    keys@csv: {{person|keys|join(', ')}}
```
- 
```
__return:
  keys:
  - name_first
  - name_last
  - email
```

