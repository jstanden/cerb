---
id: "docs-scripting-functions--arrayvalues"
title: "Scripting Function: array_values"
url: "https://cerb.ai/docs/scripting/functions/#arrayvalues"
summary: "Return values from an associative array as a new indexed array"
tags: ["docs", "docs-scripting"]
---
## array\_values

Return the values from an associative array as a new indexed array. For instance, this can affect the output in JSON encoding by using `[]` rather than `{key:value}`.

```
{% set arr = {"z":"A", "a":"B", "m":"C"} %}
{{array_values(arr)|json_encode}}
```

```
["A","B","C"]
```
