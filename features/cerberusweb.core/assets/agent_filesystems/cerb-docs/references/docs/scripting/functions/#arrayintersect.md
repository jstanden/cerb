---
id: "docs-scripting-functions--arrayintersect"
title: "Scripting Function: array_intersect"
url: "https://cerb.ai/docs/scripting/functions/#arrayintersect"
summary: "Return elements that are present in both arrays"
tags: ["docs", "docs-scripting"]
---
## array\_intersect

Returns a new array for all the elements in array1 that are also present in array2. This is the opposite of [array\_diff](#array_diff).

```
{% set arr1 = ['Apple', 'Google', 'Microsoft'] %}
{% set arr2 = ['Apple', 'Microsoft', 'Cerb'] %}
{% set intersect = array_intersect(arr2, arr1) %}
These are in both: {{intersect|join(', ')}}
```

```
These are in both: Apple, Microsoft
```
