---
id: "workflows-wgm-email-groupoptinwatchers"
title: "Group (Opt-In) Watchers"
url: "https://cerb.ai/workflows/wgm.email.group_optin_watchers/"
summary: "This page provides detailed information about the 'Group (Opt-In) Watchers' workflow in Cerb, which automatically adds all workers watching a group as watchers of the ticket when a ticket is moved to (or created in) that group. It includes sections on introduction, installation, usage, and reference. The workflow can be created on Cerb version 11.0 and above. The usage section explains how those workers who are watching a group are automatically added as ticket watchers when a ticket is moved to (or created in) that group. The reference section offers guidance on creating a custom group (opt-in) watchers workflow using the provided template, with instructions on modifying the workflow identifier to suit individual needs. The page also includes a sample workflow script and details on the requirements and configuration for implementing the group (opt-in) watchers feature."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Testing with imported message](#testing-with-imported-message)

# Introduction

This workflow automatically add workers who are watching a group as watchers for tickets moved to (or created in) that group.

# Installation

Navigate to **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Empty**.

Paste the following KATA into the large text box:

```
workflow:
  name: wgm.email.group_optin_watchers
  version: 2026-05-25T20:00:00Z
  description: Automatically add group watchers as ticket watchers for new/moved tickets.
  website: https://cerb.ai/workflows/wgm.email.group_optin_watchers/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core,
records:
  automation_event_listener/listenerMailMoved:
    fields:
      name: Group (Opt-In) Watchers
      event_name: mail.moved
      priority@int: 100
      is_disabled: 0
      event_kata@raw:
        automation/addGroupWatchers:
          uri: cerb:automation:wgm.email.group_optin_watchers.mailMoved
          disabled@bool: no
  automation/automationMailMoved:
    fields:
      name: wgm.email.group_optin_watchers.mailMoved
      extension_id: cerb.trigger.mail.moved
      description: Add group watchers as ticket watchers for new/moved tickets.
      script@raw:
        start:
          # Build a list of Group Watchers to add to the Ticket
          set:
            watcher_links@list:
              {{
                ticket_group_links["cerberusweb.contexts.worker"]
                |values
                |map((v,k) => 'worker:' ~ v)
                |join('\n')
              }}
              
          # Only update if we have group members
          outcome/hasWatchers:
            if@bool: {{watcher_links is not empty}}
            then:
              record.update:
                inputs:
                  record_type: ticket
                  record_id: {{ticket_id}}
                  fields:
                    links@key: watcher_links
      policy_kata@raw:
        commands:
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
```

Click the **Continue** button three times.

You should see output like the following:

 

Click the **Save Changes** button.

# Usage

When a new ticket is created from incoming email or when an existing ticket is moved, all workers who are watching the ticket's assigned group will automatically be added as watchers to that ticket.

To watch a group, open your worker card

 

and click **Links&nbsp;» Group**. Select the groups you want to watch and click the **Save Changes** button.

## Testing with imported message

You can test this workflow by importing a sample email message. Navigate to **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Import Message** and paste the following test message:

```
From: customer@cerb.example
To: support@cerb.example
Subject: Test ticket for group watchers
Message-ID: <test-group-optin-watchers-a1b2c3@cerb.example>

This is a test message to verify that group watchers are added as watchers to new tickets.

Please move this ticket to a group with multiple members to see the workflow in action.
```

After importing, check the created ticket's watchers to verify that all group watchers have been automatically added as ticket watchers.

 
