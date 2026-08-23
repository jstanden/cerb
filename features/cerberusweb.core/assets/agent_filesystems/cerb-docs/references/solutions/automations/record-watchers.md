---
id: "solutions-automations-record-watchers"
title: "Record watchers"
url: "https://cerb.ai/solutions/automations/record-watchers/"
summary: "This page provides guidance on managing watchers for any record in Cerb, a workflow automation tool. A watcher is essentially a worker that receives notifications for all new activity on a record. Watchers can be added and managed through automations using the `record.update:` command and the `links` field. The `links` field contains a list of record tuples formatted as `type:id`, allowing watchers to be linked to specific records. To add or remove watchers, users can use automation commands such as `record.update/watcher`, where the `worker` field specifies the worker ID to link or unlink from the record."
tags: ["solutions", "solutions-automations"]
---
A [watcher](/docs/watchers/) is a [worker](/docs/workers/) who has enabled notifications for all new activity on a [record](/docs/records/).

Internally, a watcher is a worker record [linked](/docs/records/links/) to any other [record type](/docs/records/types/). Watchers can be managed by [automations](/docs/automations/) using the [record.update:](/docs/automations/commands/record.update/) command and the `links` field.

The value of the `links` field is a list of record tuples formatted as `type:id`. So to add a watcher, use `worker:id`.

A watcher link can be removed by prepending a `-` to the tuple in the list.

- [automation](#)
- [policy](#)

- 
```
start:
  record.update/watcher:
    output: updated_ticket
    inputs:
      record_type: ticket
      record_id: 123
      fields:
        links@list:
          worker:1
          worker:2
          -worker:3
```
- 
```
commands:
  record.update:
    deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
    allow@bool: yes
```

