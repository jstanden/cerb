---
id: "docs-scripting-filters--bytespretty"
title: "Scripting Filter: bytes_pretty"
url: "https://cerb.ai/docs/scripting/filters/#bytespretty"
summary: "Convert a number into a human readable number of bytes"
tags: ["docs", "docs-scripting"]
---
## bytes\_pretty

Convert a number into a human readable number of bytes:

```
{{"123456789"|bytes_pretty(2)}}
```

```
123.46 MB
```

The optional argument determines the number of digits of precision.
