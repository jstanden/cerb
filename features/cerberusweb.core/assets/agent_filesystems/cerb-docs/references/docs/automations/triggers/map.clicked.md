---
id: "docs-automations-triggers-map-clicked"
title: "map.clicked"
url: "https://cerb.ai/docs/automations/triggers/map.clicked/"
summary: "This page provides detailed information about the 'map.clicked' automations in Cerb, which are activated when users click on regions or points within a map widget. It outlines the structure of the automation dictionary, including key inputs such as the type of feature clicked, its properties, custom input values, and details about the widget and active worker. The page also describes the expected output, specifically the 'sheet' key, which determines the schema to display based on the clicked feature's properties."
tags: ["docs", "docs-automations"]
---
**map.clicked** [automations](/docs/automations/) are triggered by clicks on [map](/docs/maps/) widget regions and points.

- [Inputs](#inputs)
- [Outputs](#outputs)
  - [return:](#return)

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `feature_type` | string | `region` or `point` |
| `feature_properties` | dictionary | The key/value [properties](/docs/maps/#properties) of the clicked feature |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller |
| `widget_*` | record | A [card](/docs/records/types/card_widget/), [profile](/docs/records/types/profile_widget/), or [workspace](/docs/records/types/workspace_widget/) widget. Supports key expansion. |
| `worker_*` | record | The active [worker](/docs/records/types/worker/) |

# Outputs

## return:

| Key | Type | Notes |
| --- | --- | --- |
| `sheet` | string | A [sheet schema](/docs/sheets/) to display for the clicked feature based on the properties dictionary |

