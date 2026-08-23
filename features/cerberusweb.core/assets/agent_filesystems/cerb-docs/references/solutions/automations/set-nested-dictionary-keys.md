---
id: "solutions-automations-set-nested-dictionary-keys"
title: "Set nested dictionary keys"
url: "https://cerb.ai/solutions/automations/set-nested-dictionary-keys/"
summary: "This page demonstrates how to use the `dict_set()` function to set deeply nested keys in dictionaries. It shows how to construct complex nested data structures by setting values at specific paths within a dictionary."
tags: ["solutions", "solutions-automations"]
---
## Using dict\_set() function

Here is an example of using the [dict\_set()](/docs/scripting/functions/#dict_set) function to set deeply nested keys in dictionaries.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    worker@json:
      {% set var = {"group": {}} %}
      {% set var = dict_set(var, 'group.name', 'Support') %}
      {% set var = dict_set(var, 'group.manager.name.first', 'Kina') %}
      {% set var = dict_set(var, 'group.manager.name.last', 'Halpue') %}
      {{var|json_encode}}
  return:
    worker@key: worker
```
- 
```
__return:
  worker:
    group:
      name: Support
      manager:
        name:
          first: Kina
          last: Halpue
```

