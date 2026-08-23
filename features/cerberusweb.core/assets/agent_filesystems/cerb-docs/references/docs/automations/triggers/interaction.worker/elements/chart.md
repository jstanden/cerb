---
id: "docs-automations-triggers-interaction-worker-elements-chart"
title: "Chart - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/chart/"
summary: "This page provides information on the 'chart' interaction form element. It explains how this element is used to render interactive data visualizations within web forms. The page includes a detailed example of a chart configuration, showcasing how to display ticket data by group over the past year using a bar chart. It outlines the syntax for defining the chart's label, datasets, and schema, including data types, series, and axis formatting options. The page serves as a guide for users to implement and customize chart elements in their interaction forms."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **chart** element renders an interactive data visualization.

```
start:
  await:
    form:
      title: Chart
      elements:
        chart/prompt_chart:
          label: Tickets by group (past 1 year):
          datasets:
            dataQuery/tickets:
              query@text:
                type:worklist.subtotals
                of:ticket
                by:[created@month,group~20]
                query:(created:"-1 year to now")
                format:timeseries
          schema:
            data:
              type: bar
              series:
                tickets:
                  x_key: ts
              stacks:
                0@csv: tickets
            axis:
              x:
                type: timeseries
                tick:
                  format:
                    date:
                      pattern: %b %Y
                  fit@bool: no
                  multiline@bool: no
                  rotate: -90
```

 

# Syntax

### label:

The optional label to display above the form element.

### datasets:

The [datasets](/docs/dashboards/widgets/chart-kata/#datasets) to load and pass to the visualization.

| Type |
| --- |
| `automation:` |
| `dataQuery:` |
| `manual:` |

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### schema:

The [schema](/docs/dashboards/widgets/chart-kata/#chart) to render.

