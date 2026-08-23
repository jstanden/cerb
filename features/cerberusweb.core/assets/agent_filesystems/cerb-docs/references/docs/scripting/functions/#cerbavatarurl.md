---
id: "docs-scripting-functions--cerbavatarurl"
title: "Scripting Function: cerb_avatar_url"
url: "https://cerb.ai/docs/scripting/functions/#cerbavatarurl"
summary: "Retrieve the avatar image URL for a given record type and ID"
tags: ["docs", "docs-scripting"]
---
## cerb\_avatar\_url

Retrieve the avatar image URL for a given record type and ID.

`cerb_avatar_url(record_type, id, updated)`

```
{{cerb_avatar_url('worker','1','now'|date('U'))}}
```

```
https://cerb.example/avatars/worker/1?v=1513212702
```
