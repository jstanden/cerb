---
id: "docs-dashboards-widgets-chart-pie"
title: "Chart: Pie - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/chart-pie/"
summary: "The Chart Pie dashboard widget renders a pie or donut chart from a data query, with one slice per category. The legend, donut style, and chart height are configurable."
tags: ["docs"]
---
The **Chart: Pie** widget displays a pie or donut chart from a [data query](/docs/data-queries/), where each slice represents one category's share of the total. It's useful for visualizing how a single total breaks down – tickets by status, time spent by group, opportunities by stage, etc.

 

# Configuration

| Field | Description |
| --- | --- |
| Run this data query | A [data query](/docs/data-queries/) that returns one value per category. Each row becomes a slice. |
| Cache | Number of seconds to cache the query results before refetching. |
| Display the chart as | `pie` (filled circle) or `donut` (ring with hollow center). |
| Chart height | Height in pixels. Leave blank for automatic sizing. |
| Show legend | When checked, the legend lists each category and its color. |

For richer chart layouts (multiple series, custom colors, mixed visualizations), use [Chart KATA](/docs/dashboards/widgets/chart-kata/) instead.

