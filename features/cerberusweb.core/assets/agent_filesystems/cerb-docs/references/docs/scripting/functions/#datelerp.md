---
id: "docs-scripting-functions--datelerp"
title: "Scripting Function: date_lerp"
url: "https://cerb.ai/docs/scripting/functions/#datelerp"
summary: "Interpolate timestamps between two dates with given unit and step"
tags: ["docs", "docs-scripting"]
---
## date\_lerp

Interpolate the timestamps between two dates with the given `unit` and `step`.

`date_lerp(date_range,unit,step,limit)`

**Arguments:**

| Name | Notes |
| --- | --- |
| **date\_range** | An absolute range like `2023-01-01 to 2023-12-31`, a relative range like `-7 days to now`, or a shortcut like `this month`. |
| **unit** | `minute`, `hour`, `day`, `week`, `month`, `year` |
| **step** | The number of `unit` to increment (e.g. `5`). Default `1`. |
| **limit** | The maximum number of results. Default `10000`. |

**Returns:** An array of Unix timestamps.

```
{{date_lerp('this month',unit='day',step=5)|map((v) => v|date('r'))|json_encode|json_pretty}}
```

```
[
    "Sat, 01 Oct 2022 00:00:00 -0700",
    "Thu, 06 Oct 2022 00:00:00 -0700",
    "Tue, 11 Oct 2022 00:00:00 -0700",
    "Sun, 16 Oct 2022 00:00:00 -0700",
    "Fri, 21 Oct 2022 00:00:00 -0700",
    "Wed, 26 Oct 2022 00:00:00 -0700",
    "Mon, 31 Oct 2022 00:00:00 -0700"
]
```
