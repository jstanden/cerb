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

| **type** | `singular`, `plural` (default), `singular_short`, `plural_short`, `id`, `uri` |

```
{{'cerberusweb.contexts.ticket'|context_name('singular')}}
{{'cerberusweb.contexts.task'|context_name('plural')}}
{{'worker'|context_name('id')}}
```

```
ticket
tasks
cerberusweb.contexts.worker
```

Every form accepts either an alias or a full context string, so `worker` and `cerberusweb.contexts.worker` are interchangeable as input.

Some record types register short names as well. For tickets, `singular_short` returns `convo` and `plural_short` returns `mail`.

Those two don't look like a pair, and that's expected: a record type can register many names, each declaring which forms it can fill, and every form is claimed by the first name that qualifies for it. `mail` is declared as singular, plural, _and_ short, so it takes `plural_short` before a later name can.
