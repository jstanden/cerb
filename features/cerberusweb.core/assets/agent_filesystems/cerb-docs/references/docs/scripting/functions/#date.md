---
id: "docs-scripting-functions--date"
title: "Scripting Function: date"
url: "https://cerb.ai/docs/scripting/functions/#date"
summary: "Create a date object for use with date manipulation filters"
tags: ["docs", "docs-scripting"]
---
## date

Create a date object for use with the [date\_modify](/docs/scripting/filters/#date_modify) filter.

```
{% set d = date('1-Jan-2018 10:00am') %}
{{d|date_modify('+2 hours')|date('F d, Y g:ia')}}
```

```
January 01, 2018 12:00pm
```
