---
id: "docs-scripting-filters--join"
title: "Scripting Filter: join"
url: "https://cerb.ai/docs/scripting/filters/#join"
summary: "Convert an array to a string with delimiters"
tags: ["docs", "docs-scripting"]
---
## join

Convert an [array](/docs/scripting/arrays-objects/) to a string with delimiters:

```
{% set items = [1,2,3] %}
{{items|join(',')}}
{{items|join(' ')}}
```

```
1,2,3
1 2 3
```
