---
id: "docs-scripting-functions--arraysortkeys"
title: "Scripting Function: array_sort_keys"
url: "https://cerb.ai/docs/scripting/functions/#arraysortkeys"
summary: "Sort an associative array by its keys rather than values"
tags: ["docs", "docs-scripting"]
---
## array\_sort\_keys

Sort an associative array by its keys rather than its values.

```
{% set arr = {"z":"A", "a":"B", "m":"C"} %}
{% set arr = array_sort_keys(arr) %}
{{arr|keys|join(',')}}
```

```
a,m,z
```
