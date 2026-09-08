---
id: "docs-scripting-filters--hex2bin"
title: "Scripting Filter: hex2bin"
url: "https://cerb.ai/docs/scripting/filters/#hex2bin"
summary: "Convert a hexadecimal string back to its binary string"
tags: ["docs", "docs-scripting"]
---
## hex2bin

Convert a hexadecimal string back to the binary string it represents. This is the inverse of [bin2hex](#bin2hex):

```
{{"43657262"|hex2bin}}
```

```
Cerb
```

The input must be an even number of hexadecimal digits and nothing else. An odd-length value, any non-hex character, and an empty string all return nothing rather than raising an error, so validate the source before relying on the result.
