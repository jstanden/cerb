---
id: "docs-scripting-filters--map"
title: "Scripting Filter: map"
url: "https://cerb.ai/docs/scripting/filters/#map"
summary: "Apply a function to each item in an array to create a new array"
tags: ["docs", "docs-scripting"]
---
## map

Apply a function to each item in an array to create a new array.

`|map(func)`

| **func(v,k)** | An arrow function that returns the new value for each item. It receives `v` (value) and `k` (key) as arguments. |

```
{% set samples = [
	[1,2,3,4,5],
	[6,7,8,9,10],
	[1,3,5,7,9],
	[2,4,6,8,10],
] %}
Averages:
{{samples|map((v,k) => array_sum(v)/(samples[k]|length))|join(', ')}}
```

```
Averages:
3, 8, 5, 6
```
