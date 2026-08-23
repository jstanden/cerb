---
id: "docs-scripting-filters--base64decode"
title: "Scripting Filter: base64_decode"
url: "https://cerb.ai/docs/scripting/filters/#base64decode"
summary: "Decode a base64-encoded string"
tags: ["docs", "docs-scripting"]
---
## base64\_decode

Decode a base64-encoded string:

```
{% set b64 = "VGhpcyB3YXMgYmFzZTY0LWVuY29kZWQ=" %}
{{b64|base64_decode}}
```

```
This was base64-encoded
```
