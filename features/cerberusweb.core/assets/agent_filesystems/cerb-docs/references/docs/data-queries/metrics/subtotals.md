---
id: "docs-data-queries-metrics-subtotals"
title: "Data Queries: Metrics Subtotals"
url: "https://cerb.ai/docs/data-queries/metrics/subtotals/"
summary: "This page documents the `metrics.subtotals` data query in Cerb. It is the inverse projection of `metrics.timeseries` -- instead of returning values plotted over time, it returns flat subtotals keyed by the raw dimension values. That makes it useful for ranking (which groups have the most open tickets), for feeding a pie or bar chart that has no time axis, and for composition, since the raw dimension values it returns can be used in a subquery worklist filter. The page covers its keys, the date range, series definition, dimension filters, and how it differs from metrics.timeseries."
tags: ["docs"]
---
# metrics.subtotals

`metrics.subtotals` [data queries](/docs/data-queries/) return flat subtotals for a [metric](/docs/metrics/), read directly from its samples.

This is the **inverse projection** of [`metrics.timeseries`](/docs/data-queries/metrics/timeseries/). Where that query returns values plotted over time, this one collapses the time axis and returns totals keyed by the raw dimension values.

That makes it the right query when you want to:

- [Syntax](#syntax)
  - [series.\*](#series)
    - [series.\*.query:](#seriesquery)

- [Choosing between subtotals and timeseries](#choosing-between-subtotals-and-timeseries)

# Syntax

```
type:metrics.subtotals
range:"last 30 days"
series.open:(
  label:Open
  metric:cerb.tickets.open
  by:group_id
  function:average
)
```

| Key | Notes |
| --- | --- |
| `range:` | The date range to read samples from |
| `series.*` | One or more series, each naming a metric and how to aggregate it |
| `timeout:` | Abort after this many milliseconds |
| `timezone:` | Shift timestamps when bucketing samples |
| `format:` | The output shape |

## series.\*

| Key | Notes |
| --- | --- |
| `metric:` | The [metric](/docs/metrics/) name |
| `by:` | A comma-separated list of [dimension](/docs/metrics/#dimensions) keys to group by |
| `function:` | `sum`, `min`, `max`, `average`, `samples`, `distinct`, and the `faceted_*` variants |
| `label:` | An optional display label |
| `query:` | An optional filter using dimension keys |

### series.\*.query:

Dimension filters work as they do in [`metrics.timeseries`](/docs/data-queries/metrics/timeseries/#seriesquery), including negation:

```
query:(group_id:!1)
```

Record-based dimensions can use [deep search filters](/docs/search/).

# Choosing between subtotals and timeseries

| Question | Query |
| --- | --- |
| How has this changed over time? | [`metrics.timeseries`](/docs/data-queries/metrics/timeseries/) |
| What are the totals right now, by dimension? | `metrics.subtotals` |

If the result is going into a chart with a time axis, you want `metrics.timeseries`. If it's going into a pie chart, a ranked table, or another query, you want `metrics.subtotals`.

