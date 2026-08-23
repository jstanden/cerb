---
id: "docs-scripting-filters--striplines"
title: "Scripting Filter: strip_lines"
url: "https://cerb.ai/docs/scripting/filters/#striplines"
summary: "Remove lines that begin with given prefixes"
tags: ["docs", "docs-scripting"]
---
## strip\_lines

Remove lines in a text block that begin with one of the given `prefixes`.

`|strip_lines(prefixes)`

```
{% set email_message %}
> This is some quoted text
> on multiple lines

This is the original message
{% endset %}
{{email_message|strip_lines(prefixes='>')}}
```

```
This is the original message
```
