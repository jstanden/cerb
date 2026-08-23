---
id: "docs-scripting-functions--jsondecode"
title: "Scripting Function: json_decode"
url: "https://cerb.ai/docs/scripting/functions/#jsondecode"
summary: "Decode a JSON-encoded string into an object"
tags: ["docs", "docs-scripting"]
---
## json\_decode

You can decode a JSON-encoded string with the **json\_decode** function:

```
{% set json_string = "{\"name\":\"Joe Customer\",\"order_id\":12345}" %}
{% set json = json_decode(json_string) %}
Customer: {{json.name}}
Order #: {{json.order_id}}
```

```
Customer: Joe Customer
Order #: 12345
```

This returns an [object](/docs/scripting/arrays-objects/).
