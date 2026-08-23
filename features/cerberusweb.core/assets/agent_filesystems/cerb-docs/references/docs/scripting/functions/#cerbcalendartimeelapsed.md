---
id: "docs-scripting-functions--cerbcalendartimeelapsed"
title: "Scripting Function: cerb_calendar_time_elapsed"
url: "https://cerb.ai/docs/scripting/functions/#cerbcalendartimeelapsed"
summary: "Calculate time elapsed between two dates using calendar availability"
tags: ["docs", "docs-scripting"]
---
## cerb\_calendar\_time\_elapsed

Calculate the time elapsed (in seconds) between two dates using calendar availability.

`cerb_calendar_time_elapsed(calendar,date_from,date_to)`

| **calendar** | The ID of the [calendar](/docs/records/types/calendar/) to use for determining availability. |
| **date\_from** | The starting date/time. |
| **date\_to** | The ending date/time. |

```
{{cerb_calendar_time_elapsed(123,'last Friday 5pm','now')|secs_pretty}}
```

```
18 hours, 13 mins
```
