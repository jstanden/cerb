---
id: "docs-automations-events-mail-sent"
title: "mail.sent"
url: "https://cerb.ai/docs/automations/events/mail.sent/"
summary: "This page provides information about the 'mail.sent' automation events in Cerb, which are designed to execute actions after a worker sends an outgoing message. It outlines the available placeholders within the automation event dictionary, specifically focusing on the message record and its key expansion capabilities. The page notes that there are no outputs associated with these automation events."
tags: ["docs", "docs-automations"]
---
**mail.sent** [automation](/docs/automations/) [events](/docs/automations/#events) can perform actions after an outgoing message is sent by a worker.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `message_*` | record | The [message](/docs/records/types/message/) record. Supports key expansion. |

# Outputs

(none)

# Legacy behaviors

When both automations and legacy [bot](/docs/records/types/bot/) behaviors are active on the `mail.sent` event, they run in this order based on automation [priority](/docs/automations/#priority):

| Priority | Execution order |
| --- | --- |
| 0–127 | Automation runs **before** legacy behaviors |
| 128–255 | Automation runs **after** legacy behaviors |

