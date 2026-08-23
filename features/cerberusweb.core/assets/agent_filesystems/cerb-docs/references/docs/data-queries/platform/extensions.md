---
id: "docs-data-queries-platform-extensions"
title: "Data Queries: Platform Extensions"
url: "https://cerb.ai/docs/data-queries/platform/extensions/"
summary: "This page provides information on data queries related to platform extensions in Cerb. It explains how to retrieve a list of plugin extensions for a specified extension point, with options to filter and paginate the results. The inputs required for the query include the extension point, and optionally, a filter keyword, limit on the number of results per page, and the starting page number. The response is formatted as dictionaries by default, making it suitable for spreadsheets and API results. An example query and its corresponding response are provided, showcasing various plugin extensions such as 'Attachment Viewer,' 'Behavior Tree,' and 'Knowledgebase Article,' each with specific identifiers, names, classes, and associated plugin IDs."
tags: ["docs"]
---
# platform.extensions

`platform.extensions` data queries return a filterable and pageable list of plugin [extensions](/docs/records/types/) for a given `point`.

### Inputs

| Req'd | Key | Notes |
| --- | --- | --- |
| **x** | `point:` | An [extension point](/docs/records/types/) |
| &nbsp; | `filter:` | An optional keyword used to filter the results |
| &nbsp; | `limit:` | The desired number of results per page |
| &nbsp; | `page:` | The desired starting page (zero-based) |

### Response Formats

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

### Examples

#### Query:

```
type:platform.extensions
point:cerb.card.widget
format:dictionaries
```

#### Response:

```
{
  "data": [
    {
      "id": "cerb.card.widget.attachment.viewer",
      "name": "Attachment Viewer",
      "class": "CardWidget_AttachmentViewer",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.behavior.tree",
      "name": "Behavior Tree",
      "class": "CardWidget_BehaviorTree",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.classifier.trainer",
      "name": "Classifier Trainer",
      "class": "CardWidget_ClassifierTrainer",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.conversation",
      "name": "Conversation",
      "class": "CardWidget_Conversation",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.form_interaction",
      "name": "Interactions Toolbar",
      "class": "CardWidget_FormInteraction",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.kb_article.viewer",
      "name": "Knowledgebase Article",
      "class": "CardWidget_KbArticle",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.fields",
      "name": "Record Fields",
      "class": "CardWidget_Fields",
      "plugin_id": "cerberusweb.core",
      "params": []
    },
    {
      "id": "cerb.card.widget.sheet",
      "name": "Sheet",
      "class": "CardWidget_Sheet",
      "plugin_id": "cerberusweb.core",
      "params": []
    }
  ],
  "_": {
    "type": "platform.extensions",
    "format": "dictionaries"
  }
}
```
