---
id: "workflows-cerb-email-autowatcher"
title: "Auto Watcher"
url: "https://cerb.ai/workflows/cerb.email.auto_watcher/"
summary: "This page provides detailed information about the 'Auto Watcher' workflow in Cerb, which automatically adds a worker as a watcher when they reply to a ticket. It includes sections on introduction, installation, usage, and reference. The workflow is integrated into Cerb version 11.0 and above, and can be enabled through the Cerb interface. The usage section explains how workers are automatically added as watchers when they reply to a ticket. The reference section offers guidance on creating a custom auto-watcher workflow using the provided template, with instructions on modifying the workflow identifier to suit individual needs. The page also includes a sample workflow script and details on the requirements and configuration for implementing the auto-watcher feature."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
- [Reference](#reference)

# Introduction

This workflow automatically adds a worker as a watcher when they reply to a ticket.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Auto Watcher**.

# Usage

In Cerb, navigate to any ticket profile and click the **Reply** button.

The **Watchers:** button will automatically include you as a watcher.

 

# Reference

You can build your own auto-watcher workflow using this template as a reference.

Change occurrences of **cerb.email.auto\_watcher** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.email.auto_watcher
  version: 2025-02-21T00:00:00Z
  description: Automatically add workers as a watcher when they reply to a ticket.
  website: https://cerb.ai/workflows/cerb.email.auto_watcher/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core,
records:
  automation_event_listener/listenerMailDraft:
    fields:
      name: Auto Watcher
      event_name: mail.draft
      priority@int: 100
      is_disabled: 0
      event_kata@raw:
        automation/addWatcher:
          uri: cerb:automation:cerb.email.auto_watcher.mailDraft
          disabled@bool: {{is_resumed or not draft_ticket_id or not draft_worker_id}}
  automation/automationMailDraft:
    fields:
      name: cerb.email.auto_watcher.mailDraft
      extension_id: cerb.trigger.mail.draft
      description@text:
      script@raw:
        start:
          outcome/hasTicket:
            if@bool: {{not draft_ticket_id or not draft_worker_id}}
            then:
              return:
          
          record.update:
            inputs:
              record_type: ticket
              record_id: {{draft_ticket_id}}
              fields:
                links@list:
                  worker:{{draft_worker_id}}
      policy_kata@raw:
        commands:
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
```
