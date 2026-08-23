---
id: "docs-scripting-functions--kataparse"
title: "Scripting Function: kata_parse"
url: "https://cerb.ai/docs/scripting/functions/#kataparse"
summary: "Parse a KATA text block into an object"
tags: ["docs", "docs-scripting"]
---
## kata\_parse

Parses a KATA text block into an object.

```
{% set kata %}
colors@list:
  red
  green
  blue
size@int: 100
{% endset %}
{{kata_parse(kata)|json_encode|json_pretty}}
```

```
{
    "colors@list": "red\ngreen\nblue",
    "size@int": "100"
}
```
