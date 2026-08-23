---
id: "docs-scripting-filters--merge"
title: "Scripting Filter: merge"
url: "https://cerb.ai/docs/scripting/filters/#merge"
summary: "Combine two arrays or objects"
tags: ["docs", "docs-scripting"]
---
## merge

Combine two arrays or objects:

```
{% set mfgs = ['Tesla','Ford'] %}
{% set mfgs = mfgs|merge(['Toyota','GM']) %}
{{mfgs|json_encode}}
```

```
["Tesla","Ford","Toyota","GM"]
```
