---
id: "docs-scripting-filters--contextname"
title: "Scripting Filter: context_name"
url: "https://cerb.ai/docs/scripting/filters/#contextname"
summary: "Convert a Cerb context ID into a human readable label"
tags: ["docs", "docs-scripting"]
---
## context\_name

Convert a Cerb `context` ID into a human readable label.

`|context_name(type)`

| **type** | `singular`, `plural`, `id`, `uri` |

```
{{'cerberusweb.contexts.ticket'|context_name('singular')}}
{{'cerberusweb.contexts.task'|context_name('plural')}}
{{'worker'|context_name('id')}}
```

```
tickets
task
```
