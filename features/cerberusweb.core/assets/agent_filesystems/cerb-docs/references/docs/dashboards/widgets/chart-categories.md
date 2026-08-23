---
id: "docs-dashboards-widgets-chart-categories"
title: "Chart: Categories - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/chart-categories/"
summary: "The Chart Categories dashboard widget renders a bar chart from a data query, with one bar per category. It's well-suited to comparisons like tickets per group, opportunities per stage, or messages per sender."
tags: ["docs"]
---
The **Chart: Categories** widget displays a bar chart from a [data query](/docs/data-queries/), with one bar per distinct category. It's a good fit for comparing record counts (or aggregates) across groups, buckets, statuses, owners, or any other categorical dimension.

 

# Configuration

| Field | Description |
| --- | --- |
| Run this data query | A [data query](/docs/data-queries/) that returns categorical data. The query is responsible for producing the x-axis categories and y-axis values. |
| Cache | Number of seconds to cache the query results before refetching. Leave blank to run the query on every render. |
| Format x-axis values as | Optional value formatter: text, number, time elapsed (minutes), or time elapsed (seconds). |
| Format y-axis values as | Optional value formatter: number, time elapsed (minutes), or time elapsed (seconds). |
| Chart height | Height in pixels. Leave blank for automatic sizing based on the widget's zone. |

For richer chart layouts (stacked bars, multiple series, custom colors), use [Chart KATA](/docs/dashboards/widgets/chart-kata/) instead.

