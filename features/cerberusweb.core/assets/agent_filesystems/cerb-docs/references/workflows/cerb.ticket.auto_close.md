---
id: "workflows-cerb-ticket-autoclose"
title: "Close Idle Tickets"
url: "https://cerb.ai/workflows/cerb.ticket.auto_close/"
summary: "This page contains an example workflow to automatically close idle tickets after a configurable period of inactivity, sending a notification to the customer upon closure. To install this workflow, users paste a specific KATA code into the Workflows interface and configure settings such as the maximum idle period and closing message. The workflow consists of two resources: an automation that checks for ticket inactivity and closes tickets that have not received a response after a specified period; and an Automation Timer that triggers this automation at set intervals, with a default schedule of every hour."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Configuration](#configuration)
- [Understanding how the auto-close workflow works](#understanding-how-the-auto-close-workflow-works)

# Introduction

When [workers](/docs/workers/) reply to [tickets](/docs/tickets/), they can use the _"waiting"_ [status](/docs/tickets/#status) to indicate that a response from the client is needed before continuing. When a client replies, the ticket status is automatically changed back to _"open"_, and notifications are sent to the owner and [watchers](/docs/watchers/).

However, sometimes a client never responds. Unless the ticket was given a specific _"reopen"_ date, it will remain in the waiting status indefinitely.

This workflow will automatically close tickets that have been waiting for a client reply for a configurable length of time. It will also send a notification to the customer to let them know their ticket has been closed.

# Installation

Click on **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» (empty)** and paste the following [KATA](/docs/kata/):

```
workflow:
  name: cerb.ticket.auto_close
  description: Automatically close idle tickets
  version: 2026-05-25T20:00:00Z
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core
  config:
    text/ticketAge:
      label: At what age should tickets be closed?
      default: -30 days
    text/closeMessage:
      label: What message should be sent upon closing?
      default: We haven't heard back from you in 30 days. This ticket has been automatically closed. You may reopen the ticket by replying to this message. This is an automated message.
records:
  automation/auto_close:
    fields:
      name: cerb.ticket.auto_close
      extension_id: cerb.trigger.automation.timer
      description: Automatically close idle tickets and send a notification to the customer.
      script@raw:
        start:
          set:
            config@json: {{cerb_workflow_config('cerb.ticket.auto_close')|json_encode}}
          
          record.search/glrxp2:
            output: results_ticket
            inputs:
              record_type: ticket
              record_query: status:w updated:"big bang to ${ticketAge}" reopen:0
              record_query_params:
                ticketAge: {{config.ticketAge}}
          
          repeat:
            each@csv: {{results_ticket|keys|join(',')}}
            as: ticketid
            do:
              record.update/close:
                output: updated_ticket
                inputs:
                  record_type: ticket
                  record_id: {{ticketid}}
                  fields:
                    status: c
              record.create/customerMessage:
                output: new_draft
                inputs:
                  record_type: draft
                  fields:
                    type: mail.transactional
                    params:
                      to: {{results_ticket[ticketid].initial_message_sender_email}}
                      subject: {{results_ticket[ticketid].subject}}
                      content: {{config.closeMessage}}
                    is_queued: 1
                    queue_delivery_date@date: now
              record.create/comment:
                output: new_comment
                inputs:
                  record_type: comment
                  fields:
                    author__context: app
                    author_id@int: 0
                    comment: Ticket closed due to idle timeout. A notification has been sent to the customer.
                    target__context: ticket
                    target_id@int: {{ticketid}}
      policy_kata@raw:
        commands:
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('comment','draft')}}
            allow@bool: yes
  automation_timer/auto_closeTimer:
    fields:
      name: cerb.timer.auto_close
      is_disabled@int: 0
      is_recurring@int: 1
      last_ran_at@int: 0
      next_run_at@int: 1738018800
      recurring_patterns@text:
        # Every hour
        0 * * * *
        
      recurring_timezone: America/Toronto
      automations_kata@raw:
        automation/zea2sx:
          uri: cerb:automation:cerb.ticket.auto_close
          disabled@bool: no
```

# Configuration

When creating the workflow, you will be prompted for the max idle period before a ticket is automatically closed as well as the message to send to customers upon closing.

 

You can update the configuration at any time by opening the workflow profile and clicking "Edit Configuration"

 

# Understanding how the auto-close workflow works

We just imported two resources. They work together to implement the auto-close functionality.

 

The **cerb.ticket.auto\_close** automation checks if a ticket has been waiting for more than 30 days without a client response. If so, it closes it, sends the client a notification, by email and leaves a comment on the ticket.

**cerb.timer.auto\_close** is an Automation Timer. It will trigger `cerb.ticket.auto_close` to run at whatever custom schedule you have set. The default is every hour.

