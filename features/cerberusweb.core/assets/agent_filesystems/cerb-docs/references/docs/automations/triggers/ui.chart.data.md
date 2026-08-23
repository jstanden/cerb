---
id: "docs-automations-triggers-ui-chart-data"
title: "ui.chart.data"
url: "https://cerb.ai/docs/automations/triggers/ui.chart.data/"
summary: "This page provides information on 'ui.chart.data' automations, which are utilized as datasets by Chart KATA widgets in Cerb. It outlines the structure of the automation dictionary, detailing the input values such as custom inputs from the caller, and records related to the widget and active worker, both of which support key expansion. The page also describes the expected output format, which includes a dictionary containing chart data as an array of series with consistent lengths. An example of the output format is provided, illustrating how time series data and corresponding series values are structured in CSV format."
tags: ["docs", "docs-automations"]
---
**ui.chart.data** [automations](/docs/automations/) are used as a dataset by [Chart KATA](/docs/dashboards/widgets/chart-kata/) widgets.

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller |
| `widget_*` | record | The [card](/docs/records/types/card_widget/), [profile](/docs/records/types/profile_widget/), or [workspace](/docs/records/types/workspace_widget/) widget record (supports key expansion) |
| `worker_*` | record | The active [worker](/docs/records/types/worker/) record. Supports key expansion. |

# Outputs

## return:

| Key | Type | Notes |
| --- | --- | --- |
| `data` | dictionary | The chart data as an array of series with the same length |

```
return:
  data:
    ts@csv: 2023-10, 2023-11, 2023-12
    series0@csv: 104, 77, 84 
    series1@csv: 218, 335, 183
```
