---
id: "docs-resources"
title: "Resources"
url: "https://cerb.ai/docs/resources/"
summary: "This page provides an overview of resources in Cerb, which are shared assets utilized by automations and widgets. It explains the two main types of resources: file-based, which involve infrequently changing uploaded files, and automation-based, which dynamically generate resources by fetching and transforming live data. The page highlights the importance of resources in efficiently managing large data sets for map widgets, providing logo images and stylesheets for portals, and supplying datasets for charts and dashboards. It also details various resource types, including datasets in CSV and JSON Lines formats, fonts, images, maps, map points, and map properties, each serving specific functions within the Cerb platform."
tags: ["docs"]
---
**Resources** are shared assets used by automations and widgets. Resources can be file-based, with an uploaded file that infrequently changes, or [automation](/docs/automations/)-based, with a dynamically generated resource. For instance, an automation-based resource can fetch live data from any HTTP endpoint, transform it, and output the expected JSON format.

For instance, [map widgets](/docs/maps/) require resources that can be several megabytes large. This would be cumbersome and redundant to include within an automation. Instead, a map widget refers to a resource by name, like `map.world.countries` or `mapPoints.worldCapitalCities`, which is then loaded directly by a client's browser and cached.

Similarly, resources can provide logo images and stylesheets for [portals](/docs/portals/), and datasets for charts and [sheets](/docs/sheets/) on [dashboards](/docs/dashboards/).

 

## Resource Types

Resources have a **type** that determines where they are available.

| Type | Description |
| --- | --- |
| **Dataset (.csv)** | A precomputed dataset in the comma-separated values (CSV) format. |
| **Dataset (.jsonl)** | A precomputed dataset in the JSON Lines format. |
| **Font** | TrueType fonts (TTF) for use in image generation. |
| **Image** | An image file (e.g. PNG, JPEG, GIF, SVG). |
| **Map** | Features that describe regions in GeoJSON (e.g. countries, states, counties). These are base maps that other features are drawn on top of. |
| **Map Points** | Points of interest to display on a base map (e.g. cities, business locations). |
| **Map Properties** | Additional datasets that can be merged into a base map (e.g. election results by state/county, COVID-19 cases per country). |

