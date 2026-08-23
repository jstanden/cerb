---
id: "docs-toolbars-interactions-mail-reply"
title: "mail.reply"
url: "https://cerb.ai/docs/toolbars/interactions/mail.reply/"
summary: "This page provides detailed instructions on configuring and using the reply toolbar in Cerb when sending an email reply. It guides users on how to navigate to the toolbar settings, specifically for `mail.reply`, and how to add interactions using the toolbar KATA. The page outlines the available placeholders for KATA, such as `message_*` and `worker_*`, which support key expansion. It also describes the interactions, including the caller parameters like `selected_text` and `text`, and the expected output, which is a text snippet to be inserted in the editor."
tags: ["docs"]
---
The reply [toolbar](/docs/toolbars/) is displayed when sending a reply to an email.

 

# Configuration

Navigate to **Search&nbsp;» Toolbars**.

Edit the record for `mail.reply`.

Add [interactions](/docs/automations/triggers/interaction.worker/) using [toolbar KATA](/docs/toolbars/#kata).

```
interaction/autoreply:
  label: Auto-Reply
  uri: cerb:automation:example.autoreply
  icon: magic
```

The following **placeholders** are available in KATA:

| Key | &nbsp; |
| --- | --- |
| `message_*` | The [message](/docs/records/types/message/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |
| `worker_*` | The active [worker](/docs/records/types/worker/) record. Supports [key expansion](/docs/guide/developers/dictionaries/#key-expansion). |

# Interactions

Caller: `cerb.toolbar.mail.reply`

### Inputs

The following `caller_params` are passed to the [interaction](/docs/automations/triggers/interaction.worker/):

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`selected_text`** | string | The currently selected editor text, if any |
| **`text`** | string | The full editor text |

### Output

The caller expects the following `return:` dictionary:

| Key | Type | &nbsp; |
| --- | --- | --- |
| **`snippet`** | string | A snippet of text to insert in the editor at the cursor |

