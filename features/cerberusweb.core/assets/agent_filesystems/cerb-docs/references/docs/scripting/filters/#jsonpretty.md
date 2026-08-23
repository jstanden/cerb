---
id: "docs-scripting-filters--jsonpretty"
title: "Scripting Filter: json_pretty"
url: "https://cerb.ai/docs/scripting/filters/#jsonpretty"
summary: "Prettify a JSON string with proper formatting"
tags: ["docs", "docs-scripting"]
---
## json\_pretty

You can _"prettify"_ a JSON string with the **json\_pretty** filter:

```
{% set json = {'name': 'Joe Customer'} %}
{% set json = dict_set(json, 'order_id', 54321) %}
{% set json = dict_set(json, 'status.text', 'shipped') %}
{% set json = dict_set(json, 'status.tracking_id', 'Z1F238') %}
{{json|json_encode|json_pretty}}
```

```
{
  "name": "Joe Customer",
  "order_id": 54321,
  "status": {
    "text": "shipped",
    "tracking_id": "Z1F238"
  }
}
```
