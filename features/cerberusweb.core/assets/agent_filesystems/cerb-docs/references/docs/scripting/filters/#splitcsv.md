---
id: "docs-scripting-filters--splitcsv"
title: "Scripting Filter: split_csv"
url: "https://cerb.ai/docs/scripting/filters/#splitcsv"
summary: "Split a string on comma delimiters with whitespace handling"
tags: ["docs", "docs-scripting"]
---
## split\_csv

Split a string on comma delimiters. This automatically handles whitespace padding.

```
{% set coins = "BTC, ETH ,LTC" %}
{{coins|split_csv|json_encode}}
```

```
["BTC","ETH","LTC"]
```
