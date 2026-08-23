---
id: "docs-scripting-functions--cerbavatarimage"
title: "Scripting Function: cerb_avatar_image"
url: "https://cerb.ai/docs/scripting/functions/#cerbavatarimage"
summary: "Retrieve the avatar image HTML for a given record type and ID"
tags: ["docs", "docs-scripting"]
---
## cerb\_avatar\_image

Retrieve the avatar image for a given record type and ID.

`cerb_avatar_image(record_type, id, updated)`

```
{{cerb_avatar_image('worker','1','now'|date('U'))}}
```

```
<img src="https:/cerb.example/avatars/worker/1?v=1513212603" style="height:16px;width:16px;border-radius:16px;vertical-align:middle;">
```
