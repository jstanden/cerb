---
id: "docs-scripting-filters--hashhmac"
title: "Scripting Filter: hash_hmac"
url: "https://cerb.ai/docs/scripting/filters/#hashhmac"
summary: "Generate a hash-based message authentication code using a secret key"
tags: ["docs", "docs-scripting"]
---
## hash\_hmac

Generate a hash-based message authentication code (HMAC[1](#fn:hmac)) using a secret key.

`|hash_hmac(secret_key, algorithm, binary)`

| **secret\_key** | The secret key used to generate the HMAC digest |
| **algorithm** | The algorithm of the returned hash (e.g. `sha256`, `sha512`). See: hash\_hmac\_algos |
| **binary** | Return raw binary data when `true`, otherwise lowercase hex (default) |

For instance, you can use this to sign parameters in a survey URL to verify that the recipient didn't modify them.

```
{% set data = {'email': 'kina@cerb.example', 'survey_id': 123} %}
{{data|json_encode|hash_hmac("THIS IS SECRET","sha256")}}
```

```
5514f8aed3b39159d455f9a8f74b5d23d4f96391fa4a27d1bea6f940cb7d410f
```

Provide your own value for THIS IS SECRET. You an store it in the bot configuration.
