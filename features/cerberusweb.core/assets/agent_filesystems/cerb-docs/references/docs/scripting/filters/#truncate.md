---
id: "docs-scripting-filters--truncate"
title: "Scripting Filter: truncate"
url: "https://cerb.ai/docs/scripting/filters/#truncate"
summary: "Ensure that a string is no longer than the given limit"
tags: ["docs", "docs-scripting"]
---
## truncate

Ensure that a string is no longer than the given limit.

`|truncate(limit)`

```
{% set str = "This string is longer than we'd prefer" %}
{{str|truncate(11)}}
```

```
This string...
```
