---
id: "docs-scripting-functions--regexpmatchall"
title: "Scripting Function: regexp_match_all"
url: "https://cerb.ai/docs/scripting/functions/#regexpmatchall"
summary: "Find all matches of a regular expression pattern in a string"
tags: ["docs", "docs-scripting"]
---
## regexp\_match\_all

`regexp_match_all(pattern, string, group)`

```
{% set headers = 
"X-Mailer: Cerb
From: customer@cerb.example
To: support@cerb.example
"
%}
{% set results = regexp_match_all("#^(.*?): (.*?)$#m", headers) %}
{{results|json_encode|json_pretty}}
```

```
[
  [
    "X-Mailer: Cerb",
    "From: customer@cerb.example",
    "To: support@cerb.example"
  ],
  [
    "X-Mailer",
    "From",
    "To"
  ],
  [
    "Cerb",
    "customer@cerb.example",
    "support@cerb.example"
  ]
]
```
