---
id: "docs-scripting-regex"
title: "Scripting Reference: Regular Expressions"
url: "https://cerb.ai/docs/scripting/regex/"
summary: "This page provides a scripting reference for using regular expressions with the regexp filter in Cerb to match or extract patterns from text. It includes an example of extracting an order ID from a string using a regular expression. Additionally, it references an external link to Wikipedia for further information on regular expressions."
tags: ["docs", "docs-scripting"]
---
You can use regular expressions[1](#fn:regexp) with the [regexp](/docs/scripting/filters/#regexp) filter to match or extract patterns in text:

```
{% set text = "Your Amazon Order #Z-1234-5678-9 has shipped!" %}
{% set order_id = text|regexp("/Amazon Order #([A-Z0-9\-]+)/", 1) %}
Amazon Order #: {{order_id}}
```

```
Amazon Order #: Z-1234-5678-9
```

[\< Loops](/docs/scripting/loops/)

[JSON \>](/docs/scripting/json/)

# References

1. Wikipedia: Regular Expression - https://en.wikipedia.org/wiki/Regular\_expression&nbsp;[↩](#fnref:regexp)

