---
id: "docs-scripting-filters--regexp"
title: "Scripting Filter: regexp"
url: "https://cerb.ai/docs/scripting/filters/#regexp"
summary: "Use regular expressions to match or extract patterns"
tags: ["docs", "docs-scripting"]
---
## regexp

You can use regular expressions[4](#fn:regexp) with the **regexp** filter to match or extract patterns.

`|regexp(pattern,group)`

- `pattern` The regular expression pattern to match.
- `group`: The matching group `()` from the pattern to extract as a string.

Example:

```
{% set text = "Your Amazon Order #Z-1234-5678-9 has shipped!" %}
{% set order_id = text|regexp("/Amazon Order #([A-Z0-9\-]+)/", 1) %}
Amazon Order #: {{order_id}}
```

```
Amazon Order #: Z-1234-5678-9
```

If you need to escape characters in your regexp pattern, you should use a [set](/docs/scripting/commands/#set) block rather than a string:

```
{% set pattern %}
#\[.*?\] (.*)#
{% endset %}
{% set bracketed_text = "[ABC-123-45678] Order Processing - 7 Days" %}
{{bracketed_text|regexp(pattern, 1)}}
```

```
Order Processing - 7 Days
```
