---
id: "docs-dashboards-widgets-chart-scatterplot"
title: "Chart: Scatterplot - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/chart-scatterplot/"
summary: "The Chart Scatterplot dashboard widget renders an X/Y plot from a data query, with one point per data row. It's useful for spotting correlations, clusters, and outliers between two numeric measurements."
tags: ["docs"]
---
The **Chart: Scatterplot** widget displays an X/Y plot from a [data query](/docs/data-queries/), with each data row drawn as a single point. Scatterplots are useful for spotting correlations between two numeric measurements – for instance, response time vs. ticket age, or message length vs. customer satisfaction.

 

# Configuration

| Field | Description |
| --- | --- |
| Run this data query | A [data query](/docs/data-queries/) that returns rows with x and y values. |
| Cache | Number of seconds to cache the query results before refetching. |
| Label x-axis | Optional axis title shown along the horizontal axis. |
| Format x-axis values as | Optional value formatter: number, time elapsed (minutes), or time elapsed (seconds). |
| Label y-axis | Optional axis title shown along the vertical axis. |
| Format y-axis values as | Optional value formatter: number, time elapsed (minutes), or time elapsed (seconds). |
| Chart height | Height in pixels. Leave blank for automatic sizing. |

