---
id: "docs-scripting-filters--reduce"
title: "Scripting Filter: reduce"
url: "https://cerb.ai/docs/scripting/filters/#reduce"
summary: "Reduce an array of items into a single output value"
tags: ["docs", "docs-scripting"]
---
## reduce

Reduce an array of items into a single output value.

`|reduce(func,initial)`

| **func(carry,v)** | An arrow function that returns the new carry value after each item. It receives the old `carry` value and the current item `v` (value). |
| **initial** | An optional starting value for `carry`. |

```
{% set samples = [
	[1,2,3,4,5],
	[6,7,8,9,10],
	[1,3,5,7,9],
	[2,4,6,8,10],
] %}
Sum:
{{samples|reduce((carry,v) => carry + array_sum(v))}}
```

```
Sum:
110
```
