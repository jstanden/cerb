---
id: "docs-data-queries-metrics-timeseries"
title: "Data Queries: Metrics Timeseries"
url: "https://cerb.ai/docs/data-queries/metrics/timeseries/"
summary: "This page provides detailed information on the `metrics.timeseries` data queries in Cerb, which are used to aggregate metrics statistics over specified date ranges. It explains how to retrieve multiple series, each with different metrics and functions such as average, sum, minimum, maximum, and count. The page outlines how these series can be aggregated or filtered by various metric dimensions. Key components include defining the period for the level of detail (minute, hour, day, week, month, year), setting the date range for statistics, and specifying series details like metric name, grouping dimensions, functions, labels, and handling of missing data. It also covers the use of dimension filters, query timeouts, timezone adjustments, and output formats for the results, such as dictionaries, timeblock, and timeseries."
tags: ["docs"]
---
# metrics.timeseries

`metrics.timeseries` [data queries](/docs/data-queries/) aggregates [metrics](/docs/metrics/) statistics over a date range. This can retrieve multiple series each with a different metric and function (avg, sum, min, max, count). A series can also be aggregated or filtered by any combination of metric [dimensions](/docs/metrics/#dimensions).

 

```
type:metrics.timeseries
period:hour
range:"-24 hours to now"
series.avg:(
  label:Average
  metric:cerb.tickets.open
  function:avg
)
series.min:(
  label:Min
  metric:cerb.tickets.open
  function:min
)
series.avg:(
  label:Max
  metric:cerb.tickets.open
  function:max
)
timezone:Europe/Berlin
format:timeseries
```

The ['Chart: Metrics Explorer'](/docs/dashboards/widgets/metrics-explorer/) widget can write one of these queries for you with **Copy » Data Query**. Expect two differences from the example above: it uses the `faceted_*` functions rather than `avg`, `min`, and `max`, and it templates the metric as `metric:"{{record_name}}"`. That placeholder only resolves against a [metric](/docs/metrics/) record, so pasting the query anywhere else returns nothing until you replace it with the metric's name.

- [period:](#period)
- [range:](#range)
- [series.\*:](#series)
  - [series.\*.query:](#seriesquery)

- [timeout:](#timeout)
- [timezone:](#timezone)
- [format:](#format)

# period:

The [period](/docs/metrics/#periods) is the metric's level of detail:

| `minute` | 5-minute statistics (up to the past 24 hours) |
| `hour` | hourly statistics (up to the past 2 weeks) |
| `day` | daily statistics (no limit) |
| `week` | weekly statistics starting on Monday (no limit) |
| `week-sun` | weekly statistics starting on Sunday (no limit) |
| `month` | monthly statistics (no limit) |
| `year` | yearly statistics (no limit) |

# range:

The date range to show statistics for, grouped by the `period`.

| `this week` |
| `this month` |
| `-1 year` |
| `Jan 1 2025 to Dec 31 2025` |

# series.\*:

Each `series.*` series should provide:

| Key | Description |
| --- | --- |
| `metric:` | The [metric](/docs/metrics/) name |
| `by:` | A comma-separated list of [dimension](/docs/metrics/#dimensions) keys to group by |
| `function:` | `sum`, `min`, `max`, `average`, `samples`, `distinct`, `faceted_average`, `faceted_min`, `faceted_max`. The faceted functions sum samples with different dimensions into a single total. |
| `label:` | A human-readable label for this series |
| `missing:` | How to handle missing sample periods: `null` (default), `zero` (set to 0), or `carry` (carry the last sample) |
| `query:` | An optional query using [dimension](/docs/metrics/#dimensions) keys to filter samples. |

### series.\*.query:

Dimension filters can use negation. For instance, `query:(worker_id:![1,2,3])`

This makes proportional charts straightforward – one series for a dimension and another for everything else, stacked against each other:

```
series.mine:(
  label:Mine
  metric:cerb.tickets.open
  query:(group_id:1)
)
series.others:(
  label:Others
  metric:cerb.tickets.open
  query:(group_id:!1)
)
```

Record-based dimension can use [deep search filers](/docs/search/).

# timeout:

The query will be aborted after this many milliseconds have elapsed with no response.

The default is **20000** milliseconds (20 seconds).

# timezone:

Statistics are stored in GMT/UTC. The `timezone:` location shifts timestamps when grouping metrics by `period`.

Day-based period units collapse into UTC consistently, so a day boundary means the same thing regardless of where the query runs.

For example:

| `America/Los_Angeles` |
| `Europe/Berlin` |
| `Asia/Tokyo` |

If omitted, this defaults to the timezone of the current worker or the server.

# format:

The metric results can be returned in various formats:

| Format | &nbsp; |
| --- | --- |
| `dictionaries` | Returns series data suitable for a sheet or automation. |
| `timeblock` | Returns data suitable for a timeblocks widget |
| `timeseries` | (Default) Returns series-based data suitable for a time-series chart (with the 'x' axis values as timestamps) |

