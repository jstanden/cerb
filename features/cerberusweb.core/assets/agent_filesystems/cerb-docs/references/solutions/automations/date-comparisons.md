---
id: "solutions-automations-date-comparisons"
title: "Date comparisons"
url: "https://cerb.ai/solutions/automations/date-comparisons/"
summary: "This page explains the usage of the `|date('U')` filter in Cerb, which converts human-readable dates to Unix timestamps for easier comparisons. It provides two examples: one showing how to check if a SLA coverage has expired within 2 weeks using an automation rule, and another demonstrating how to return a specific text output based on the expiration status of the SLA coverage."
tags: ["solutions", "solutions-automations"]
---
The [|date](/docs/scripting/filters/#date) filter can convert human-readable dates into Unix timestamps for easier comparisons as a numeric range.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    sla_expiration@date: +2 weeks
  return:
    output@text:
      {% if sla_expiration >= 'now'|date('U') %}
      Your SLA coverage is active.
      {% else %}
      Your SLA coverage has expired.
      {% endif %}
```
- 
```
__return:
  output: |
    Your SLA coverage is active.
```

