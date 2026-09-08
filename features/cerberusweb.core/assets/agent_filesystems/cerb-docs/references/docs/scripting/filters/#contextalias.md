---
id: "docs-scripting-filters--contextalias"
title: "Scripting Filter: context_alias"
url: "https://cerb.ai/docs/scripting/filters/#contextalias"
summary: "Convert a Cerb context ID into its URI alias"
tags: ["docs", "docs-scripting"]
---
## context\_alias

Convert a Cerb `context` ID into its URI alias.

`|context_alias`

```
{{'cerberusweb.contexts.ticket'|context_alias}}
```

```
ticket
```

This is [context\_name](#context_name) with its `type` pinned to `uri`. `{{x|context_alias}}` and `{{x|context_name('uri')}}` are the same call.
