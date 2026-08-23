---
id: "docs-scripting-functions--cerbrecordwriteable"
title: "Scripting Function: cerb_record_writeable"
url: "https://cerb.ai/docs/scripting/functions/#cerbrecordwriteable"
summary: "Check if an actor has write access to a given record"
tags: ["docs", "docs-scripting"]
---
## cerb\_record\_writeable

Returns a boolean if the given actor has write access to the given record. If no actor is provided then the current worker is assumed. This allows bots and widgets to adapt based on record permissions. For instance, an HTML widget on a profile dashboard could only show a button to workers who can modify the record.

```
{% if cerb_record_writeable('ticket', 123, 'worker', 1) %}
Worker #1 can modify ticket #123.
{% endif %}
```

```
Worker #1 can modify ticket #123.
```
