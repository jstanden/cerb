---
id: "workflows-cerb-autoresponder"
title: "Auto Responder"
url: "https://cerb.ai/workflows/cerb.auto_responder/"
summary: "This page provides a comprehensive guide on setting up and using the Auto Responder workflow in Cerb, which automatically sends confirmation emails to clients when they open new tickets. It includes detailed instructions on installation, which is built into Cerb 11.0+, and usage, such as creating snippet templates for auto-response emails, enabling automatic responses for different groups, and testing the auto responder. The guide also covers how to suppress auto responses to automated senders by detecting message headers and using mail.filter automation. Additionally, it offers a reference section for building custom auto-responder workflows, complete with a template and detailed configuration options."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Snippet templates](#snippet-templates)
  - [Enabling automatic responses per group](#enabling-automatic-responses-per-group)
  - [Test the auto responder](#test-the-auto-responder)
  - [Suppressing auto responses to automated senders](#suppressing-auto-responses-to-automated-senders)

- [Reference](#reference)

# Introduction

It's a common practice in professional environments to send an automatic response to new messages. This acknowledges that you received them, and it lets you set expectations up front about office hours and response times. You can note the obligations of a service level agreement if the client has one, and if they don't you can briefly make the case for purchasing one.

Most email management applications support automatic responses in some form. The simplest feature just responds with a fixed message like _"Thanks for your message. We'll respond soon."_ or _"I'm out of the office this week and will return next Monday"._ Sophisticated implementations, like Cerb, can send highly personalized automatic responses by considering everything you know about the client and their organization, their history, your team's schedules, your current workload, etc. Cerb can even predict a client's intent and send them suggestions before you've even read their message.

This workflow automatically sends a confirmation email to clients when they open a new ticket.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Auto Responder**.

# Usage

### Snippet templates

The workflow creates a **Ticket Auto-Response** snippet for the default auto-response email template. You can create as many ticket-based snippets as you like for different groups in **Search&nbsp;» Snippets**.

### Enabling automatic responses per group

From **Search&nbsp;» Groups**, edit each group to enable automatic responses to new tickets where desirable.

Click the **Add Fieldset** button and select the **Auto-Responder** fieldset.

 

Check the **Enabled** box and select the snippet to use for the group.

 

Click the **Save Changes** button.

### Test the auto responder

You can send a new message into Cerb from your normal email client, or paste a test message from **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Import**

```
From: customer@cerb.example
To: demo@cerb.example
Subject: This is a demo ticket

This is a demo ticket
```

You'll see an auto-response queued for delivery on new tickets.

 

Automatic responses are queued so they can be delivered efficiently without impacting email parser performance.

### Suppressing auto responses to automated senders

The workflow will attempt to use message headers to detect automated senders to avoid sending them an automated response. It will also avoid sending an automated response to banned or defunct senders, or mailboxes that start with `mailer-daemon@`, `postmaster@`, `noreply@`, or `no-reply@`.

You can use a [mail.filter](/docs/automations/events/mail.filter/) automation to add an `Auto-Submitted: auto-generated` email header to inbound messages that should not receive an automatic response.

# Reference

You can build your own auto-responder workflow using this template as a reference.

Change occurrences of **cerb.auto\_responder** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.auto_responder
  version: 2025-05-09T00:00:00Z
  description: Send an automatic response when new tickets are opened
  website: https://cerb.ai/workflows/cerb.auto_responder/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core,
  config:
    picklist/keep_copy:
      label: Save a copy of the auto-reply on the ticket
      options@csv: yes, no
      default: yes
      multiple@bool: no

records:
  custom_fieldset/fieldset_auto_responder:
    fields:
      name: Auto-Responder
      context: group
      owner__context: app
      owner_id@int: 0
  custom_field/field_auto_responder_enabled:
    fields:
      name: Enabled
      context: group
      uri: auto_responder_enabled
      custom_fieldset_id: {{records.fieldset_auto_responder.id}}
      type: C
      pos@int: 1
  custom_field/field_auto_responder_snippet:
    fields:
      name: Template
      context: group
      uri: auto_responder_snippet
      custom_fieldset_id: {{records.fieldset_auto_responder.id}}
      type: L
      pos@int: 2
      params:
        context: snippet

  snippet/snippet_autoresponder:
    updatePolicy@csv:
    fields:
      title: Ticket Auto-Response
      context: cerberusweb.contexts.ticket
      owner__context: app
      owner_id: 0
      content@raw:
        Thanks for contacting us! We'll get back to you as soon as possible.

        Reference #: {{mask}}
        Subject: {{subject}}

  automation/automation_autoresponder:
    fields:
      name: cerb.autoResponder.mailReceived
      extension_id: cerb.trigger.mail.received
      script@raw:
        start:
          set/config:
            config@json: {{cerb_workflow_config('cerb.auto_responder')|json_encode}}
          outcome/validate:
            if@bool:
              {{
                not message_ticket_group_auto_responder_enabled
                or not message_ticket_group_auto_responder_snippet_id
              }}
            then:
              return:

          kata.parse:
            output: results
            inputs:
              kata:
                template: {{message_ticket_group_auto_responder_snippet_content}}
              dict@json:
                {% do message_ticket_ %}
                {{cerb_placeholders_list('message_ticket_', '')|json_encode}}

          record.create:
            output: new_draft
            inputs:
              record_type: draft
              fields:
                name: Auto-Response
                type: ticket.reply
                ticket_id: {{message_ticket_id}}
                is_queued: 1
                queue_delivery_date@date: 5 mins
                to: {{message_sender_address}}
                params:
                  to: {{message_sender_address}}
                  headers:
                    In-Reply-To@optional: {{message_headers['in-reply-to']}}
                    Auto-Submitted: auto-replied
                  content: {{results.template}}
                  is_autoreply@optional: {{'no' == config.keep_copy ? 1 : null}}
      policy_kata@raw:
        commands:
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('draft')}}
            allow@bool: yes

  automation_event_listener/listener_mail_received:
    fields:
      name: Email Auto-Responder
      event_name: mail.received
      priority: 10
      is_disabled: 0
      event_kata@raw:
        automation/autoresponder:
          uri: cerb:automation:cerb.autoResponder.mailReceived
          # Exclude automated senders
          disabled@bool:
            {{
              not is_new_ticket
              or not message_ticket_group_auto_responder_enabled
              or not message_ticket_group_auto_responder_snippet_id
              or message_sender_is_banned
              or message_sender_is_defunct
              or message_sender_address is pattern (
                'mailer-daemon@*',
                'postmaster@*',
                'noreply@*',
                'no-reply@*',
              )
              or message_headers['auto-submitted']
              or message_headers['x-autogenerated']
              or message_headers['x-autoreply']
              or message_headers['x-autoreply-from']
              or message_headers['x-autorespond']
              or 'auto_reply' == message_headers['x-precedence']
              or 'auto_reply' == message_headers['preference']
              or 'bulk' == message_headers['precedence']
              or message_ticket_subject is pattern (
                '*out of the office*',
                '*out of office*',
                '*auto response*',
                '*autoreply*',
              )
            }}
```
