---
id: "docs-data-queries-ui-icons"
title: "Data Queries: UI Icons"
url: "https://cerb.ai/docs/data-queries/ui/icons/"
summary: "This page provides information on `ui.icons` data queries, which return a filterable and pageable list of icons. It details the inputs required for the query, such as optional filters, the number of results per page, and the starting page. The response format is primarily in dictionaries, suitable for sheets and API results. An example query is provided, demonstrating how to filter icons related to 'git' and limit the results to 10 per page, along with the corresponding response structure."
tags: ["docs"]
---
# ui.icons

`ui.icons` data queries return a filterable and pageable list of icons.

### Inputs

| Req'd | Key | Notes |
| --- | --- | --- |
| &nbsp; | `filter:` | An optional keyword used to filter the results. |
| &nbsp; | `limit:` | The desired number of results per page. |
| &nbsp; | `page:` | The desired starting page (zero-based). |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

#### Query:

```
type:ui.icons
filter:git
limit:10
format:dictionaries
```

#### Response:

```
{
  "data": [
    {
      "icon": "git-branch"
    },
    {
      "icon": "git-commit"
    },
    {
      "icon": "git-compare"
    },
    {
      "icon": "git-create"
    },
    {
      "icon": "git-delete"
    },
    {
      "icon": "git-merge"
    },
    {
      "icon": "git-private"
    },
    {
      "icon": "git-pull-request"
    }
  ],
  "_": {
    "type": "ui.icons",
    "format": "dictionaries",
    "paging": {
      "page": {
        "of": 1,
        "rows": {
          "of": 8,
          "count": 8,
          "limit": 10,
          "from": 1,
          "to": 8
        },
        "index": 0
      }
    }
  }
}
```
