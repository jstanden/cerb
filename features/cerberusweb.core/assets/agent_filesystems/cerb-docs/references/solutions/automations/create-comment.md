---
id: "solutions-automations-create-comment"
title: "Create a comment"
url: "https://cerb.ai/solutions/automations/create-comment/"
summary: "This page explains how to create a comment on any record using the `record.create` command in Cerb. It also provides examples of how to specify the author, target record, and other fields for the comment, as well as an example of how to implement a deny policy for the `record.create` command to only allow comments on records of type `comment`."
tags: ["solutions", "solutions-automations"]
---
You can use record.create: to create a [comment](/docs/comments/) on any [record](/docs/records/).

## Create a formatted comment as Cerb on a ticket record

- [automation](#)
- [policy](#)

- 
```
start:
  record.create/comment:
    output: new_comment
    inputs:
      record_type: comment
      fields:
        author__context: app
        author_id@int: 0
        target__context: ticket
        target_id@int: 123
        is_markdown@int: 1
        comment@text:
          This is a **comment** from an automation.
```

| Field | &nbsp; |
| --- | --- |
| `author__context:` | [record type](/docs/records/types/) of author (`app`, `role`, `group`, `worker`) |
| `target__context:` | [record type](/docs/records/types/) to comment on (`ticket`, `message`, `task`, etc.) |

- 
```
commands:
  record.create:
    deny/type@bool: {{inputs.record_type is not record type ('comment')}}
    allow@bool: yes
```

