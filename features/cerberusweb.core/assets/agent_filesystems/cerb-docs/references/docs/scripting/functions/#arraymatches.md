---
id: "docs-scripting-functions--arraymatches"
title: "Scripting Function: array_matches"
url: "https://cerb.ai/docs/scripting/functions/#arraymatches"
summary: "Compare an array of values to an array of patterns"
tags: ["docs", "docs-scripting"]
---
## array\_matches

Compares an array of values to an array of patterns.

```
{% set recipients = ['support@cerb.example','sales@cerb.example'] %}
{% set patterns = ['sales@*'] %}
{% set results = array_matches(recipients, patterns) %}
Matches: {{results|join(', ')}}
```

```
Matches: sales@cerb.example
```
