---
id: "docs-toolbars-interactions-global-search"
title: "global.search"
url: "https://cerb.ai/docs/toolbars/interactions/global.search/"
summary: "This page provides information on configuring and using the global search toolbar in Cerb. It explains how to access the toolbar from the search icon on every page and details the configuration process through the 'Search » Toolbars' menu. The page includes instructions for editing the global search record and adding interactions using toolbar KATA, with an example of a ticket search interaction. It also lists available placeholders in KATA, such as `worker_*` for the active worker record. The page outlines the caller and input/output specifications for interactions related to the global search toolbar."
tags: ["docs"]
---
The global search [toolbar](/docs/toolbars/) is accessed from the search icon in the top right of every page.

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `global.search`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/ticketSearch:
  uri: cerb:automation:wgm.example.ticketSearchInteraction
  label: Ticket search
  icon: search
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

# Interactions

Caller: `cerb.toolbar.global.search`

### Inputs

(none)

### Output

(none)

