---
id: "solutions-automations-format-dates-timezones"
title: "Format dates and timezones"
url: "https://cerb.ai/solutions/automations/format-dates-timezones/"
summary: "This page demonstrates how to use the `|date` filter to format dates and work with different timezones. Examples include formatting current time, converting between timezones, and using common date format standards like RFC-2822 and ISO-8601."
tags: ["solutions", "solutions-automations"]
---
## Using |date filter

Here is an example of using the [|date](/docs/scripting/filters/#date) filter to format dates and handle different timezones.

You can use any of the formating options from PHP DateTime::format.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    now: {{'now'|date('F d, Y h:ia T')}}
    timezone: {{'now'|date('F d, Y h:ia T', 'Europe/Berlin')}}
    tomorrow: {{'tomorrow 5pm'|date('D, d F Y H:i T')}}
    two_weeks: {{'+2 weeks 08:00'|date('Y-m-d h:ia T')}}
    rfc2822: {{'now'|date('r')}}
    iso8601: {{'now'|date('c')}}
    unix@int: {{'now'|date('U')}}
```
- 
```
__return:
  now: February 07, 2025 09:09am PST
  timezone: February 07, 2025 06:09pm CET
  tomorrow: Sat, 08 February 2025 17:00 PST
  two_weeks: 2025-02-21 08:00am PST
  rfc2822: Fri, 07 Feb 2025 09:09:43 -0800
  iso8601: "2025-02-07T09:09:43-08:00"
  unix: 1738948183
```

