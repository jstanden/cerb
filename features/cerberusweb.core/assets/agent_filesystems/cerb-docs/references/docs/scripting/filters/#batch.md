---
id: "docs-scripting-filters--batch"
title: "Scripting Filter: batch"
url: "https://cerb.ai/docs/scripting/filters/#batch"
summary: "Break a list into smaller chunks"
tags: ["docs", "docs-scripting"]
---
## batch

Break a list into smaller chunks with **batch**:

```
{% set items = ['red','blue','green'] %}
{{items|batch(2, '(empty)')|json_encode|json_pretty}}
```

```
[
    [
        "red",
        "blue"
    ],
    [
        "green",
        "(empty)"
    ]
]
```
