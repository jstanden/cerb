---
id: "workflows-cerb-sla"
title: "Service Level Agreements"
url: "https://cerb.ai/workflows/cerb.sla/"
summary: "This page provides a comprehensive guide on implementing and managing Service Level Agreements (SLAs) within Cerb. It covers the introduction, installation, and detailed usage instructions for adding and assigning SLA plans to organizations, sending test messages, and integrating SLA widgets into ticket profiles. The guide also explains how to update SLA hours, sort work by SLA, and manage the SLA deadline lifecycle. Additionally, it includes related workflows to automate organization assignment and prioritize tickets nearing SLA deadlines. The reference section offers a template for creating custom SLA workflows, ensuring users can tailor the system to their specific needs."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Adding SLA plans](#adding-sla-plans)
  - [Assigning SLA plans to organizations](#assigning-sla-plans-to-organizations)
  - [Send a test message from that organization](#send-a-test-message-from-that-organization)
  - [Add the SLA widget to ticket profiles](#add-the-sla-widget-to-ticket-profiles)
  - [Updating SLA hours](#updating-sla-hours)
  - [Sorting work by SLA](#sorting-work-by-sla)
  - [SLA deadline lifecycle](#sla-deadline-lifecycle)

- [Related workflows](#related-workflows)
- [Reference](#reference)

# Introduction

This workflow enforces Service Level Agreements (SLA) for tickets from organizations.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Service Level Agreements**.

# Usage

### Adding SLA plans

An **SLA plan** contains an availability calendar and a response time target.

You can manage SLA plans from **Search&nbsp;» Sla Plans**.

A default **Priority** plan has been created for you, along with a business hour calendar and 2-hour response time target.

 

### Assigning SLA plans to organizations

Navigate to **Search&nbsp;» Organizations**.

Edit an organization to assign an SLA plan to it.

Click the **Add Fieldset** button and select **SLA**.

 

Select an SLA plan. An optional expiration date is available if you sell annual subscriptions to priority support. Otherwise you can set this to a large number like `+10 years`.

 

Click the **Save Changes** button to save the organization record.

### Send a test message from that organization

You can simulate an inbound email in MIME format from **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Import**.

```
From: Maria Vasquez <maria.vasquez@baston.example>
To: support@cerb.example
Subject: Issues with email notifications not sending
Message-ID: <54321@baston.example>
Date: Fri, 18 Oct 2024 12:05:47 +0200
MIME-Version: 1.0
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: 8bit

Hello Support Team,

We're currently having an issue with our email notifications not being sent out. Our team relies on these notifications for critical updates, and they've stopped being delivered in the past couple of days. I've checked the spam folders, and nothing is there either.

Is this a known issue? Could you please advise on how we can fix this as soon as possible?

Thank you for your help!

Best regards,  
Maria Vasquez  
IT Support  
Baston Inc.
```

### Add the SLA widget to ticket profiles

Navigate to the ticket profile page for your test message above from **Search&nbsp;» Tickets**.

Click on the **Add Widget** button at the top of the profile page.

Select **SLA Deadline** from the widget **Library** tab and click the **Create** button.

You can drag the new widget into your preferred placement from the drag handle in its top right corner when hovering over it.

 

The widget will change colors when a ticket is overdue.

 

### Updating SLA hours

From **Search&nbsp;» SLA Plans**, edit a plan's calendar to update the service operating hours.

You can add events and recurring events (like office holidays) to block out availability.

### Sorting work by SLA

You can filter and sort ticket worklists by SLA:

```
sla.deadline:!null sort:sla.deadline
```

### SLA deadline lifecycle

When a worker replies to a ticket with an SLA deadline and a non-open status, it will clear the deadline.

When a participant responds to the ticket without an existing SLA deadline, a new deadline will be added to the ticket.

# Related workflows

You can enable the [Sender Org By Hostname](/workflows/cerb.email.org_by_hostname/) workflow to automatically add organizations to new contacts by email hostname. This will ensure they receive the appropriate SLA deadline.

Use the [Auto Dispatcher](/workflows/cerb.auto_dispatcher/) workflow to give priority assignment to tickets with an approaching SLA deadline.

# Reference

You can build your own Service Level Agreement workflow using this template as a reference.

Change occurrences of **cerb.sla** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.sla
  version: 2025-02-21T00:00:00Z
  description: Enforce Service Level Agreements (SLA) for tickets from organizations
  website: https://cerb.ai/workflows/cerb.sla/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core
records:
  custom_record/sla_plan:
    fields:
      name: SLA Plan
      name_plural: SLA Plans
      uri: sla_plan
      params:
        owners:
          contexts@csv: cerberusweb.contexts.app
  custom_field/sla_plan_calendar:
    fields:
      name: Calendar
      context: sla_plan
      uri: calendar
      type: L
      pos: 1
      params:
        context: calendar
  custom_field/sla_plan_target_response_time:
    fields:
      name: Target Response Time
      context: sla_plan
      uri: target_response_time
      type: S
      pos: 2
  custom_fieldset/fieldset_org_sla:
    fields:
      name: SLA
      context: org
      owner__context: app
      owner_id@int: 0
  custom_field/fieldset_org_sla_plan:
    fields:
      name: Plan
      context: org
      uri: sla_plan
      type: L
      pos: 1
      custom_fieldset_id: {{records.fieldset_org_sla.id}}
      params:
        context: sla_plan
  custom_field/fieldset_org_sla_expires:
    fields:
      name: Expires
      context: org
      uri: sla_expires
      type: E
      pos: 2
      custom_fieldset_id: {{records.fieldset_org_sla.id}}
  custom_fieldset/fieldset_ticket_sla:
    fields:
      name: SLA
      context: ticket
      owner__context: workflow
      owner_id@int: {{workflow_id}}
  custom_field/fieldset_ticket_sla_deadline:
    fields:
      name: Deadline
      context: ticket
      uri: sla_deadline
      type: E
      pos: 1
      custom_fieldset_id: {{records.fieldset_ticket_sla.id}}
  calendar/calendar_priority:
    fields:
      name: SLA Priority Schedule
      owner__context: workflow
      owner_id: {{workflow_id}}
      timezone@text:
      params:
        manual_disabled: 0
        sync_enabled: 0
        start_on_mon: 1
        hide_start_time: 0
        color_available: #a0d95b
        color_busy: #c8c8c8
  calendar_recurring_event/recur_event_priority_available:
    updatePolicy@csv:
    fields:
      name: Available
      calendar_id: {{records.calendar_priority.id}}
      is_available@int: 1
      tz: America/Los_Angeles
      event_start: 8am
      event_end: 6pm
      recur_start: 0
      recur_end: 0
      patterns: Weekdays
  sla_plan/priority:
    fields:
      name: Priority
      owner__context: app
      owner_id@int: 0
      calendar: {{records.calendar_priority.id}}
      target_response_time: 2 hours
  automation/automation_set_ticket_deadline:
    fields:
      name: cerb.sla.mailReceived.addDeadline
      extension_id: cerb.trigger.mail.received
      description: When the sender org of a new ticket has an SLA, add a deadline in business hours using a calendar
      script@raw:
        start:
          # Calculate if the org has an SLA plan and the ticket has no deadline
          outcome/validate:
            if@bool:
              {{
                message_ticket_sla_deadline
                or not message_ticket_org_sla_plan_id
                or not message_ticket_org_sla_plan_calendar_id
                or not message_ticket_org_sla_plan_target_response_time
              }}
            then:
              return:

          set:
            sla_deadline@text:
              {{
                cerb_calendar_get_relative_date(
                  message_ticket_org_sla_plan_calendar_id,
                  message_ticket_org_sla_plan_target_response_time
                )|date('r')
              }}

          outcome/hasDeadline:
            if@bool: {{sla_deadline}}
            then:
              record.update/ticket:
                output: updated_ticket
                inputs:
                  record_type: ticket
                  record_id: {{message_ticket_id}}
                  fields:
                    sla_deadline@date: {{sla_deadline}}
      policy_kata@raw:
        commands:
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
  automation_event_listener/listener_mail_received:
    fields:
      name: Service Level Agreements
      event_name: mail.received
      priority: 1
      is_disabled: 0
      event_kata@raw:
        automation/assign:
          uri: cerb:automation:cerb.sla.mailReceived.addDeadline
          # Skip when sender has no org assigned
          disabled@bool:
            {{
              message_ticket_sla_deadline
              or not message_ticket_org_sla_plan_id
              or not message_ticket_org_sla_plan_calendar_id
              or not message_ticket_org_sla_plan_target_response_time
            }}
  automation/automation_reply_clear_sla_deadline:
    fields:
      name: cerb.sla.mailSent.clearDeadline
      extension_id: cerb.trigger.mail.sent
      description@text:
      script@raw:
        start:
          # Only if we have an SLA deadline
          outcome/validate:
            if@bool:
              {{
                not message_ticket_sla_deadline
                or not message_worker_id
              }}
            then:
              return:

          # Clear the SLA deadline on worker replies
          record.update/ticket:
            output: updated_ticket
            inputs:
              record_type: ticket
              record_id: {{message_ticket_id}}
              fields:
                sla_deadline@int: 0
      policy_kata@raw:
        commands:
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
  automation_event_listener/listener_mail_sent:
    fields:
      name: Service Level Agreements
      event_name: mail.sent
      priority: 1
      is_disabled: 0
      event_kata@raw:
        automation/assign:
          uri: cerb:automation:cerb.sla.mailSent.clearDeadline
          # Only if we have an SLA deadline and it's not an auto-reply
          disabled@bool:
            {{
              not message_ticket_sla_deadline
              or not message_worker_id
            }}
  package/package_ticket_sla:
    fields:
      name: SLA Deadline
      description: Display the SLA deadline for the current ticket
      point: profile_widget:ticket
      uri: cerb_profile_widget_sla_ticket_deadline
      package_json@raw:
        {
          "package": {
            "name": "SLA Ticket Deadline",
            "revision": 1,
            "requires": {
              "cerb_version": "11.0.0",
              "plugins": [

              ]
            },
            "library": {
              "name": "SLA Ticket Deadline",
              "uri": "cerb_profile_widget_sla_ticket_deadline",
              "description": "Display the SLA deadline for the current ticket",
              "point": "profile_widget:ticket"
            },
            "configure": {
              "placeholders": [

              ],
              "prompts": [
                {
                  "type": "chooser",
                  "label": "Profile Dashboard",
                  "key": "profile_tab_id",
                  "hidden": true,
                  "params": {
                    "context": "cerberusweb.contexts.profile.tab",
                    "single": true,
                    "query": ""
                  }
                }
              ]
            }
          },
          "records": [
            {
              "uid": "profile_widget_sla_deadline",
              "_context": "cerberusweb.contexts.profile.widget",
              "name": "Service Level Agreement (SLA)",
              "extension_id": "cerb.profile.tab.widget.sheet",
              "profile_tab_id": "{{{profile_tab_id}}}",
              "pos": 0,
              "width_units": 4,
              "zone": "sidebar",
              "extension_params": {
                  "data_query": "type:worklist.records\r\nof:ticket\r\nexpand: [customfields,org_customfields]\r\nquery:(\r\n id:{{record_id}}\r\n limit:1\r\n sort:[id]\r\n)\r\nformat:dictionaries",
                  "cache_secs": "",
                  "placeholder_simulator_kata": "",
                  "sheet_kata": "layout:\r\n style: fieldsets\r\n headings@bool: no\r\n paging@bool: no\r\n title_column: org_sla_plan_id\r\n colors:\r\n reds5@csv: #a50f15, #de2d26, #fb6a4a, #fcae91, #fee5d9\r\ncolumns:\r\n card/org_sla_plan_id:\r\n params:\r\n text_size@raw: {{sla_deadline < 'now'|date('U') ? '225%' : '150%'}}\r\n underline@bool: no\r\n bold@bool: yes\r\n text_color@raw: {{sla_deadline < 'now'|date('U') ? 'reds5:2' : ''}}\r\n icon:\r\n image_template@raw: {{sla_deadline < 'now'|date('U') ? 'warning-sign' : ''}}\r\n text/sla_deadline:\r\n params:\r\n bold@raw: {{sla_deadline < 'now'|date('U') ? 'yes' : ''}}\r\n text_color@raw: {{sla_deadline < 'now'|date('U') ? 'reds5:2' : ''}}\r\n value_template@raw:\r\n Due: {{sla_deadline|date('D, d M Y h:ia T')}} ({{sla_deadline|date_pretty}})",
                  "toolbar_kata": ""
              },
              "options_kata": "hidden@bool: {{not record_sla_deadline}}"
            }
          ]
        }
```
