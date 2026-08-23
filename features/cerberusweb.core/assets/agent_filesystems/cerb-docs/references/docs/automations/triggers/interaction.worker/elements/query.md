---
id: "docs-automations-triggers-interaction-worker-elements-query"
title: "Search Query - Interaction Form Element"
url: "https://cerb.ai/docs/automations/triggers/interaction.worker/elements/query/"
summary: "This page provides information on the 'query' element used in interaction web forms within Cerb. It explains how this element displays a search query prompt with filter autocompletion, specifically for searching records like tickets or workers. The page details the syntax for configuring the query element, including optional parameters such as the label to display above the form element, the record type for autocompletion, and whether user input is required."
tags: ["docs", "docs-automations"]
---
In [interaction](/docs/automations/triggers/interaction.worker/) web forms, a **query** element displays a search query prompt with filter autocompletion.

```
start:
  await:
    form:
      title: Search Tickets
      elements:
        query/prompt_query:
          label: Query:
          record_type: ticket
          default@text:
            status:o
```

 

# Syntax

### label:

The optional label to display above the form element.

### record\_type:

The [record type](/docs/records/types/) to use for query autocompletion. For instance, `ticket` or `worker`.

### hidden:

This form element can be conditionally hidden.

```
hidden@bool: {{not worker_is_superuser}}
```

### required@bool:

If user input is required on this element use a value of `yes`. Otherwise, omit.

