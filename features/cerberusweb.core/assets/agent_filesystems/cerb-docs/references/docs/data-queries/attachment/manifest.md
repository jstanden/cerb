---
id: "docs-data-queries-attachment-manifest"
title: "Data Queries: Attachment Manifest"
url: "https://cerb.ai/docs/data-queries/attachment/manifest/"
summary: "This page provides detailed information on how to use the `attachment.manifest` data queries in Cerb to list files within an archive attachment, such as a `.zip` file. It outlines the various input parameters that can be used to customize the query, including `filter` for matching specific file patterns, `format` for specifying the output format, `id` for identifying the attachment record, `limit` for the number of file paths to return, and `offset` for the starting point of the file paths. The default response format is `dictionaries`, which is suitable for integration with sheets and APIs. An example query is provided to illustrate the usage of these parameters."
tags: ["docs"]
---
# attachment.manifest

`attachment.manifest` queries list files within an archive attachment (e.g. `.zip`).

# Inputs

| Key | Description |
| --- | --- |
| `filter:` | An optional pattern to match on returned paths and files. (e.g. `*.png`, `/example/path/*`) |
| `format:` | Must be `dictionaries` (default) or omitted |
| `id:` | The `id` of an [attachment](/docs/records/types/attachment/) record to inspect (must be an archive) |
| `limit:` | The number of file paths to return (defaults to `1000`) |
| `offset:` | The file path to start returning from (defaults to `0`) |

# Response Formats

The results can be returned in these formats:

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

# Examples

```
type:attachment.manifest
filter:*.xml
id:123
limit:100
format:dictionaries
```
