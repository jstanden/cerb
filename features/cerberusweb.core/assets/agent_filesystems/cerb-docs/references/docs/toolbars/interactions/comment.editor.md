---
id: "docs-toolbars-interactions-comment-editor"
title: "comment.editor"
url: "https://cerb.ai/docs/toolbars/interactions/comment.editor/"
summary: "This page provides detailed instructions for configuring and utilizing the comment editor toolbar in Cerb. It explains how to navigate to the toolbar settings, edit the `comment.editor` record, and add interactions using the KATA scripting language. The page outlines available placeholders for use in KATA, such as `record_*` and `worker_*`, which provide context about the record being commented on and the active worker. It also describes the inputs and outputs for interactions with the toolbar, detailing the parameters passed to the interaction and the expected return values, such as inserting text snippets into the editor."
tags: ["docs"]
---
The [toolbar](/docs/toolbars/) displayed in the comment editor.

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `comment.editor`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/snippets:
  uri: cerb:automation:wgm.example.snippet
  icon: paste
  tooltip: Paste snippets
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `record_*` | The [dictionary](/docs/guide/developers/dictionaries/) of the [record](/docs/records/) being commented upon. |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

# Interactions

Caller: `cerb.toolbar.comment.editor`

### Inputs

The following `caller_params` are passed to the [interaction](/docs/automations/triggers/interaction.worker/):

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`record_id`** | number | The record ID being commented upon |
| **`record_type`** | string | The record type being commented upon |
| **`selected_text`** | string | The currently selected editor text |
| **`text`** | string | The full editor text |

### Output

The caller expects the following `return:` dictionary:

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`snippet`** | string | A snippet of text to insert in the editor at the cursor |

