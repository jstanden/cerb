---
id: "docs-scripting-functions--arraycountvalues"
title: "Scripting Function: array_count_values"
url: "https://cerb.ai/docs/scripting/functions/#arraycountvalues"
summary: "Count occurrences of values in an array"
tags: ["docs", "docs-scripting"]
---
## array\_count\_values

The **array\_count\_values** function takes an array of values as input, and returns an array with distinct values as keys and their count of occurrences. This function only works on arrays of strings or numbers.

```
{% set values = [1,2,3,1,3,2,3,1,2,1,3,1,3] %}
{{array_count_values(values)|json_encode|json_pretty}}
```

```
{
    "1": 5,
    "2": 3,
    "3": 5
}
```
