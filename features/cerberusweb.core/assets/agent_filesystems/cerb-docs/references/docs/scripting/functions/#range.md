---
id: "docs-scripting-functions--range"
title: "Scripting Function: range"
url: "https://cerb.ai/docs/scripting/functions/#range"
summary: "Return an array with values between from and to with optional step"
tags: ["docs", "docs-scripting"]
---
## range

Return an array with values between `from` and `to` (inclusive).

`range(from,to,step)`

```
{{range(5,15)|json_encode}}
{{range(5,15,2)|json_encode}}
```

```
[5,6,7,8,9,10,11,12,13,14,15]
[5,7,9,11,13,15]
```
