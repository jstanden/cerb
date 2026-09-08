---
id: "docs-scripting-filters--parseurl"
title: "Scripting Filter: parse_url"
url: "https://cerb.ai/docs/scripting/filters/#parseurl"
summary: "Parse a URL string into an object for validation"
tags: ["docs", "docs-scripting"]
---
## parse\_url

Parse a URL string into an object for validation.

The filter takes no arguments and always returns the whole record. Reach a single component from the result – `{{url|parse_url.host}}` – rather than asking for one by name.

```
{% set url = "https://cerb.ai/search?q=oauth2#fragment" %}
{{url|parse_url|json_encode|json_pretty}}
```

```
{
    "scheme": "https",
    "host": "cerb.ai",
    "path": "/search",
    "query": "q=oauth2",
    "fragment": "fragment"
}
```
