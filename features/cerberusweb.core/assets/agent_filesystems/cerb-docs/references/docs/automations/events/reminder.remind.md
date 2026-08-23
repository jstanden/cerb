---
id: "docs-automations-events-reminder-remind"
title: "reminder.remind"
url: "https://cerb.ai/docs/automations/events/reminder.remind/"
summary: "This page provides information about the 'reminder.remind' automation events in Cerb, which are activated when a reminder record hits its specified 'remind at' date. It details the placeholders available in the automation event dictionary, specifically highlighting the use of the `reminder_*` key for accessing the reminder record with support for key expansion. The page notes that there are no outputs associated with this event."
tags: ["docs", "docs-automations"]
---
**reminder.remind** [automation](/docs/automations/) [events](/docs/automations/#events) are triggered by a reminder record that reaches its "remind at" date.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `reminder_*` | record | The [reminder](/docs/records/types/reminder/) record. Supports key expansion. |

# Outputs

(none)

