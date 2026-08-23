---
id: "docs-scripting-filters--append"
title: "Scripting Filter: append"
url: "https://cerb.ai/docs/scripting/filters/#append"
summary: "Append a suffix to the current text with optional delimiter"
tags: ["docs", "docs-scripting"]
---
## append

Append a suffix to the current text.

`|append(suffix, delimiter, trim)`

| **suffix** | The text to append. |
| **delimiter** | An optional delimiter to add between the current text and the suffix, only if the current text is non-empty. |
| **trim** | Optional characters to remove from the end of the current value (e.g. dangling commas). When omitted the trim is set to the same value as the delimiter. |

```
{% set emails = "customer@cerb.example" %}
{{emails|append('vendor@cerb.example', delimiter=', ')}}
```

```
customer@cerb.example, vendor@cerb.example
```

```
{% set emails = null %}
{{emails|append('vendor@cerb.example', delimiter=', ')}}
```

```
vendor@cerb.example
```
