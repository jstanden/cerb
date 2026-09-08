---
id: "docs-scripting-filters--bin2hex"
title: "Scripting Filter: bin2hex"
url: "https://cerb.ai/docs/scripting/filters/#bin2hex"
summary: "Convert a binary string to its hexadecimal representation"
tags: ["docs", "docs-scripting"]
---
## bin2hex

Convert a binary string to its hexadecimal representation:

```
{{"Cerb"|bin2hex}}
```

```
43657262
```

Every byte becomes exactly two lowercase hex digits, so the result is twice as long as the input and safe to slice at any even offset. That's what makes it useful on decoded binary – the bytes of a message header, a hash digest, or a packed identifier – where the raw string would be unprintable and slicing it by character could split a multi-byte sequence.

```
{% set bytes = "AAECf/8="|base64_decode %}
{{bytes|bin2hex}}
```

```
0001027fff
```

Anything that isn't a string returns nothing at all. Use [hex2bin](#hex2bin) to convert back.
