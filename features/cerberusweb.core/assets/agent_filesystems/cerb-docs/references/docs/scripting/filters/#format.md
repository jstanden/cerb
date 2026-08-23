---
id: "docs-scripting-filters--format"
title: "Scripting Filter: format"
url: "https://cerb.ai/docs/scripting/filters/#format"
summary: "Insert variables into a string using formatting specifiers"
tags: ["docs", "docs-scripting"]
---
## format

Insert variables into a [string](/docs/scripting/strings/):

```
{% set who = "Kina" %}
{% set quantity = 120 %}
{{"%s closed %d tickets today!"|format(who, quantity)}}
```

```
Kina closed 120 tickets today!
```

For formatting specifiers, see: https://www.php.net/sprintf
