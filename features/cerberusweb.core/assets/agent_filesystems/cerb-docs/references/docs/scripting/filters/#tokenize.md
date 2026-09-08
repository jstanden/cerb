---
id: "docs-scripting-filters--tokenize"
title: "Scripting Filter: tokenize"
url: "https://cerb.ai/docs/scripting/filters/#tokenize"
summary: "Return an array of word tokens from a text block"
tags: ["docs", "docs-scripting"]
---
## tokenize

Return an array of word tokens from a text block. This ignores punctuation and returns tokens in the order they appear, including duplicates.

`|tokenize`

```
{% set message %}
support support support support ticket ticket ticket reply reply queue
{% endset %}
{{array_count_values(message|tokenize)|sort|reverse|json_encode|json_pretty}}
```

```
{
    "support": 4,
    "ticket": 3,
    "reply": 2,
    "queue": 1
}
```

Sorting counts puts the most frequent tokens first, but **sort** is not stable for equal values, so tokens with the same count can come back in any order.
