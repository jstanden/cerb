---
id: "docs-scripting-functions--cerbcalendargetrelativedate"
title: "Scripting Function: cerb_calendar_get_relative_date"
url: "https://cerb.ai/docs/scripting/functions/#cerbcalendargetrelativedate"
summary: "Calculate a future timestamp using calendar availability"
tags: ["docs", "docs-scripting"]
---
## cerb\_calendar\_get\_relative\_date

Calculate a future timestamp using calendar availability. For instance, this can be used for SLAs to generate a due date like "+4 business hours".

`cerb_calendar_get_relative_date(calendar,rel_date,now)`

| **calendar** | The ID of the [calendar](/docs/records/types/calendar/) to use for determining availability. |
| **date\_rel** | The time increment (e.g. "+2 hours"). |
| **now** | An optional starting date/time. |

```
Now: {{"now"|date('r')}}
Due: {{cerb_calendar_get_relative_date(123,'+2 hours')|date('r')}}
```

```
Now: Fri, 18 Oct 2024 20:02:18 -0700
Due: Mon, 21 Oct 2024 09:00:00 -0700
```
