---
id: "docs-scripting-filters--parseemails"
title: "Scripting Filter: parse_emails"
url: "https://cerb.ai/docs/scripting/filters/#parseemails"
summary: "Parse a delimited string of email addresses into an object"
tags: ["docs", "docs-scripting"]
---
## parse\_emails

Parse a delimited string of email addresses into an object. This also assists with email validation.

```
{% set emails = "kina@cerb.example, milo@cerb.example, karl" %}
{{emails|parse_emails|json_encode|json_pretty}}
```

```
{
    "kina@cerb.example": {
        "full_email": "kina@cerb.example",
        "email": "kina@cerb.example",
        "mailbox": "kina",
        "host": "cerb.example",
        "personal": null
    },
    "milo@cerb.example": {
        "full_email": "milo@cerb.example",
        "email": "milo@cerb.example",
        "mailbox": "milo",
        "host": "cerb.example",
        "personal": null
    },
    "karl@localhost": {
        "full_email": "karl@localhost",
        "email": "karl@localhost",
        "mailbox": "karl",
        "host": "localhost",
        "personal": null
    }
}
```
