---
id: "guides-packages-create-records"
title: "Create a reusable set of related records in a single package"
url: "https://cerb.ai/guides/packages/create-records/"
summary: "This page provides a comprehensive guide on creating reusable packages of related records in Cerb. It explains how to include various records such as tickets, messages, senders, and attachments in a single package, and how these records can be interconnected using unique identifiers (UIDs). The guide details the structure of a package in JSON format, highlighting the use of UIDs for linking records, setting custom fields, and creating links between records. It also covers the modification of automation events and toolbars within packages. An example is provided to illustrate the creation of a new ticket with associated messages and attachments, demonstrating the use of placeholders and scripting syntax for dynamic content. This approach streamlines the process of creating complex records and enhances automation capabilities in Cerb."
tags: ["guides"]
---
 

- [Including records in packages](#including-records-in-packages)
  - [The record object](#the-record-object)
  - [Using UIDs](#using-uids)
  - [Using \_exclude](#using-_exclude)
  - [Custom fields](#custom-fields)
  - [Links](#links)
  - [Automation events](#automation-events)
  - [Toolbars](#toolbars)

- [Examples](#examples)
  - [Ticket](#ticket)

# Including records in packages

In the [build a new packages](/guides/packages/building/) guide you learned that a package can include any number of records.

If you wanted to create a new ticket from the API, you would have to create several different records and link them all together: a ticket, a message, a sender, the sender's organization, attachments, comments, etc.

With a package, you can include all of these records in a single package and define how they relate to each other. You can also use a package as a template to create new tickets by only changing a few details, like the subject and sender. This is particularly useful when combined with bots for automation.

## The record object

Packages are in JSON format. At the top-level of the package object you'll find the `records` key:

```
{
  "package": {
    "name": "Example package"
  }
  "records": []
}
```

This key holds an array of record objects. Each record object has two required keys:

```
{
  "uid": "example_uid",
  "_context": "record_type"
}
```

- **uid** is a unique identifier for this record that can be referenced elsewhere in the package. It will be replaced with the real record ID when the package is imported.
- **\_context** is the type of record: `attachment`, `comment`, `ticket`, `task`, etc.

The record object can also contain any number of additional keys based on the available fields. For instance, a `ticket` record has keys like `subject`, `mask`, `importance`, etc.

## Using UIDs

When you create a record like a `message`, it requires a `ticket_id` key. If you create the ticket and the message in the same package, you don't have any idea what the ticket's eventual ID would be.

This is the purpose of UIDs. Each UID has a placeholder in the format `{{{uid.<uid-name>}}}`. You can use this placeholder as the value of any key in a record object:

```
"records": [
  {
    "uid": "ticket_001",
    "_context": "ticket",
    "subject": "This is a new ticket"
  },
  {
    "uid": "message_001",
    "_context": "message",
    "ticket_id": "{{{uid.ticket_001}}}"
  }
]
```

When this package is imported, the message would be added to the ticket `ticket_001` from the same package.

Cerb will handle all dependencies between UIDs for you – you don't have to define records in any particular order. The message could be defined first and it would still be created after the ticket is created.

## Using \_exclude

Records can be conditionally included or excluded during package import using the `_exclude` key. This key accepts scripting syntax that evaluates to a boolean value:

```
{
  "uid": "example_record",
  "_context": "record_type",
  "_exclude": "{{% if some_condition %}}true{{% endif %}}",
  "name": "Example Record"
}
```

When the `_exclude` condition evaluates to `true`, the record will be excluded during package import.

This is particularly useful when combined with package prompts to allow workers to choose which records to import. For example:

```
{
  "configure": {
    "prompts": [
      {
        "type": "picklist",
        "label": "Dataset:",
        "key": "prompt_dataset",
        "params": {
          "options": {
            "Open Tickets": "tickets_open",
            "My Tasks": "tasks_my"
          }
        }
      }
    ]
  },
  "records": [
    {
      "uid": "widget_open_tickets",
      "_context": "workspace_widget",
      "_exclude": "{{% if prompt_dataset != 'tickets_open' %}}true{{% endif %}}",
      "label": "Open Tickets"
    },
    {
      "uid": "widget_my_tasks",
      "_context": "workspace_widget", 
      "_exclude": "{{% if prompt_dataset != 'tasks_my' %}}true{{% endif %}}",
      "label": "My Tasks"
    }
  ]
}
```

In this example, based on the worker's dataset selection in the prompt, only one widget record will be imported. If they select "Open Tickets" then the `widget_my_tasks` record will be excluded, and vice versa.

## Custom fields

You can set a custom field by including a key with the format `custom_<id>`, where `<id>` is the ID of the custom field. These IDs can be found from **Search&nbsp;» Custom Fields** in Cerb by adding the **ID** column to the worklist.

```
{
  "uid": "example_record",
  "_context": "record_type",
  "custom_1": "Some text",
  "custom_2": 1234,
  "custom_3": ["Option 1", "Option 2"]
}
```

## Links

You can create links between records by including a `links` key in your record object. The value is an array of `context:id` pairs.

```
{
  "uid": "example_record",
  "_context": "record_type",
  "links": [
    "ticket:123",
    "org:456"
  ]
}
```

## Automation events

Automation event records are modified but not created. You can use the `events` key to append to an event by name.

```
{
  "package": {
  },
  "events": [
    {
      "event": "mail.draft.validate",
      "kata": "automation/profanityCheck:\n uri: cerb:automation:wgm.reply.validation.profanity\n inputs:\n draft@key: draft_id\n\n"
    }
  ]
}
```

## Toolbars

Toolbar records are modified but not created. You can use the `toolbars` key to append to a toolbar by name.

```
{
  "package": {
  },
  "toolbars": [
    {
      "toolbar": "mail.read",
      "kata": "interaction/sentiment:\n label: Sentiment\n uri: cerb:automation:wgm.example.comprehend.sentiment\n icon: scale-classic\n inputs:\n message@key: message_id\n\n"
    }
  ]
}
```

# Examples

Let's look at some working examples for different record types.

## Ticket

This package creates a new ticket with one message. The message has both plaintext and HTML versions, and an image attachment:

```
{
  "package": {
    "name": "New ticket",
    "revision": 1,
    "requires": {
      "cerb_version": "9.1.4",
      "plugins": []
    },
    "configure": {
      "prompts": [],
      "placeholders": []
    }
  },
  "records": [
    {
      "uid": "ticket_new",
      "_context": "ticket",
      "group_id": "{{{default.group_id}}}",
      "bucket_id": "{{{default.bucket_id}}}",
      "subject": "This is a new ticket",
      "importance": 50,
      "status": "open",
      "created": "{{{'now'|date('U')}}}",
      "updated": "{{{'now'|date('U')}}}",
      "participants": "customer@cerb.example",
      "org": "Example, Inc."
    },
    {
      "uid": "attachment_html_part",
      "_context": "attachment",
      "name": "original_message.html",
      "mime_type": "text/html",
      "attach": [
        "message:{{{uid.message_1}}}"
      ],
      "content": "This is the <b>HTML</b> version of the message."
    },
    {
      "uid": "attachment_image",
      "_context": "attachment",
      "name": "cerby.png",
      "mime_type": "image/png",
      "attach": [
        "message:{{{uid.message_1}}}"
      ],
      "content": "data:image/png;base64,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"
    },
    {
      "uid": "message_1",
      "_context": "message",
      "ticket_id": "{{{uid.ticket_new}}}",
      "created": "{{{'now'|date('U')}}}",
      "is_outgoing": 0,
      "sender": "customer@cerb.example",
      "response_time": "0",
      "hash_header_message_id": null,
      "headers": "To: support@cerb.example\r\nFrom: customer@cerb.example\r\nSubject: This is a new ticket\r\nX-Mailer: Cerb",
      "content": "This is the plaintext version of the message.",
      "html_attachment_id": "{{{uid.attachment_html_part}}}"
    }
  ]
}
```

The `group_id` and `bucket_id` keys use special placeholders from the `default` object. You could also set these to specific ID values, or use a prompt to ask for them at import time.

The `created` and `updated` keys use [scripting syntax](/docs/scripting/) to create a Unix timestamp for the current time.

You'll notice on keys like `org` and `participants` on the ticket that we're just providing values and not IDs. Cerb will figure out for you if these are new or existing records.

In the `attachment` records, there's an `attach` key with an array of `context:id` pairs to link the file to. We use the placeholder for the message's UID. You could also use a comment, or anything else.

On the HTML attachment we provide text in the `content` key. For the image attachment, we prefix the content with `data:image/png;base64,` to let Cerb know that we're proving binary content with base64 encoding. That will be converted back into binary for us when the file is created.

In the `html_message_id` key on the message we link the attachment for the HTML version of the message. This is optional, and the message will just be in plaintext otherwise.

After you [import the package](/guides/packages/importing/), the ticket looks looks like:

 
