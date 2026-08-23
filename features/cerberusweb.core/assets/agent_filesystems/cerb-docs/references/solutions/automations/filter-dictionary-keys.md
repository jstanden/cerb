---
id: "solutions-automations-filter-dictionary-keys"
title: "Filter dictionary keys"
url: "https://cerb.ai/solutions/automations/filter-dictionary-keys/"
summary: "This page demonstrates how to use the filter modifier with arrow functions to filter dictionaries based on their key names. It shows how to use lambda expressions with key parameters to filter objects by specific key patterns."
tags: ["solutions", "solutions-automations"]
---
## Filtering headers by prefix

Here is an example of using the |filter modifier with arrow functions to filter dictionary keys.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message_headers:
      x-gitlab-project: abc123
      x-blah: def456
      x-example: ghi789
  return:
    gitlab_headers@json: {{message_headers|filter((v,k) => k is prefixed ('x-gitlab'))|json_encode}}
```
- 
```
__return:
  gitlab_headers:
    x-gitlab-project: abc123
```

