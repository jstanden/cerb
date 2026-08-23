---
id: "docs-scripting-filters--qpencode"
title: "Scripting Filter: qp_encode"
url: "https://cerb.ai/docs/scripting/filters/#qpencode"
summary: "Encode a string in quoted-printable format"
tags: ["docs", "docs-scripting"]
---
## qp\_encode

Encode a string in quoted-printable format. For instance, creating tickets with emoji using [email.parse:](/docs/automations/commands/email.parse/).

```
{% set message %}
Hello and welcome to our new service! 😀

We're delighted 🎉 to have you as a member of our community.
This is a sample email with emojis 🚀 and quoted-printable encoding.

Have a great day! 🌈

Best regards,
The Team 👋
{% endset %}

{{message|qp_encode}}
```

```
Hello and welcome to our new service! =F0=9F=98=80

We're delighted =F0=9F=8E=89 to have you as a member of our community.
This is a sample email with emojis =F0=9F=9A=80 and quoted-printable encodi=
ng.

Have a great day! =F0=9F=8C=88

Best regards,
The Team =F0=9F=91=8B
```
