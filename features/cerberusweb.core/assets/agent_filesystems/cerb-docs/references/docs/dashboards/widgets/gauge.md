---
id: "docs-dashboards-widgets-gauge"
title: "Gauge - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/gauge/"
summary: "The Gauge dashboard widget displays a value as a meter between a minimum and maximum, with up to seven configurable colored thresholds. Useful for visualizing utilization, progress against a goal, and SLA health."
tags: ["docs"]
---
The **Gauge** widget displays a value as a meter between a configured minimum and maximum, with up to seven colored thresholds. It's useful for visualizing utilization (e.g. license seats in use), progress against a goal (e.g. tickets resolved this week), and health indicators (e.g. SLA compliance).

# Configuration

Configuration is split into two tabs.

## Data Source

| Field | Description |
| --- | --- |
| Data from | The data source extension used to produce the current value. Configuration for the selected source appears below. |
| Display as | How to format the value: number, decimal, percentage, bytes, seconds elapsed, or minutes elapsed. |
| Prepend | Optional text shown before the number. |
| Append | Optional text shown after the number. |

## Thresholds

| Field | Description |
| --- | --- |
| Minimum value | The low end of the gauge scale. |
| Label | An optional label for the threshold band (e.g. _Healthy_, _Warning_, _Critical_). |
| Max. Value | The high end of this band. Subsequent bands begin where the previous one ended. |
| Color | The color used to fill this band. |

Up to seven threshold bands can be defined. The largest defined Max. Value becomes the gauge's maximum.

Like [Counter](/docs/dashboards/widgets/counter/), the Gauge widget can use any installed data source. The modern [data query](/docs/data-queries/) datasource is preferred for new widgets.

