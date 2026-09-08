---
id: "docs-scripting-filters--datepretty"
title: "Scripting Filter: date_pretty"
url: "https://cerb.ai/docs/scripting/filters/#datepretty"
summary: "Convert a Unix timestamp into a human-readable, relative date"
tags: ["docs", "docs-scripting"]
---
## date\_pretty

Convert a Unix timestamp into a human-readable, relative date:

```
{% set an_hour_ago = date('now')|date('U') - 3600 %}
{% set three_days_ago = date('now')|date('U') - (86400 * 3) %}
{% set in_two_hours = date('now')|date('U') + 7200 %}
{{an_hour_ago|date_pretty}}
{{three_days_ago|date_pretty}}
{{in_two_hours|date_pretty}}
```

```
1 hour ago
3 days ago
2 hours
```

A date in the future has no suffix, as in the `2 hours` above.

This filter takes a Unix timestamp, not a date object. Piping a [date()](/docs/scripting/functions/#date) object straight in returns an empty string and reports no error, so convert it first with |date('U').
