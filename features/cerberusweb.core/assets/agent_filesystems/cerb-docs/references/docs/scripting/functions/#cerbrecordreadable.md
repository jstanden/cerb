---
id: "docs-scripting-functions--cerbrecordreadable"
title: "Scripting Function: cerb_record_readable"
url: "https://cerb.ai/docs/scripting/functions/#cerbrecordreadable"
summary: "Check if an actor has read access to a given record"
tags: ["docs", "docs-scripting"]
---
## cerb\_record\_readable

Returns a boolean if the given actor has read access to the given record. If no actor is provided then the current worker is assumed. This allows bots and widgets to adapt based on record permissions. For instance, an HTML widget on a profile dashboard could only show a button to workers who can modify the record.

```
{% if cerb_record_readable('ticket', 123, 'worker', 1) %}
Worker #1 can read ticket #123.
{% endif %}
```

```
Worker #1 can read ticket #123.
```
