---
id: "solutions-automations-create-email-draft"
title: "Create an email draft"
url: "https://cerb.ai/solutions/automations/create-email-draft/"
summary: "This page provides a detailed guide on creating a transactional email draft in Cerb. It explains how to set up a `mail.transactional` draft, which is sent by the system rather than an individual worker and does not generate a ticket record. The guide includes a code snippet demonstrating how to create a draft with specific parameters such as the recipient's email, subject, and content. It also outlines the policy for allowing the creation of such drafts, ensuring that only drafts of the correct type are permitted. The example provided schedules the email for delivery five minutes after creation and includes a link to time-saving tips for the recipient."
tags: ["solutions", "solutions-automations"]
---
## Create a transactional email draft

A `mail.transactional` [draft](/docs/records/types/draft/) is sent by the system rather than a particular worker. This does not create a [ticket](/docs/tickets/) record.

- [automation](#)
- [policy](#)

- 
```
start:
  record.create:
    output: draft_record
    inputs:
      record_type: draft
      fields:
        type: mail.transactional
        name: Welcome to the product!
        is_queued@int: 1
        queue_delivery_date@date: 5 mins
        params:
          to: customer@cerb.example
          subject: Welcome to the product!
          content@text:
            Welcome to the product!

            Have you seen these time-saving tips?
            https://product.example/link/
```
- 
```
commands:
  record.create:
    deny/type@bool: {{inputs.record_type is not record type ('draft')}}
    allow@bool: yes
```

