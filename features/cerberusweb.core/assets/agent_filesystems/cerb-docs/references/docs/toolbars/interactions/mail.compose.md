---
id: "docs-toolbars-interactions-mail-compose"
title: "mail.compose"
url: "https://cerb.ai/docs/toolbars/interactions/mail.compose/"
summary: "This page provides detailed instructions on configuring the compose toolbar in Cerb for sending new emails. It guides users on how to navigate to the toolbar settings, edit the `mail.compose` record, and add interactions using the toolbar KATA. The page outlines the available placeholders, such as `worker_*`, for use in KATA, and describes the inputs and outputs for interactions, including how to handle selected text and insert snippets into the email editor."
tags: ["docs"]
---
The compose [toolbar](/docs/toolbars/) is displayed when sending a new email.

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `mail.compose`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/example:
  uri: cerb:automation:example.alert
  label: Example
  icon: bell
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

# Interactions

Caller: `cerb.toolbar.mail.compose`

### Inputs

The following `caller_params` are passed [interactions](/docs/automations/triggers/interaction.worker/):

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`selected_text`** | string | The currently selected editor text |

### Output

[Interactions](/docs/automations/triggers/interaction.worker/) can `return:` the following keys:

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`snippet`** | string | A snippet of text to insert in the editor at the cursor |

