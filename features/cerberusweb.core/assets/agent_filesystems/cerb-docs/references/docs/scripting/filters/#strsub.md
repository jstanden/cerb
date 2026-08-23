---
id: "docs-scripting-filters--strsub"
title: "Scripting Filter: str_sub"
url: "https://cerb.ai/docs/scripting/filters/#strsub"
summary: "Extract a substring using starting and ending positions"
tags: ["docs", "docs-scripting"]
---
## str\_sub

Extract a substring from a larger string using starting and ending positions. This is an alternative to [|slice(from,length)](/docs/scripting/filters/#slice).

`|str_sub(from, to)`

| **from** | The position to start extracting a substring from (inclusive). |
| **to** | The position to end extraction at (exclusive). |

```
{% set alphabet %}
ABCDEFGHIJKLMNOPQRSTUVWXYZ
{% endset %}
{{alphabet|str_sub(7,9)}}
```

```
HI
```
