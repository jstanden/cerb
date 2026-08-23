---
id: "docs-scripting-filters--filter"
title: "Scripting Filter: filter"
url: "https://cerb.ai/docs/scripting/filters/#filter"
summary: "Exclude items from an array using an arrow function"
tags: ["docs", "docs-scripting"]
---
## filter

Exclude items from an array using an arrow function.

`|filter(func)`

| **func(v,k)** | An arrow function that returns `true` (include) or `false` (exclude) for each item. It receives `v` (value) and `k` (key) as arguments. |

```
{% set arr = [1,2,3,4,5,6,7,8] %}
{{arr|filter((v,k) => v is even)|values|join(',')}}
```

```
2,4,6,8
```
