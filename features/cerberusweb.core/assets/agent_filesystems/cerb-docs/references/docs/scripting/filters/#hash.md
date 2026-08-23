---
id: "docs-scripting-filters--hash"
title: "Scripting Filter: hash"
url: "https://cerb.ai/docs/scripting/filters/#hash"
summary: "Generate a one-way hash using various algorithms"
tags: ["docs", "docs-scripting"]
---
## hash

Generate a one-way hash.

`|hash(algorithm, binary=false)`

| **algorithm** | The algorithm of the returned hash (e.g. `sha256`, `sha512`). |
| **binary** | Return raw binary data when `true` |

The **algorithm** can be one of: `crc32`, `md5`, `murmur3a`, `murmur3c`, `murmur3f`, `sha1`, `sha256`, `sha512/224`, `sha512/256`, `sha512`, `sha3-224`, `sha3-256`, `sha3-384`, `sha3-512`, `whirlpool`, `xxh32`, `xxh64`, `xxh3`, `xxh128`

```
{% set text = 'This string will be hashed' %}
SHA-512: {{text|hash('sha512')}}
Murmur3a: {{text|hash('murmur3a')}}
xxh128: {{text|hash('xxh128')}}
```

```
SHA-512: 8b0a3e297c0447e43e20e966d1cbf4a20163c9ddebb95e1d4ba44e2542c1915597375c1a39dfce4f5786d1d187a4ce5f780817d34632fcbc571694533b3961f0
Murmur3a: 4a9df623
xxh128: 0da37dd25c7ee8945e2947cd89e86549
```
