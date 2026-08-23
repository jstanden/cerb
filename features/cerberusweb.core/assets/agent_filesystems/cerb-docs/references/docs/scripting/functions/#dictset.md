---
id: "docs-scripting-functions--dictset"
title: "Scripting Function: dict_set"
url: "https://cerb.ai/docs/scripting/functions/#dictset"
summary: "Add, modify, or append items in an array or object using dot notation"
tags: ["docs", "docs-scripting"]
---
## dict\_set

You can use the **dict\_set** function to quickly add, modify, or append items in an array or object.

`dict_set(object,path,value,delimiter) : object`

**Arguments:**

| Name | Notes |
| --- | --- |
| **object** | The object to modify |
| **path** | The key or key path (with delimiters) to set |
| **value** | The new value for the given key or key path |
| **delimiter** | Defaults to dot (`.`), but may be any character sequence (e.g. `||`) |

**Returns:** The function returns a modified version of `object`.

You can set deeply nested keys in a single line using dot-notation:

```
{% set var = {"group": {}} %}
{% set var = dict_set(var, 'group.name', 'Support') %}
{% set var = dict_set(var, 'group.manager.name.first', 'Kina') %}
{% set var = dict_set(var, 'group.manager.name.last', 'Halpue') %}
{{var|json_encode|json_pretty}}
```

```
{
  "group": {
    "name": "Support",
    "manager": {
      "name": {
        "first": "Kina",
        "last": "Halpue"
      }
    }
  }
}
```

Append items to an array by adding `.[]` to the key:

```
{% set var = {"group": {}} %}
{% set var = dict_set(var, 'group.name', 'Support') %}
{% set var = dict_set(var, 'group.members.[]', 'Kina Halpue') %}
{% set var = dict_set(var, 'group.members.[]', 'William Portcullis') %}
{% set var = dict_set(var, 'group.members.[]', 'Steven Emplois') %}
{{var|json_encode|json_pretty}}
```

```
{
  "group": {
    "name": "Support",
    "members": [
      "Kina Halpue",
      "William Portcullis",
      "Steven Emplois"
    ]
  }
}
```

Append to nested arrays:

```
{% set var = [1,2,[3,4,[5,6]]] %}
{% set var = dict_set(var, '2.2.[]', 7) %}
{% set var = dict_set(var, '2.2.[]', 8) %}
{% set var = dict_set(var, '2.3', 9) %}
{{var|json_encode|json_pretty}}
```

```
[
  1,
  2,
  [
    3,
    4,
    [
      5,
      6,
      7,
      8
    ],
    9
  ]
]
```
