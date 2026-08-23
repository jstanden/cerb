---
id: "docs-scripting-functions--cerbhaspriv"
title: "Scripting Function: cerb_has_priv"
url: "https://cerb.ai/docs/scripting/functions/#cerbhaspriv"
summary: "Check if an actor has a given privilege among their roles"
tags: ["docs", "docs-scripting"]
---
## cerb\_has\_priv

Returns a boolean depending on whether the given actor has the given privilege among their roles. If no actor is given, the current worker is assumed. This allows bot functionality, snippets, and widgets, to adapt based on worker permissions. This is particularly useful in HTML-based profile widgets.

```
{% if cerb_has_priv('contexts.cerberusweb.context.ticket.create', 'worker', 1) %}
Worker #1 has permission to create tickets.
{% endif %}
```

```
Worker #1 has permission to create tickets.
```
