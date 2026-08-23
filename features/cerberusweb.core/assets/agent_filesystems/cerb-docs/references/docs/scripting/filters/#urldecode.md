---
id: "docs-scripting-filters--urldecode"
title: "Scripting Filter: url_decode"
url: "https://cerb.ai/docs/scripting/filters/#urldecode"
summary: "Decode a URL query string into an array"
tags: ["docs", "docs-scripting"]
---
## url\_decode

Decode a URL query string into an array:

```
{% set query = "name=Kina&action=light_on" %}
{{query|url_decode('json')}}
```

```
{"name":"Kina","action":"light_on"}
```
