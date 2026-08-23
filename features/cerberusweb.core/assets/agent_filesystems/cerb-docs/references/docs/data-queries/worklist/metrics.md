---
id: "docs-data-queries-worklist-metrics"
title: "Data Queries: Worklist Metrics"
url: "https://cerb.ai/docs/data-queries/worklist/metrics/"
summary: "This page provides detailed information on using `worklist.metrics` queries in Cerb to compute metrics from worklist data, such as calculating the average ticket first response time over a specified period. It outlines the necessary inputs for these queries, including parameters like label, record type, field, function, metric, and query filters. The page also describes the available response formats, such as pie charts and tables, and provides examples of how to calculate metrics like average response times and multiple functions in a single query. This resource is essential for users looking to analyze and visualize worklist data effectively in Cerb."
tags: ["docs"]
---
# worklist.metrics

`worklist.metrics` queries return computed metrics based on worklist data (e.g. 'average ticket first response time over the past year').

- [Inputs](#inputs)
- [Response Formats](#response-formats)
- [Examples](#examples)
  - [Calculating the average first response time from a worklist of tickets](#calculating-the-average-first-response-time-from-a-worklist-of-tickets)
  - [Calculating multiple functions in a single query](#calculating-multiple-functions-in-a-single-query)

# Inputs

Each `values.*` series should provide:

- `label:` (human-friendly series name for visualizations)
- `of:` (record type)
- `field:` (record field using quick search keys)
- `function:` (count,min,max,average,sum)
- `metric:` (an equation to apply to each value)
- `query:` (the query to filter the results for this series)
- `query.required:` (the required query to filter the results for this series)

Optionally, multiple functions can be specified for a series, like `functions:[sum,average]`, and multiple series will be generated automatically using the same record type, field, and query.

# Response Formats

- **pie** returns data for use in pie and donut charts.

- **table** (default) returns tabular output, suitable for display with the 'Chart: Table' visualization widget. Multiple metrics are returned as rows.

# Examples

## Calculating the average first response time from a worklist of tickets

```
type:worklist.metrics 
values.total:(
  of:ticket
  field:response.first 
  function:average 
  query:(
    created:"-1 year"
    response.first:>0
  )
)
format:table
```

 

## Calculating multiple functions in a single query

```
type:worklist.metrics
values.response_time:(
  of:message 
  functions:[average,min,max,sum,count] 
  field:responseTime 
  query:(
    worker.id:{{record_id}} 
    created:"-1 month" 
    isOutgoing:y 
    isBroadcast:n 
    responseTime:>0
  )
)
format:table
```

 
