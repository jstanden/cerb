---
id: "docs-data-queries-worklist-geopoints"
title: "Data Queries: Geo Points"
url: "https://cerb.ai/docs/data-queries/worklist/geopoints/"
summary: "This page provides detailed information on using the `worklist.geo.points` data queries in Cerb to retrieve and plot geolocation data from worklist records. It explains the structure and components of the query, including the `series.*` parameters such as `of`, `point`, `fields`, `query`, and `query.required`, which define the type of records, the field containing geolocation data, additional fields to include, and filtering criteria. The page also describes the output formats available, such as `geopoints` and `table`, and provides examples, like plotting organizations based on geolocation custom fields, to illustrate practical applications of these queries."
tags: ["docs"]
---
# worklist.geo.points

`worklist.geo.points` [data queries](/docs/data-queries/) returns geolocation data from worklist records.

```
type:worklist.geo.points
series.points:(
  of:org
  point:coordinates
  fields:[name,coordinates]
  query:(coordinates:!null)
)
format:geojson
```

- [series.\*](#series)
  - [of:](#of)
  - [point:](#point)
  - [fields:](#fields)
  - [query:](#query)
  - [query.required:](#queryrequired)

- [format:](#format)
- [Examples](#examples)
  - [Plot organizations based on a geolocation custom field.](#plot-organizations-based-on-a-geolocation-custom-field)

# series.\*

Each `series.*` should provide:

## of:

The `of:` key specifies the type of [records](/docs/records/) to search.

```
of:tickets
```

## point:

The `point:` key specifies the record [field](/docs/records/#fields) containing latitude/longitude data.

```
point:coordinates
```

## fields:

The `fields:` key specifies the record [fields](/docs/records/#fields) to include with each plotted point.

```
point:coordinates
```

## query:

The `query:` key specifies a [search query](/docs/search/) for filtering records.

```
query:(region:Europe)
```

## query.required:

The `query.required:` key specifies a mandatory [search query](/docs/search/) for filtering records. This should be protected from user-entered filters.

```
query.required:(ids:[1,2,3])
```

# format:

The results can be returned in various formats:

- **geopoints** (default) returns a list of latitude/longitude points.

- **table** returns tabular output, suitable for display with the 'Chart: Table' visualization widget.

# Examples

## Plot organizations based on a geolocation custom field.

```
type:worklist.geo.points
series.points:(
  of:org
  point:coordinates
  fields:[name,coordinates]
  query:(coordinates:!null)
)
format:geojson
```

 
