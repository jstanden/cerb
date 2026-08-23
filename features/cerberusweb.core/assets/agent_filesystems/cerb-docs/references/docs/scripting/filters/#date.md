---
id: "docs-scripting-filters--date"
title: "Scripting Filter: date"
url: "https://cerb.ai/docs/scripting/filters/#date"
summary: "Format a string or variable as a date with custom formatting"
tags: ["docs", "docs-scripting"]
---
## date

Use the **date** filter to format a [string](/docs/scripting/strings/) or [variable](/docs/scripting/variables/) as a date:

```
{{'now'|date('F d, Y h:ia T')}}
{{'tomorrow 5pm'|date('D, d F Y H:i T')}}
{{'+2 weeks 08:00'|date('Y-m-d h:ia T')}}
```

```
December 12, 2017 11:50am PST
Wed, 13 December 2017 17:00 PST
2017-12-26 08:00am PST
```

You can use any of the formatting options from PHP DateTime::format.

The second parameter to the **date** filter can specify a timezone to use:

```
{% set ts_now = 'now' -%}

Bangalore: {{ts_now|date(time_format, 'Asia/Calcutta')}}
Berlin: {{ts_now|date(time_format, 'Europe/Berlin')}}
New York: {{ts_now|date(time_format, 'America/New_York')}}
```

```
Bangalore: December 13, 2017 01:27
Berlin: December 12, 2017 20:57
New York: December 12, 2017 14:57
```

You can get a Unix timestamp (seconds since 1-Jan-1970 00:00:00 UTC) from a date value with the `|date('U')` filter:

```
It has been {{'now'|date('U')}} seconds since {{'0'|date(null, 'UTC')}}
```

```
It has been 1513108417 seconds since January 1, 1970 00:00
```
