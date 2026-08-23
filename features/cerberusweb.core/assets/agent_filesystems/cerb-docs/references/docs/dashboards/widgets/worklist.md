---
id: "docs-dashboards-widgets-worklist"
title: "Worklist - Dashboard Widgets"
url: "https://cerb.ai/docs/dashboards/widgets/worklist/"
summary: "This page provides information on the Worklist - Dashboard Widget, which allows users to configure a list of records with customizable search queries and columns. The widget can display any record type, set default search queries, specify fields to include or hide, and control how many records are displayed per page."
tags: ["docs"]
---
The [worklist](/docs/worklists/) widget provides a list of records with configurable types, search queries and columns.

When selecting columns, fields from [custom fieldsets](/docs/records/types/custom_fieldset/) are split into their own sections with an all/none toggle. Changing a widget's configuration also clears its per-worker cache, so a [workflow](/docs/workflows/)-managed worklist can't keep showing a worker the previous columns or query.

 

## Configuration

Worklist widgets can be configured to display any record type. You can also set a default search query, records per page, what fields you want included and a `hidden@bool:` statement for conditional hiding.

 
