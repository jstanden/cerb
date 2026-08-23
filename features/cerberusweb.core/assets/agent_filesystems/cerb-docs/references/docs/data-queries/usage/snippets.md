---
id: "docs-data-queries-usage-snippets"
title: "Data Queries: Snippet Usage"
url: "https://cerb.ai/docs/data-queries/usage/snippets/"
summary: "This page provides information on data queries related to the historical usage of snippets in Cerb, specifically focusing on how these queries can track snippet usage by workers over time. It outlines the available response formats for these queries, which include a default tabular format suitable for table visualizations and a timeseries format for time series visualizations. The page also includes examples of how to structure these queries, such as using the 'timeseries' format to obtain timestamp-based data."
tags: ["docs"]
---
# usage.snippets

`usage.snippets` data queries return historical usage data for snippets (e.g. uses by worker over time).

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
type:usage.snippets
format:timeseries
```

 
