---
id: "docs-scripting-functions--arraydiff"
title: "Scripting Function: array_diff"
url: "https://cerb.ai/docs/scripting/functions/#arraydiff"
summary: "Return items in the second array that are not present in the first array"
tags: ["docs", "docs-scripting"]
---
## array\_diff

The **array\_diff** function returns the items in the second array that are not present in the first array:

```
{% set arr1 = ['Apple', 'Google', 'Microsoft'] %}
{% set arr2 = ['Apple', 'Microsoft', 'Cerb'] %}
{% set diff = array_diff(arr2, arr1) %}
These are new: {{diff|join(', ')}}
```

```
These are new: Cerb
```
