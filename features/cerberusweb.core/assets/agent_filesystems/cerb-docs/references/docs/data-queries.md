---
id: "docs-data-queries"
title: "Data Queries"
url: "https://cerb.ai/docs/data-queries/"
summary: "This page provides an overview of Cerb's data queries, a text-based query language designed to retrieve, transform, and format data for visualizations. It details the structure of data queries, which consist of `key:value` pairs, and emphasizes the necessity of a `type:` key in every query. The page lists various query types, such as attachment manifests, calendar events, classifier predictions, and worklist metrics, each serving different data retrieval and processing purposes. It also explains how data queries can be executed through different Cerb features, including dashboard widgets, bot behaviors, and the API, with results returned in JSON format. The page highlights the ease of automating these queries in various Cerb components, making them a versatile tool for data management and visualization."
tags: ["docs"]
---
 

**Data queries** are a purpose-built, text-based query language to retrieve, transform, and format data for visualizations.

- [Types](#types)
- [Running data queries](#running-data-queries)
  - [Setup](#setup)
  - [Dashboard widgets](#dashboard-widgets)
  - [Automations](#automations)
  - [API](#api)

These textual queries are very simple to automate in [automations](/docs/automations/), [dashboard](/docs/dashboards/) widgets, or the [API](/docs/api/).

A data query is a collection of `key:value` pairs.

Every query must include a `type:` key:

```
type:worklist.subtotals
```

Additional keys are available depending on the type of data being requested.

A `format:` key prepares the response for different visualizations:

```
format:dictionaries
```

# Types

| Type | Description |
| --- | --- |
| [attachment.manifest](/docs/data-queries/attachment/manifest/) | Iterates and filters the manifest of attachment archives (e.g. ZIP). |
| [automation.invoke](/docs/data-queries/automation/invoke/) | Invoke a custom [data.query](/docs/automations/triggers/data.query/) automation. |
| [autocomplete.completions](/docs/data-queries/autocomplete/completions/) | Return a complete listing of the autocomplete options for an automation or automation event |
| [calendar.availability](/docs/data-queries/calendar/availability/) | Aggregate any number of matching calenders to display availability over a date range by hour or day |
| [calendar.events](/docs/data-queries/calendar/events/) | Return events and synthesized recurring events for the given calendars grouped into days |
| [classifier.prediction](/docs/data-queries/classifier/prediction/) | Return a predicted classification for the given text using the given classifier |
| [data.query.types](/docs/data-queries/data/query-types/) | Return a list of data query types |
| [gpg.keyinfo](/docs/data-queries/gpg/keyinfo/) | Return details about a PGP public key |
| [metrics.subtotals](/docs/data-queries/metrics/subtotals/) | Flat metric subtotals by dimension, with no time axis |
| [metrics.timeseries](/docs/data-queries/metrics/timeseries/) | Aggregates metrics statistics over a date range |
| [platform.extensions](/docs/data-queries/platform/extensions/) | Return a filterable and pageable list of plugin extensions for a given point |
| [platform.extension.points](/docs/data-queries/platform/points/) | Return a filterable and pageable list of platform extension points |
| [record.fields](/docs/data-queries/record/fields/) | Return a filterable and pageable list of fields from a record type |
| [record.filters](/docs/data-queries/record/filters/) | Return the available search filters for a record type |
| [record.types](/docs/data-queries/record/types/) | Return a filterable and pageable list of record types |
| [ui.icons](/docs/data-queries/ui/icons/) | Return a filterable and pageable list of icons |
| [usage.behaviors](/docs/data-queries/usage/bot-behaviors/) | Return historical usage data for bot behaviors (e.g. uses, avg. runtime, and total runtime over time) |
| [usage.snippets](/docs/data-queries/usage/snippets/) | Return historical usage data for snippets (e.g. uses by worker over time) |
| [worklist.geo.points](/docs/data-queries/worklist/geopoints/) | Return geolocation data from worklist records |
| [worklist.metrics](/docs/data-queries/worklist/metrics/) | Return computed metrics based on worklist data (e.g. 'average ticket first response time over the past year') |
| [worklist.records](/docs/data-queries/worklist/records/) | Retrieve record dictionaries with a search query |
| [worklist.series](/docs/data-queries/worklist/series/) | Return series-based data from any worklist (e.g. tickets created by month by status) |
| [worklist.subtotals](/docs/data-queries/worklist/subtotals/) | Run aggregate functions to categorize matching worklist records |
| [worklist.xy](/docs/data-queries/worklist/xy/) | Plot two-dimensional data to visualize clusters or correlations |
| [sample.\*](/docs/data-queries/sample/) | Return simulated data |
| [custom](/docs/data-queries/custom/) | Fetch custom data from a bot behavior |

# Running data queries

Data queries may be utilized in several features.

## Setup

As an administrator, you can test data queries from **Setup&nbsp;» Developers&nbsp;» Data Query Tester**.

 

## Dashboard widgets

Data queries can be used to build visualizations on dashboards.

 

## Automations

Automations can use the [data.query](/docs/automations/commands/data.query/) command to run a data query and retrieve the results.

This is a much simpler way to exchange information between multiple functions and APIs.

 

## API

Data queries can be run from the API using the [/data/query](/docs/api/endpoints/data/) endpoint.

For a `GET` the query should be provided in the `q` query parameter.

For a `POST` the query should be provided as text in the HTTP request body.

The results will always be in JSON format. This is now the recommended way to extract data from worklists and other functions for use in other applications.

 

Data is returned in JSON format.

