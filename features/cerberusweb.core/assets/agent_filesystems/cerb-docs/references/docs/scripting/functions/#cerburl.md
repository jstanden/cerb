---
id: "docs-scripting-functions--cerburl"
title: "Scripting Function: cerb_url"
url: "https://cerb.ai/docs/scripting/functions/#cerburl"
summary: "Retrieve a full URL to a page or resource in Cerb"
tags: ["docs", "docs-scripting"]
---
## cerb\_url

Retrieve a full URL to a page or resource in Cerb.

This automatically adapts to use within Cerb and community portals (e.g. SSL, proxies).

```
{{cerb_url("c=profiles&type=ticket&id=5")}}
```

```
https://cerb.example/profiles/ticket/5
```
