---
id: "docs-toolbars-interactions-records-worklist"
title: "records.worklist"
url: "https://cerb.ai/docs/toolbars/interactions/records.worklist/"
summary: "This page provides detailed information on configuring and customizing the `records.worklist` toolbar in Cerb. It guides users on how to navigate to the toolbar settings, edit records, and add custom interactions using KATA scripting. The page outlines available placeholders for dynamic content and explains how to override built-in functionalities with custom interactions. It also details the inputs and outputs expected during interactions, including parameters like worklist ID, record type, and selected record IDs. The page is a comprehensive resource for users looking to tailor the worklist toolbar to their specific needs in Cerb."
tags: ["docs"]
---
The `records.worklist` [toolbar](/docs/toolbars/) is displayed below [worklists](/docs/worklists/).

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `records.worklist`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/customExplore:
  label: custom explore
  icon: play-button
  uri: cerb:automation:cerb.worklist.buttons.explore
  inputs:
    open_new_tab: yes
  class: action-always-show
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `worklist_record_type` | The record type of the worklist (e.g. ticket). |
| `worklist_id` | The id of the worklist (e.g. cust\_1234). |
| `worklist_query` | The query of the worklist (e.g. status:o group:Support). |
| `worklist_query_required` | The required query of the worklist (e.g. status:o group:Support). |
| `worklist_page` | The current page of the worklist (e.g. 2). |
| `worklist_limit` | The number of records per worklist page (e.g. 25). |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

**Override** built-in functionality by naming an interaction one of these:

| Key | &nbsp; |
| --- | --- |
| `interaction/explore:` | Replace the default 'explore' button below worklists with a custom interaction. |

# Interactions

Caller: `cerb.toolbar.records.worklist`

### Inputs

The following `caller_params` are passed to the [interaction](/docs/automations/triggers/interaction.worker/):

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`worklist_id`** | string | The ID of the displayed [worklist](/docs/worklists/). |
| **`worklist_record_type`** | string | The [record type](/docs/records/types/) of the displayed [worklist](/docs/worklists/). |
| **`selected_record_ids`** | array | An array of selected record IDs in the worklist (if any). |

### Output

The caller expects no outputs.

### after:

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`refresh_worklist@bool:`** | boolean | Refresh the [worklist](/docs/worklists/) after the interaction ends |

