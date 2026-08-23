---
id: "docs-scripting-filters--strpos"
title: "Scripting Filter: str_pos"
url: "https://cerb.ai/docs/scripting/filters/#strpos"
summary: "Return the position of a substring within a larger text"
tags: ["docs", "docs-scripting"]
---
## str\_pos

Return the position of a substring (needle) within a larger text (haystack). This returns `-1` if the substring is not found.

`|str_pos(needle, offset, ignoreCase)`

| **needle** | The substring to search for. |
| **offset** | The position to start searching from. |
| **ignoreCase** | `true` for case-insensitive matching, `false` for case-sensitive |

```
{% set alphabet %}
ABCDEFGHIJKLMNOPQRSTUVWXYZ
{% endset %}
{{alphabet|str_pos(needle='hi', offset=0, ignoreCase=true)}}
```

```
7
```
