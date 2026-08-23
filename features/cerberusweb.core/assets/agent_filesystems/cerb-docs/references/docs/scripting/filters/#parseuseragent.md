---
id: "docs-scripting-filters--parseuseragent"
title: "Scripting Filter: parse_user_agent"
url: "https://cerb.ai/docs/scripting/filters/#parseuseragent"
summary: "Parse a user-agent string into an object"
tags: ["docs", "docs-scripting"]
---
## parse\_user\_agent

Parse a user-agent string into an object for validation.

```
{% set user_agent %}
Mozilla/5.0 (Macintosh; Intel Mac OS X 13_0) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.1 Safari/605.1.15
{% endset %}
{{user_agent|parse_user_agent|json_encode}}
```

```
{
    "platform": "Macintosh",
    "browser": "Safari",
    "version": "16.1"
}
```
