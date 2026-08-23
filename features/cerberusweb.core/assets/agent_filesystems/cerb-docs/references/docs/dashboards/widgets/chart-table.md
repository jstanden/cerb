---
id: "docs-dashboards-widgets-chart-table"
title: "Chart: Table - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/chart-table/"
summary: "The Chart Table dashboard widget renders a data query as a simple table with one row per result. Columns come from the query's selected fields, with no additional configuration beyond the query itself."
tags: ["docs"]
---
The **Chart: Table** widget renders a [data query](/docs/data-queries/) as a simple HTML table – one row per result row, columns drawn from whatever the query selects. It's the right choice when you want raw values rather than a visualization.

 

For more control over column layout, formatting, conditional styling, and inline rendering, use the [Sheet](/docs/dashboards/widgets/sheet/) widget instead.

# Configuration

| Field | Description |
| --- | --- |
| Run this data query | A [data query](/docs/data-queries/) returning the rows and columns to render. |
| Cache | Number of seconds to cache the query results before refetching. |

