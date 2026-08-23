---
id: "docs-automations-triggers-interaction-worker-elements-map"
title: "Map - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/map/"
summary: "This page provides detailed information on the 'map' interaction form element in Cerb, which is used to display interactive maps within web forms. It explains how the map element utilizes map KATA to return selected regions or points, such as displaying the geographical location of an IP address with a pin on a map. The page includes an example configuration for setting up a map with specific parameters like resource URI, projection type, scale, center coordinates, and point data. Additionally, it covers syntax details, including the optional requirement for user input on the map element."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **map** element displays an interactive [map](/docs/maps/) using map [KATA](/docs/kata/) and returns the selection region(s) or point(s).

For instance, an interaction that displays the geographical location of an IP address can drop a pin on a map prompt.

```
start:
  await:
    form:
      elements:
        map/respond_map:
          resource:
            uri: cerb:resource:map.world.countries
          projection:
            type: mercator
            scale: 350
            center:
              latitude: 47.0416
              longitude: 19.6887
          points:
            size:
              default: 5
            data:
              point/berlin:
                latitude: 52.5246
                longitude: 13.4033
                properties:
                  name: Berlin
```

 

# Syntax

The maps element supports all of the functionality from [maps KATA](/docs/maps/#maps-kata).

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

