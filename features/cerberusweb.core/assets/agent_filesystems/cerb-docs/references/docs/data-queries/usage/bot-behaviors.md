---
id: "docs-data-queries-usage-bot-behaviors"
title: "Data Queries: Bot Behavior Usage"
url: "https://cerb.ai/docs/data-queries/usage/bot-behaviors/"
summary: "This page provides information on data queries related to the historical usage of bot behaviors in Cerb, including metrics such as uses, average runtime, and total runtime over time. It outlines the available response formats for these queries, which include a default tabular format for table visualizations and a timeseries format for time series visualizations. The page also includes an example of how to structure a query using the timeseries format."
tags: ["docs"]
---
# usage.behaviors

`usage.behaviors` data queries return historical usage data for bot behaviors (e.g. uses, avg. runtime, and total runtime over time).

- [Inputs](#inputs)
- [Response Formats](#response-formats)
- [Examples](#examples)

# Inputs

(none)

# Response Formats

- **table** (default) returns tabular data (columns and rows) suitable for the "Chart: Table" visualization widget.

- **timeseries** returns series-based data suitable for the "Chart: Time Series" visualization widget (the x-axis values are timestamps).

# Examples

```
type:usage.behaviors
format:timeseries
```

 
