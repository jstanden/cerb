---
id: "docs-dashboards-widgets-automation-graph"
title: "Automation Graph - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/automation-graph/"
summary: "This page documents the Automation Graph widget in Cerb. It renders a read-only control-flow graph of an automation -- its decisions, outcomes, and loops -- which makes an unfamiliar or deeply nested script much easier to follow than reading its KATA top to bottom. The same graph appears in the automation editor's Visualization tab, and this widget surfaces it outside the editor as a card widget, a profile widget, or a workspace dashboard widget. The page covers configuration and where each variant is useful."
tags: ["docs"]
---
The **Automation: Graph** widget renders a read-only control-flow graph of an [automation](/docs/automations/) – its decisions, outcomes, and loops.

Reading an unfamiliar or deeply nested script top to bottom makes its branching hard to hold in your head. The graph shows the shape instead.

 

The same graph appears in the automation editor's **Visualization** tab. This widget surfaces it outside the editor, in three variants:

| Variant | Useful for |
| --- | --- |
| Card widget | Glancing at an automation's shape from its card, without opening the editor |
| Profile widget | A permanent panel on the automation's profile |
| Workspace widget | Putting a key automation's flow on a [dashboard](/docs/dashboards/) |

- [Configuration](#configuration)
- [Controls](#controls)
- [Related](#related)

# Configuration

Choose which [automation](/docs/records/types/automation/) to graph.

On a card or profile widget attached to an automation record, the widget resolves the automation from the record it's rendered on.

# Controls

Unlike the editor's Visualization tab, the widget has its own controls for reading a graph too large to fit at once. Most sit in a toolbar; the minimap toggle is an overlay in the bottom-right corner of the canvas, and changes icon depending on its state:

| Control | Description |
| --- | --- |
| Graph / Code | Switch between the control-flow graph and the automation's [KATA](/docs/kata/) |
| Fit | Scale the graph so every node fits, returning to the view the widget opens with |
| Focus start | Recenter on the `start:` node at 100% zoom, from whatever the current zoom is |
| Zoom in / Zoom out | Change the zoom a step at a time, within a range of 20% to 250% |
| Toggle minimap | Show or hide the minimap in the corner of the canvas |

Fit is constrained by the widget's **height**, not its width. Giving the widget a wider [dashboard](/docs/dashboards/) column won't enlarge the graph; giving it more rows will.

# Related

- [Automations](/docs/automations/#editor) – the editor, including the Visualization tab
- [Automation events](/docs/automations/#events) – for tracing what runs across automations rather than within one

