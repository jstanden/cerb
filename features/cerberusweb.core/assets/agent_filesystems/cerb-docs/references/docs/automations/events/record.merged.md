---
id: "docs-automations-events-record-merged"
title: "record.merged"
url: "https://cerb.ai/docs/automations/events/record.merged/"
summary: "This page provides information about the 'record.merged' automation events in Cerb, which are triggered after records have been merged into a target but before the source records are deleted. It details the placeholders available in the automation event dictionary, including keys for the merged record type, an array of merged record dictionaries, source record IDs, the target record ID, and the current worker dictionary. The page specifies that there are no outputs for this event."
tags: ["docs", "docs-automations"]
---
**record.merged** [automation](/docs/automations/) [events](/docs/automations/#events) trigger after a set of records have been merged into a target, but before the sources have been deleted.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `record_type_*` | text | The merged [record type](/docs/records/types/). |
| `records` | records | An array of [record](/docs/records/) dictionaries that were merged. |
| `source_ids` | array | An array of record IDs merged into `target_id`. |
| `target_id` | id | The destination [record](/docs/records/) ID the `source_ids` merged into. |
| `worker_*` | record | The current [worker](/docs/records/types/worker/) dictionary. Supports key expansion. |

# Outputs

(none)

