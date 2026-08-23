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
KATA ("Key Annotated Tree of Attributes") is a human-friendly format for modeling structured 
data that is used throughout Cerb to describe configurations, customizations, sheets, and 
automations. KATA was inspired by YAML but avoids many of its pitfalls.
{% endset %}
{{array_count_values(message|tokenize)|sort|reverse|json_encode|json_pretty}}
```

```
{
    "kata": 2,
    "of": 2,
    "is": 2,
    "that": 1,
    "data": 1,
    "structured": 1,
    "modeling": 1,
    "for": 1,
    "format": 1,
    "friendly": 1,
    "human": 1,
    "a": 1,
    "attributes": 1,
    "tree": 1,
    "annotated": 1,
    "used": 1,
    "key": 1,
    "throughout": 1,
    "to": 1,
    "its": 1,
    "many": 1,
    "avoids": 1,
    "but": 1,
    "yaml": 1,
    "by": 1,
    "cerb": 1,
    "inspired": 1,
    "automations": 1,
    "and": 1,
    "sheets": 1,
    "customizations": 1,
    "configurations": 1,
    "describe": 1,
    "was": 1,
    "pitfalls": 1
}
```
