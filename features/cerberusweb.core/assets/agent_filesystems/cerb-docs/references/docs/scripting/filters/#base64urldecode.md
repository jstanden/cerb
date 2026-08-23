---
id: "docs-scripting-filters--base64urldecode"
title: "Scripting Filter: base64url_decode"
url: "https://cerb.ai/docs/scripting/filters/#base64urldecode"
summary: "Decode a base64url-encoded string"
tags: ["docs", "docs-scripting"]
---
## base64url\_decode

Decode a base64url-encoded string:

```
{% set b64 = "VGhpcyB3YXMgYmFzZTY0dXJsLWVuY29kZWQ" %}
{{b64|base64url_decode}}
```

```
This was base64url-encoded
```
