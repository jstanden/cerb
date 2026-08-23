---
id: "docs-data-queries-worklist-records"
title: "Data Queries: Worklist Records"
url: "https://cerb.ai/docs/data-queries/worklist/records/"
summary: "This page provides detailed information on using `worklist.records` data queries in Cerb to retrieve record dictionaries based on specific search criteria. It explains various parameters such as `of:`, which specifies the type of records to retrieve, and `query:`, which filters the results. The page also covers optional parameters like `expand:`, which determines which key paths to expand in the results, `page:`, for pagination, and `timeout:`, which sets a time limit for the query. The default format for results is `dictionaries`, suitable for sheets and API outputs. Additionally, the page includes examples, such as returning a stacked bar chart of tickets by owner and status, to illustrate the practical application of these queries."
tags: ["docs"]
---
# worklist.records

`worklist.records` [data queries](/docs/data-queries/) retrieve record dictionaries with a [search query](/docs/search/).

```
type:worklist.records
of:ticket
expand:[group_,custom_]
query:(
  status:open
  limit:10
  sort:-updated
)
format:dictionaries
```

- [of:](#of)
- [query:](#query)
- [query.required:](#queryrequired)
- [expand:](#expand)
- [page:](#page)
- [timeout:](#timeout)
- [format:](#format)
- [Examples](#examples)
  - [Return a stacked bar chart of tickets by owner by status](#return-a-stacked-bar-chart-of-tickets-by-owner-by-status)

# of:

The `of:` key specifies the type of [records](/docs/records/) to retrieve.

```
of:ticket
```

# query:

The `query:` key specifies the [query](/docs/search/) for filtering the results.

A query's `limit:` is clamped to **1-250** results. Larger values are silently reduced to `250`, so a `limit:1000` returns a partial result set without any warning. Use [`page:`](#page) to retrieve the results beyond the first page.

# query.required:

The `query.required:` key specifies the required [query](/docs/search/) for filtering the results. This is used to set the scope and should never contain placeholders with user input.

# expand:

The `expand:` key specifies which key paths should be expanded in the results.

```
expand:[custom_,group_,owner_]
```

# page:

The `page:` key specifies the page to return. Pages numbering is zero-based. This is used by functionality like [sheets](/docs/sheets/).

Since a query returns at most 250 results, `page:` is how you work through a larger set. With `limit:250`, `page:0` returns results 1-250, `page:1` returns 251-500, and so on.

# timeout:

The time limit of the query in milliseconds (0-60000). Default: `20000`.

# format:

The worklist results can be returned in these formats:

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

# Examples

## Return a stacked bar chart of tickets by owner by status

```
type:worklist.records
of:ticket
query:(status:open owner.id:me)
expand:[group_,owner_]
format:dictionaries
```

 
