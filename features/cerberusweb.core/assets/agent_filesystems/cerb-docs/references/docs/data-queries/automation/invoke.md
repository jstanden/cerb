---
id: "docs-data-queries-automation-invoke"
title: "Data Queries: Automation Invoke"
url: "https://cerb.ai/docs/data-queries/automation/invoke/"
summary: "This page provides information on using the `automation.invoke` feature in Cerb to run data queries that return custom results. It explains how this functionality can be used to integrate with third-party data sources, such as APIs. The page outlines the necessary inputs for invoking an automation, specifically the automation name and the inputs to be sent. It also mentions that the supported response formats are determined by each automation. An example is provided to illustrate how to use `automation.invoke` to fetch data with specific parameters."
tags: ["docs"]
---
# automation.invoke

`automation.invoke` queries run a [data.query](/docs/automations/triggers/data.query/) to return custom results. This can be used to integrate with third-party data sources (e.g. APIs).

# Inputs

| Key | Description |
| --- | --- |
| `name:` | The automation name to invoke. Must be of type `data.query`. |
| `inputs:` | The inputs to send to the automation. |

# Response Formats

The supported result formats are determined by each automation.

# Examples

```
type:automation.invoke
name:example.fetchApiData
inputs:(
  dateRange:"this year"
)
format:dictionaries
```
