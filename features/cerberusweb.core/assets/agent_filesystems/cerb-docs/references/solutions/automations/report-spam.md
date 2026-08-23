---
id: "solutions-automations-report-spam"
title: "Report a ticket as spam"
url: "https://cerb.ai/solutions/automations/report-spam/"
summary: "This page provides an integration guide for reporting a ticket as spam using automations in Cerb, a project management tool. The example demonstrates how to use the `cerb.commands.email.spam.train` command to report a specific ticket ID (1234) as spam and implement a policy that denies any other commands with similar functionality."
tags: ["solutions", "solutions-automations"]
---
## Using api.command:

- [automation](#)
- [policy](#)

- 
```
start:
  api.command:
    output: results
    inputs:
      name: cerb.commands.email.spam.train
      params:
        ticket_id@int: 1234
```
- 
```
commands:
  api.command:
    deny/name@bool: {{inputs.name not in ['cerb.commands.email.spam.train']}}
    allow@bool: yes
```

