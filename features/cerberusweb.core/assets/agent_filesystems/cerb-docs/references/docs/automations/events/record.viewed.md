---
id: "docs-automations-events-record-viewed"
title: "record.viewed"
url: "https://cerb.ai/docs/automations/events/record.viewed/"
summary: "This page provides information about the 'record.viewed' automation events in Cerb, which are triggered after a worker views a record profile. It details the placeholders available in the automation event dictionary, including keys for the viewed record and the current worker, both of which support key expansion. The page notes that there are no outputs associated with this event."
tags: ["docs", "docs-automations"]
---
**record.viewed** [automation](/docs/automations/) [events](/docs/automations/#events) are invoked after a [worker](/docs/workers/) views a [record](/docs/records/) profile.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `record_*` | record | The viewed [record](/docs/records/) dictionary. Supports key expansion. |
| `worker_*` | record | The current [worker](/docs/records/types/worker/) dictionary. Supports key expansion. |

# Outputs

(none)

# Legacy behaviors

When both automations and legacy [bot](/docs/records/types/bot/) behaviors are active on the `record.viewed` event, they run in this order based on automation [priority](/docs/automations/#priority):

| Priority | Execution order |
| --- | --- |
| 0–127 | Automation runs **before** legacy behaviors |
| 128–255 | Automation runs **after** legacy behaviors |

