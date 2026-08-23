---
id: "docs-scripting-filters--parsecsv"
title: "Scripting Filter: parse_csv"
url: "https://cerb.ai/docs/scripting/filters/#parsecsv"
summary: "Parse a document with rows of comma-separated columns"
tags: ["docs", "docs-scripting"]
---
## parse\_csv

Parse a document with rows of comma-separated columns. Returns an array of rows with elements for columns.

`parse_csv(separator=',',enclosure='"',escape='\\')`

| **separator** | An optional character to separate fields by. Defaults to comma (`,`). |
| **enclosure** | An optional character to enclose fields. Defaults to double quote (`"`). The enclosure field can be used inside a field by doubling it (as an alternative to escaping). |
| **escape** | An optional character to escape special characters (e.g. `\n`). This defaults to backslash (`\`), and escaping can be disabled with an empty string. |

```
{% set text %}
"Person Name",Email,Organization
"Kina Halpue",kina@cerb.example,Cerb
"Claire Bertin",c.bertin@baston.example,"Baston Defence"
{% endset %}
{{text|parse_csv|json_encode|json_pretty}}
```

```
[
    [
        "Person Name",
        "Email",
        "Organization"
    ],
    [
        "Kina Halpue",
        "kina@cerb.example",
        "Cerb"
    ],
    [
        "Claire Bertin",
        "c.bertin@baston.example",
        "Baston Defence"
    ]
]
```
