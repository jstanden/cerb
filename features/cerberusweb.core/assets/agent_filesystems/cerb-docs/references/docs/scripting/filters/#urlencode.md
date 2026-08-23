---
id: "docs-scripting-filters--urlencode"
title: "Scripting Filter: url_encode"
url: "https://cerb.ai/docs/scripting/filters/#urlencode"
summary: "Build a URL query string from an array"
tags: ["docs", "docs-scripting"]
---
## url\_encode

Build a URL query string from an array:

```
{% set args = {"name": "Kina", "action": "light_on" } %}
{{args|url_encode}}
```

```
name=Kina&action=light_on
```
