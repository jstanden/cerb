---
id: "docs-scripting-functions--validateemail"
title: "Scripting Function: validate_email"
url: "https://cerb.ai/docs/scripting/functions/#validateemail"
summary: "Validate an email address format"
tags: ["docs", "docs-scripting"]
---
## validate\_email

Validate an email address:

```
{{validate_email('kina')|json_encode}}
{{validate_email('kina#cerb.example')|json_encode}}
{{validate_email('kina@cerb.example')|json_encode}}
```

```
false
false
true
```
