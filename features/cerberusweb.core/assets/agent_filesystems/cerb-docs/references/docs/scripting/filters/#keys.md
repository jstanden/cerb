---
id: "docs-scripting-filters--keys"
title: "Scripting Filter: keys"
url: "https://cerb.ai/docs/scripting/filters/#keys"
summary: "Return the keys of an array or object"
tags: ["docs", "docs-scripting"]
---
## keys

Return the keys of an array or object:

```
{% set list = ['red','green','blue'] %}
{% set obj = { 'name': 'Kina', 'age': 35, 'title': 'Customer Support Supervisor'} %}

{{list|keys|join(',')}}
{{obj|keys|json_encode}}
```

```
0,1,2

["name","age","title"]
```
