---
id: "docs-data-queries-record-types"
title: "Data Queries: Record Types"
url: "https://cerb.ai/docs/data-queries/record/types/"
summary: "This page provides information on `record.types` data queries in Cerb, which return a list of record types that can be filtered and paginated. It details the inputs required for these queries, such as options to exclude custom record types, apply filters, set result limits, and specify page numbers. The response format is primarily in dictionaries, suitable for integration with sheets and APIs. An example query and its corresponding response are provided, illustrating how to filter for specific record types like widgets and retrieve their details in a structured format."
tags: ["docs"]
---
# record.types

`record.types` data queries return a filterable and pageable list of [record types](/docs/records/types/).

### Inputs

| Req'd | Key | Notes |
| --- | --- | --- |
| &nbsp; | `exclude_custom:` | `yes` to exclude custom record types (default `no`) |
| &nbsp; | `only_custom:` | `yes` to include only custom record types (default `no`). The complement of `exclude_custom:`. |
| &nbsp; | `filter:` | An optional keyword used to filter the results |
| &nbsp; | `limit:` | The desired number of results per page |
| &nbsp; | `options:` | `autocomplete`, `avatars`, `cards`, `comments`, `custom_fields`, `links`, `owner`, `records`, `search`, `snippets`, `va_variable`, `watchers`, `workspace` |
| &nbsp; | `page:` | The desired starting page (zero-based) |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

#### Query:

```
type:record.types
filter:widget
options:[search]
format:dictionaries
```

#### Response:

```
{
  "data": {
    "18": {
      "id": "cerb.contexts.card.widget",
      "uri": "card_widget",
      "label_singular": "Card Widget",
      "label_plural": "Card Widgets"
    },
    "69": {
      "id": "cerb.contexts.portal.widget",
      "uri": "portal_widget",
      "label_singular": "Portal Widget",
      "label_plural": "Portal Widgets"
    },
    "71": {
      "id": "cerberusweb.contexts.profile.widget",
      "uri": "profile_widget",
      "label_singular": "Profile Widget",
      "label_plural": "Profile Widgets"
    },
    "98": {
      "id": "cerberusweb.contexts.workspace.widget",
      "uri": "workspace_widget",
      "label_singular": "Workspace Widget",
      "label_plural": "Workspace Widgets"
    }
  },
  "_": {
    "type": "record.types",
    "format": "dictionaries"
  }
}
```
