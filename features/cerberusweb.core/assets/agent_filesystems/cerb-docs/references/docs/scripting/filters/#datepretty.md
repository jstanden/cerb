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
{% set timestamp = date("Jan 9 2002 10am", "America/Los_Angeles") %}
{{timestamp|date('U')|date_pretty}}
```

```
18 years ago
```
