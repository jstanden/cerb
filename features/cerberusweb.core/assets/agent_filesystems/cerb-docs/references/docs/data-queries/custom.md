---
id: "docs-data-queries-custom"
title: "Data Queries: Custom Datasources"
url: "https://cerb.ai/docs/data-queries/custom/"
summary: "This page provides an overview of creating custom data queries using the `behavior.*` data queries in Cerb. It explains how these queries are initiated on the 'Data Query Datasource' event, allowing behaviors to gather data from various sources such as external APIs or worklist results. The page details how to configure an alias for these queries, which can then be used as a data query type. It covers the inputs required for these queries, the JSON format of the responses, and provides examples, such as fetching stock price data. The example demonstrates how bots can utilize these behaviors to return data from any source by sending query parameters as input variables and receiving data in JSON format."
tags: ["docs"]
---
# behavior.\*

`behavior.*` data queries are created on the 'Data Query Datasource' event. The behavior can gather data as needed (e.g. external APIs, worklist results, etc).

The alias configured there (e.g. `get_stock_price`) can be specified as a data query type (e.g. `type:behavior.get_stock_price`).

- [Inputs](#inputs)
- [Response Formats](#response-formats)
- [Examples](#examples)
  - [Fetch stock price data](#fetch-stock-price-data)

### Inputs

Any arguments provided in the query are provided to the behavior as input variables.

### Response Formats

Responses are returned in JSON format. This makes it much simpler to request data from bot behaviors from dashboard widgets, other bot behaviors, or the API.

# Examples

### Fetch stock price data

Bots can create behaviors on the 'Data Query Datasource' event. This allows data from any source (e.g. external API) to be returned from a data query. Data query parameters are sent to the bot behavior as input variables, and data is returned from the 'Return data' action as JSON.

 

The data query would look like:

```
type:behavior.get_stock_price
symbol:AAPL
```

Which could be visualized as:

 
