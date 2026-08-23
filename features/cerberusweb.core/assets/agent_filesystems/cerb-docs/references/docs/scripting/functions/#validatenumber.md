---
id: "docs-scripting-functions--validatenumber"
title: "Scripting Function: validate_number"
url: "https://cerb.ai/docs/scripting/functions/#validatenumber"
summary: "Validate if a value is a valid number"
tags: ["docs", "docs-scripting"]
---
## validate\_number

Validate a number:

```
{{validate_number('abcde')|json_encode}}
{{validate_number('20.f')|json_encode}}
{{validate_number(10)|json_encode}}
{{validate_number('123.45')|json_encode}}
```

```
false
false
true
true
```
