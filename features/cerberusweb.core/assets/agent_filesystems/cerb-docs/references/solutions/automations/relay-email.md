---
id: "solutions-automations-relay-email"
title: "Relay email messages to workers"
url: "https://cerb.ai/solutions/automations/relay-email/"
summary: "This page provides an overview of email relay in Cerb, which enables workers to respond to messages from external email clients instead of being forced to use the web browser."
tags: ["solutions", "solutions-automations"]
---
## Using api.command:

With [email relay](/guides/mail/relaying/), workers can respond to messages from external email clients rather than their web browser.

```
start:
  api.command:
    inputs:
      name: cerb.commands.email.relay
      params:
        message_id@int: 1234
        emails@csv: kina@cerb.example, mara@cerb.example
    output: results
```
