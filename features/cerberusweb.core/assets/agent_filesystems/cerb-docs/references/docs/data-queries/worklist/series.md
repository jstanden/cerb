---
id: "docs-data-queries-worklist-series"
title: "Data Queries: Worklist Series"
url: "https://cerb.ai/docs/data-queries/worklist/series/"
summary: "This page provides detailed information on using `worklist.series` data queries in Cerb to generate series-based data from worklists, such as tracking tickets created by month and status. It outlines the necessary inputs for constructing a query, including specifying labels, record types, x and y-axis fields, metrics, and functions like count, min, max, average, and sum. The page also explains how to filter results using queries and adjust the number of data points returned. Additionally, it covers timezone settings for date labels and describes various response formats available for visualizing the data, such as dictionaries, pie charts, tables, and time series. An example query is provided to illustrate how to return series data from a worklist."
tags: ["docs"]
---
# worklist.series

`worklist.series` data queries return series-based data from any worklist (e.g. tickets created by month by status). Data from multiple series of different record types can be returned in a single query.

- [Inputs](#inputs)
- [timezone:](#timezone)
- [Response Formats](#response-formats)
- [Examples](#examples)
  - [Return series data from a worklist](#return-series-data-from-a-worklist)

# Inputs

Each `series.*` should provide:

- `label:` (human-friendly series name for visualizations)
- `of:` (record type)
- `x:` (record field for the x-axis using quick search keys)
- `y:` (record field for the y-axis using quick search keys)
- `y.metric:` (an equation to apply to each y-axis value)
- `function:` (count,min,max,average,sum on `y:` field)
- `query:` (the query to filter the results for this series)
- `query.required:` (the required query to filter the results for this series)

By default you'll receive 10 data points per series. You can add a `limit:<number>` to the `query:` to change this.

# timezone:

The `timezone:` key generates date labels in the given timezone location for bins like `by:[created@day]`.

An option like `timezone:America/Los_Angeles` uses the offset UTC-7 or UTC-8 depending on Daylight Saving Time.

If omitted, this defaults to the timezone of the current worker or the server.

# Response Formats

- **dictionaries** returns data for use in sheets.

- **pie** returns data for use in pie and donut charts.

- **table** returns tabular output, suitable for display with the 'Chart: Table' visualization widget.

- **timeseries** (default) returns time-based series data, suitable for display with the 'Chart: Time Series' visualization widget.

# Examples

## Return series data from a worklist

```
type:worklist.series 
series.open_tickets:(
  of:tickets 
  x:created@month 
  y:id 
  function:count 
  query:(status:o limit:24)
) 
series.closed_tickets:(
  of:tickets 
  x:created@month 
  y:id 
  function:count 
  query:(status:c limit:24)
)
format:timeseries
```

 
