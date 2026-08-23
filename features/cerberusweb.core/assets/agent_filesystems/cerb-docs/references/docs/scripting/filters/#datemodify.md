---
id: "docs-scripting-filters--datemodify"
title: "Scripting Filter: date_modify"
url: "https://cerb.ai/docs/scripting/filters/#datemodify"
summary: "Manipulate a date by adding or subtracting time"
tags: ["docs", "docs-scripting"]
---
## date\_modify

If you need to manipulate a date, create a date object with the [date](/docs/scripting/functions/#date) function and use the **date\_modify** filter:

```
{% set format = 'D, d M Y T' %}
{% set timestamp = date('now') %}
Now: {{timestamp|date(format)}}
+2 days: {{timestamp|date_modify('+2 days')|date(format)}}
```

```
Now: Tue, 12 Dec 2017 PST
+2 days: Thu, 14 Dec 2017 PST
```
