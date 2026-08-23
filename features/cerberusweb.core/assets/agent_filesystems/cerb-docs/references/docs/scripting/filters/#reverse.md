---
id: "docs-scripting-filters--reverse"
title: "Scripting Filter: reverse"
url: "https://cerb.ai/docs/scripting/filters/#reverse"
summary: "Reverse a string or array"
tags: ["docs", "docs-scripting"]
---
## reverse

Reverse a string or array:

```
{{"Leonardo da Vinci"|reverse}}
{{[1,2,3,4,5]|reverse|join}}
```

```
icniV ad odranoeL
54321
```

The optional preserve\_keys parameter will maintain object keys.
