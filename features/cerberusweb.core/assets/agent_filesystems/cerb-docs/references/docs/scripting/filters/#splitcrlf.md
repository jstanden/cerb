---
id: "docs-scripting-filters--splitcrlf"
title: "Scripting Filter: split_crlf"
url: "https://cerb.ai/docs/scripting/filters/#splitcrlf"
summary: "Split a string on carriage return and linefeed delimiters"
tags: ["docs", "docs-scripting"]
---
## split\_crlf

Split a string on any combination of carriage return (`\r`) and linefeed (`\n`) delimiters.

`|split_crlf(keep_blanks=false,trim_lines=true)`

| **keep\_blanks** | Remove lines that are comprised of only whitespace. |
| **trim\_lines** | Remove whitespace before and after each line. |

```
{% set rainbow = "red
orange
yellow
green
blue
indigo
violet" %}
{{rainbow|split_crlf|json_encode}}
```

```
["red","orange","yellow","green","blue","indigo","violet"]
```
