---
id: "docs-dashboards-widgets-counter"
title: "Counter - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/counter/"
summary: "The Counter dashboard widget displays a single large number derived from a data source. It supports unit formatting (number, decimal, percentage, bytes, elapsed time), an optional prefix and suffix, and a configurable accent color."
tags: ["docs"]
---
The **Counter** widget displays a single large number drawn from a data source. It's the right widget for headline metrics like _Open tickets_, _Tasks due today_, _Active workers_, or _Storage used_.

 

# Configuration

| Field | Description |
| --- | --- |
| Data from | The data source extension used to produce the value. The data source's own configuration appears below this selector. |
| Display as | How to format the value: number, decimal, percentage, bytes, seconds elapsed, or minutes elapsed. |
| Prepend | Optional text shown before the number (e.g. `$`). |
| Append | Optional text shown after the number (e.g. ` open`). |
| Color | An accent color for the widget. |

The supplied data sources include [Manual Input](/docs/plugins/extensions/core.workspace.widget.datasource.manual/), [URL](/docs/plugins/extensions/core.workspace.widget.datasource.url/), [Worklist (Metric)](/docs/plugins/extensions/core.workspace.widget.datasource.worklist.metric/), and the modern [data query](/docs/data-queries/) datasource. New widgets should prefer the data query datasource -- the others are legacy and being phased out.

For more control over layout, formatting, and combining multiple metrics into one widget, use the [Sheet](/docs/dashboards/widgets/sheet/) widget instead.

