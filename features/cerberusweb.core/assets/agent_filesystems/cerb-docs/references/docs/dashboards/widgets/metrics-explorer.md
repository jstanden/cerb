---
id: "docs-dashboards-widgets-metrics-explorer"
title: "Chart Metrics Explorer - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/metrics-explorer/"
summary: "This page documents the Chart Metrics Explorer card widget in Cerb. It is an interactive, client-side chart builder for metrics that lets you add and live-edit multiple series without writing a data query first -- choosing the metric, aggregate function, dimension filters, chart type, axis, stacking, color, and visibility for each, over an adjustable range and period. Once the chart looks right, it can be exported as a metrics.timeseries data query, as an importable Chart KATA widget, or as an explorer configuration. The page covers configuration, the series controls, the available aggregate functions and periods, and the export paths."
tags: ["docs"]
---
The **Chart: Metrics Explorer** widget is an interactive chart builder for [metrics](/docs/metrics/).

 

Unlike the other chart widgets, you don't write a [data query](/docs/data-queries/) first. Build the chart by adding series and adjusting them in place, watching the result update as you go – then export it once it looks right.

It's a **card widget**, available on [metric](/docs/records/types/metric/) records and on other record types.

- [Building a chart](#building-a-chart)
  - [Functions](#functions)
  - [Periods](#periods)

- [Exporting](#exporting)
- [Related](#related)

# Building a chart

The widget opens with five series already defined against the current record – `Samples`, `Sum`, `Average`, `Min`, and `Max` – but only `Sum` is visible, so a first open looks like a single line. The rest are waiting behind their visibility toggles.

Each **series** is configured independently:

| Control | Notes |
| --- | --- |
| Metric | Which [metric](/docs/metrics/) to plot |
| Function | How to aggregate its samples |
| Dimension filters | Narrow the series by [dimension](/docs/metrics/#dimensions), matching (`is`) or excluding (`not`) |
| Type | Line, bar, or area |
| Axis | Plot against the left or right axis |
| Stacking | Stack this series with others |
| Color | The series color |
| Visibility | Show or hide without deleting it |

The chart's **range** and **period** apply to every series.

## Functions

`count`, `sum`, `avg`, `min`, `max`, `distinct`, `faceted_average`, `faceted_min`, `faceted_max`

The `faceted_*` variants aggregate across a metric's dimension rows within each bin – a sum of minimums, for instance, rather than the minimum of the whole bin.

## Periods

`minute`, `hour`, `day`

Pick the period to match the range: a day-level period over a 24-hour range gives you one bar.

# Exporting

Nothing you build in the explorer is saved. Series, range, period, chart type, axis, color, and visibility all live in the browser, and closing the card discards them. Exporting is the only way to keep a chart you want back.

Once a chart is right, it can leave the explorer three ways:

| Export | Use it for |
| --- | --- |
| [`metrics.timeseries`](/docs/data-queries/metrics/timeseries/) data query | Reusing the query anywhere data queries are accepted |
| [Chart KATA](/docs/dashboards/widgets/chart-kata/) widget | Putting the finished chart on a dashboard |
| Explorer config | Saving the widget's own configuration |

This makes the explorer a practical way to _write_ a `metrics.timeseries` query: build it visually, then export the query rather than composing it by hand.

# Related

- [Metrics](/docs/metrics/) – the metrics reference and dimension model
- [`metrics.timeseries`](/docs/data-queries/metrics/timeseries/) – values over time
- [`metrics.subtotals`](/docs/data-queries/metrics/subtotals/) – flat totals by dimension

