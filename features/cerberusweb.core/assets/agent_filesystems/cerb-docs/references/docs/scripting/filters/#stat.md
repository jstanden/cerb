---
id: "docs-scripting-filters--stat"
title: "Scripting Filter: stat"
url: "https://cerb.ai/docs/scripting/filters/#stat"
summary: "Calculate statistical measures for an array of numbers"
tags: ["docs", "docs-scripting"]
---
## stat

Calculate a statistical measure for a given array of numbers.

`|stat(measure, decimals)`

| **measure** | `count`, `max`, `mean`, `median`, `min`, `mode`, `stdevp`, `stdevs`, `sum`, `varp`, `vars` |
| **decimals** | The number of decimal places for rounding |

```
{% set samples = [1,2,3,4,5,6,7,8,9,10] %}
{{samples|stat(measure='median')}}
```

```
5.5
```
