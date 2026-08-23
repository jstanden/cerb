---
id: "docs-automations-events-record-merge"
title: "record.merge"
url: "https://cerb.ai/docs/automations/events/record.merge/"
summary: "This page provides detailed information on the **record.merge** automation events in Cerb, which are used to allow or reject record merge requests based on specific properties such as record type, source and target IDs, and worker permissions. It explains the process that occurs after merge mapping and emphasizes that a worker must have the necessary permissions to merge records. The page outlines how policies can be implemented, such as requiring tickets to share the same group or participants before merging. It also describes the placeholders and outputs involved in the automation event dictionary, including keys like `record_type_*`, `records`, `source_ids`, `target_id`, and `worker_*`, as well as the `deny:` output key that determines whether a merge is allowed or denied."
tags: ["docs", "docs-automations"]
---
**record.merge** [automation](/docs/automations/) [events](/docs/automations/#events) can allow or reject a record merge request based on its properties (e.g. record type, records, source ids, target id, worker).

This occurs after merge mapping. A worker must still have permission to merge records in one of their roles.

This uses events KATA, and the first automation to **return:** is used.

For instance, a policy could require that tickets can only be merged if they share the same group or participants.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `record_type_*` | text | The [record type](/docs/records/types/) being merged. |
| `records` | records | An array of [record](/docs/records/) dictionaries to be merged. |
| `source_ids` | array | An array of record IDs being merged into `target_id`. |
| `target_id` | id | The destination [record](/docs/records/) ID the `source_ids` will be merged into. |
| `worker_*` | record | The current [worker](/docs/records/types/worker/) dictionary. Supports key expansion. |

# Outputs

| Key | Type | Notes |
| --- | --- | --- |
| `deny:` | string | If set, the merge is denied, and this key's value is the displayed error message. If omitted the merge is allowed. |

# Examples

Only allow admins to merge tickets:

- [automation](#)
- [event](#)

- 
```
start:
  outcome/notAdmin:
    if@bool: {{not worker_is_superuser}}
    then:
      return:
        deny: Sorry, only admins can merge tickets.
```
- 
```
automation/merge:
  uri: cerb:automation:example.merge.adminsOnly
  disabled@bool: {{record_type is not record type ('ticket')}}
```

