---
id: "docs-data-queries-data-query-types"
title: "Data Queries: Data Query Types"
url: "https://cerb.ai/docs/data-queries/data/query-types/"
summary: "This page provides information on how to use `data.query.types` data queries to retrieve details about available data query types, including inputs, response formats, and examples. The `data.query.types` data query returns a dictionary containing metadata for all supported data query types, with options to include or exclude custom query types."
tags: ["docs"]
---
# data.query.types

`data.query.types` return details about the available data query types.

### Inputs

| `exclude_custom:` | Include or exclude custom query types |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

- [query](#)
- [response](#)

- 
```
type:data.query.types
exclude_custom:yes
format:dictionaries
```
- 
```
{
  "data": {
    "attachment.manifest": {
      "name": "attachment.manifest",
      "description": "Read and filter file contents from archives",
      "docs_url": "https://cerb.ai/docs/data-queries/attachment/manifest/"
    },
    "autocomplete.completions": {
      "name": "autocomplete.completions",
      "description": "Autocomplete completions for a schema",
      "docs_url": "https://cerb.ai/docs/data-queries/autocomplete/completions/"
    },
    "automation.invoke": {
      "name": "automation.invoke",
      "description": "Invoke an automation for custom data queries",
      "docs_url": "https://cerb.ai/docs/data-queries/automation/invoke/"
    },
    "calendar.availability": {
      "name": "calendar.availability",
      "description": "Combined availability from calendars",
      "docs_url": "https://cerb.ai/docs/data-queries/calendar/availability/"
    },
    "calendar.events": {
      "name": "calendar.events",
      "description": "Get events and recurring events from calendars",
      "docs_url": "https://cerb.ai/docs/data-queries/calendar/events/"
    },
    "classifier.prediction": {
      "name": "classifier.prediction",
      "description": "Predict classification for text with a classifier",
      "docs_url": "https://cerb.ai/docs/data-queries/classifier/prediction/"
    },
    "data.query.types": {
      "name": "data.query.types",
      "description": "Get metadata for data query types",
      "docs_url": "https://cerb.ai/docs/data-queries/data/query-types/"
    },
    "gpg.keyinfo": {
      "name": "gpg.keyinfo",
      "description": "Get info for a PGP public key",
      "docs_url": "https://cerb.ai/docs/data-queries/gpg/keyinfo/"
    },
    "metrics.timeseries": {
      "name": "metrics.timeseries",
      "description": "Chart and aggregate time-based metrics",
      "docs_url": "https://cerb.ai/docs/data-queries/metrics/timeseries/"
    },
    "platform.extension.points": {
      "name": "platform.extension.points",
      "description": "Filterable and pageable list of plugin extensions hooks",
      "docs_url": "https://cerb.ai/docs/data-queries/platform/extension-points/"
    },
    "platform.extensions": {
      "name": "platform.extensions",
      "description": "Filterable and pageable list of plugin extensions for a given hook",
      "docs_url": "https://cerb.ai/docs/data-queries/platform/extensions/"
    },
    "record.fields": {
      "name": "record.fields",
      "description": "Filterable and pageable list of fields from a record type",
      "docs_url": "https://cerb.ai/docs/data-queries/record/fields/"
    },
    "record.types": {
      "name": "record.types",
      "description": "Filterable and pageable list of record types",
      "docs_url": "https://cerb.ai/docs/data-queries/record/types/"
    },
    "sample.geo.points": {
      "name": "sample.geo.points",
      "description": "Simulated GeoJSON data",
      "docs_url": "https://cerb.ai/docs/data-queries/sample/geopoints/"
    },
    "sample.records": {
      "name": "sample.records",
      "description": "Simulated record dictionaries data",
      "docs_url": "https://cerb.ai/docs/data-queries/sample/records/"
    },
    "sample.timeseries": {
      "name": "sample.timeseries",
      "description": "Simulated time-series data",
      "docs_url": "https://cerb.ai/docs/data-queries/sample/timeseries/"
    },
    "sample.xy": {
      "name": "sample.xy",
      "description": "Simulated X/Y data for scatterplots",
      "docs_url": "https://cerb.ai/docs/data-queries/sample/xy/"
    },
    "ui.icons": {
      "name": "ui.icons",
      "description": "Filterable and pageable list of icons",
      "docs_url": "https://cerb.ai/docs/data-queries/ui/icons/"
    },
    "usage.behaviors": {
      "name": "usage.behaviors",
      "description": "Historical usage data for bot behaviors",
      "docs_url": "https://cerb.ai/docs/data-queries/usage/bot-behaviors/"
    },
    "usage.snippets": {
      "name": "usage.snippets",
      "description": "Historical usage data for snippets",
      "docs_url": "https://cerb.ai/docs/data-queries/usage/snippets/"
    },
    "worklist.geo.points": {
      "name": "worklist.geo.points",
      "description": "Geolocation data from worklist records",
      "docs_url": "https://cerb.ai/docs/data-queries/worklist/geopoints/"
    },
    "worklist.metrics": {
      "name": "worklist.metrics",
      "description": "Computed metrics based on worklist data",
      "docs_url": "https://cerb.ai/docs/data-queries/worklist/metrics/"
    },
    "worklist.records": {
      "name": "worklist.records",
      "description": "Record dictionaries with a search query",
      "docs_url": "https://cerb.ai/docs/data-queries/worklist/records/"
    },
    "worklist.series": {
      "name": "worklist.series",
      "description": "Series-based data from any worklist",
      "docs_url": "https://cerb.ai/docs/data-queries/worklist/series/"
    },
    "worklist.subtotals": {
      "name": "worklist.subtotals",
      "description": "Aggregations on worklist record fields",
      "docs_url": "https://cerb.ai/docs/data-queries/worklist/subtotals/"
    },
    "worklist.xy": {
      "name": "worklist.xy",
      "description": "Compute clusters of two dimensional data from records",
      "docs_url": "https://cerb.ai/docs/data-queries/worklist/xy/"
    }
  },
  "_": {
    "type": "data.query.types",
    "format": "dictionaries"
  }
}
```

