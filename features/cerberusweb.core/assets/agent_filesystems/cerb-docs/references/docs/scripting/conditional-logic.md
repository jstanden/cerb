---
id: "docs-scripting-conditional-logic"
title: "Scripting Reference: Conditional Logic"
url: "https://cerb.ai/docs/scripting/conditional-logic/"
summary: "This page provides a scripting reference for implementing conditional logic in Cerb. It explains how to use expressions to display different content based on conditions, such as checking if a service level agreement (SLA) is active or expired. The example given demonstrates setting a date for SLA expiration and using an if-else statement to determine and display the appropriate message. The page also mentions the use of operators in handling dates within these expressions."
tags: ["docs", "docs-scripting"]
---
Conditional logic can display different content based on the result of any number of **expressions**:

```
{% set sla_expiration = '+2 weeks'|date('U') %}
{% if sla_expiration >= 'now'|date('U') %}
Your SLA coverage is active.
{% else %}
Your SLA coverage has expired.
{% endif %}
```

```
Your SLA coverage is active.
```

[\< Dates](/docs/scripting/dates/)

[Operators \>](/docs/scripting/operators/)

