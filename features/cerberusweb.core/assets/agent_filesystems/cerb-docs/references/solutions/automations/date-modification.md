---
id: "solutions-automations-date-modification"
title: "Date modification"
url: "https://cerb.ai/solutions/automations/date-modification/"
summary: "This page demonstrates how to use the `|date_modify` filter to perform date arithmetic. It shows how to add or subtract various time units from dates and format the results."
tags: ["solutions", "solutions-automations"]
---
## Using |date\_modify filter

Here is an example of using the [|date\_modify](/docs/scripting/filters/#date_modify) filter to add or subtract units of time from dates.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@text:
      {% set format = 'D, d M Y T' %}
      {% set timestamp = date('2025-06-15') %}
      At: {{timestamp|date(format)}}
      +2 days: {{timestamp|date_modify('+2 days')|date(format)}}
      -1 week, 3 days: {{timestamp|date_modify('-1 week, -3 days')|date(format)}}
```
- 
```
__return:
  output: |-
    At: Sun, 15 Jun 2025 PDT
    +2 days: Tue, 17 Jun 2025 PDT
    -1 week, 3 days: Thu, 05 Jun 2025 PDT
```

