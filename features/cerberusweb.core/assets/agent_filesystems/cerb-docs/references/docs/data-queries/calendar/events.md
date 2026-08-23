---
id: "docs-data-queries-calendar-events"
title: "Data Queries: Calendar Events"
url: "https://cerb.ai/docs/data-queries/calendar/events/"
summary: "This page provides detailed information on how to perform data queries for calendar events using the `calendar.events` function. It outlines the necessary inputs required for the queries, such as specifying which calendar records to include, the date range for the events, and any additional keys to expand in the returned event dictionaries. The page also describes the response format, which is primarily in a table-based dictionary format suitable for integration with sheets and APIs. An example query is provided to illustrate how to retrieve events from specified calendars within a given date range, demonstrating the practical application of the function."
tags: ["docs"]
---
# calendar.events

`calendar.events` queries return events and synthesized recurring events for the given calendars grouped into days.

- [Inputs](#inputs)
- [Response Formats](#response-formats)
- [Examples](#examples)

# Inputs

- `calendar:` (a [search query](/docs/search/) of [calendar](/docs/records/types/calendar/#search-query-fields) records to include)
- `from:` (return events from this starting datetime)
- `to:` (return events until this ending datetime)
- `expand:` (the keys to expand on the returned event dictionaries)

# Response Formats

The results can be returned in these formats:

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

# Examples

```
type:calendar.events
calendar:(name:["U.S. Holidays","Office Hours"])
from:"this week Monday 00:00:00"
to:"this week Sunday 23:59:59"
expand:[calendar_owner_]
format:dictionaries
```
