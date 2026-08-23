---
id: "docs-toolbars-interactions-global-menu"
title: "global.menu"
url: "https://cerb.ai/docs/toolbars/interactions/global.menu/"
summary: "This page provides instructions on configuring the global interactions toolbar in Cerb, accessible via the floating Cerb icon on every page. It guides users on navigating to the Search » Toolbars section to edit the `global.menu` record and add interactions using toolbar KATA. The page also lists available placeholders in KATA, such as `worker_*` for the active worker record, and details the caller for interactions as `cerb.toolbar.global.menu`, noting that there are no specific inputs or outputs for these interactions."
tags: ["docs"]
---
The global interactions [toolbar](/docs/toolbars/) is accessed from the floating Cerb icon in the lower right of every page.

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `global.menu`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/hello:
  label: Hello
  uri: cerb:automation:example.hello
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

# Interactions

Caller: `cerb.toolbar.global.menu`

### Inputs

(none)

### Output

(none)

