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

Relative English date strings like these are resolved when the script runs, so their output isn't shown here. The examples below use a fixed date instead.

You can use any of the formatting options from PHP DateTime::format.

The second parameter to the **date** filter is the timezone the result is displayed in. It does not change the timezone a date string is _parsed_ in, so include the zone in the string itself when it matters:

```
{% set time_format = 'F j, Y H:i' %}
{% set ts = date('2017-12-12 14:57 America/New_York') -%}

Bangalore: {{ts|date(time_format, 'Asia/Kolkata')}}
Berlin: {{ts|date(time_format, 'Europe/Berlin')}}
New York: {{ts|date(time_format, 'America/New_York')}}
```

```
Bangalore: December 13, 2017 01:27
Berlin: December 12, 2017 20:57
New York: December 12, 2017 14:57
```

You can get a Unix timestamp (seconds since 1-Jan-1970 00:00:00 UTC) from a date value with the `|date('U')` filter:

```
{{"2017-12-12 14:57 America/New_York"|date('U')}}
{{"1513108620"|date('F j, Y H:i', 'America/New_York')}}
```

```
1513108620
December 12, 2017 14:57
```
