---
id: "docs-scripting-functions--arrayunique"
title: "Scripting Function: array_unique"
url: "https://cerb.ai/docs/scripting/functions/#arrayunique"
summary: "Return a new array with only distinct values"
tags: ["docs", "docs-scripting"]
---
## array\_unique

Return a new array with only the distinct values from the `array` argument.

```
{% set arr = [1,1,2,2,3,3,4,4,5,5,6] %}
Unique values {{array_unique(arr)|join(',')}}
```

```
Unique values 1,2,3,4,5,6
```
