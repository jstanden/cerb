---
id: "docs-scripting-filters--jsonencode"
title: "Scripting Filter: json_encode"
url: "https://cerb.ai/docs/scripting/filters/#jsonencode"
summary: "Encode any variable as a JSON string"
tags: ["docs", "docs-scripting"]
---
## json\_encode

You can encode any variable as a JSON string with the **json\_encode** filter:

```
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode}}
```

```
{"name":"Joe Customer","order_id":54321,"status":{"text":"shipped","tracking_id":"Z1F238"}}
```
