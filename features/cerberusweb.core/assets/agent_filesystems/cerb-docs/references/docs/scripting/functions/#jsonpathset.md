---
id: "docs-scripting-functions--jsonpathset"
title: "Scripting Function: jsonpath_set"
url: "https://cerb.ai/docs/scripting/functions/#jsonpathset"
summary: "Set values in JSON objects using path notation"
tags: ["docs", "docs-scripting"]
---
## jsonpath\_set

This is nearly identical to [dict\_set](#dict_set).

```
{% set json_string = "{\"name\":\"Joe Customer\",\"order_id\":12345}" %}
{% set json = json_decode(json_string) %}
{% set json = jsonpath_set(json, 'order_id', '67890') %}
{{json.order_id}}
```

```
67890
```

You can specify an array by appending `[]` without a leading dot (`.`):

```
{% set json_string = "{\"team\":{\"groups\":[]}}" %}
{% set json = json_decode(json_string) %}
{% set json = jsonpath_set(json, 'team.groups[]', 'Support') %}
{% set json = jsonpath_set(json, 'team.groups[]', 'Sales') %}
{% set json = jsonpath_set(json, 'team.groups[]', 'Development') %}
{{json|json_encode|json_pretty}}
```

```
{
  "team": {
    "groups": [
      "Support",
      "Sales",
      "Development"
    ]
  }
}
```
