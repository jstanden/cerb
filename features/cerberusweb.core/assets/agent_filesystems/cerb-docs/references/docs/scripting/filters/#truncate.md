---
id: "docs-scripting-filters--truncate"
title: "Scripting Filter: truncate"
url: "https://cerb.ai/docs/scripting/filters/#truncate"
summary: "Ensure that a string is no longer than the given limit"
tags: ["docs", "docs-scripting"]
---
## truncate

Ensure that a string is no longer than the given limit. The limit includes the separator, so the result is never longer than `limit` characters.

`|truncate(limit, separator)`

| **limit** | The maximum length of the result, counting the separator. |
| **separator** | The text appended to a truncated string. This defaults to `...` |

```
{% set str = "This string is longer than we'd prefer" %}
{{str|truncate(11)}}
```

```
This str...
```

This filter takes (limit, separator). Twig's own **truncate** takes (length, preserve, separator), so a snippet copied from Twig's documentation passes its second argument as the _separator_, and no error is reported:

{{"The quick brown fox jumps over the lazy dog"|truncate(20, true)}} returns The quick brown fox1, using true as the separator text. Without it, truncate(20) returns The quick brown f...
