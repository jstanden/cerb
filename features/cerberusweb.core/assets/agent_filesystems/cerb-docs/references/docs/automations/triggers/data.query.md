---
id: "docs-automations-triggers-data-query"
title: "data.query"
url: "https://cerb.ai/docs/automations/triggers/data.query/"
summary: "This page provides an overview of the 'data.query' feature in Cerb, which allows for the generation of custom data query results through automation. It explains how this feature can be used to interact with third-party data sources in various Cerb functionalities like sheets and charts. The page details the inputs required for the automation dictionary, including custom input values and the requested query results format. It also describes the expected outputs, specifically the format of the data returned, which can vary based on the specified query format, such as an array of dictionaries. The page highlights the flexibility of returning more complex data structures using annotations like `@json` or `@key`."
tags: ["docs", "docs-automations"]
---
**data.query** [automations](/docs/automations/) generate custom data query results for an [automation.invoke](/docs/data-queries/automation/invoke/) data query.

For instance: reading from a resource file, fetching from an external API, post-processing search results, etc.

It's now possible to work with third-party data sources in any feature that supports data queries (sheets, charts, etc).

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller |
| `query_format` | string | The requested query results format (e.g. `format:dictionaries`) |

# Outputs

## return:

| Key | &nbsp; |
| --- | --- |
| `data:` | The results of the data query. |

The format of `data:` will depend on the given `query_format`.

For instance, the `dictionaries` format is an array of dictionaries, like:

```
return:
  data:
    0:
      id: 1
      name: Record 1
    1:
      id: 2
      name: Record 2
```

More complex values can be returned using the `@json` or `@key` annotations.

