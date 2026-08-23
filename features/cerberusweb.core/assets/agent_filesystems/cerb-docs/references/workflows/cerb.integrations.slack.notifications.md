---
id: "workflows-cerb-integrations-slack-notifications"
title: "Slack Notifications"
url: "https://cerb.ai/workflows/cerb.integrations.slack.notifications/"
summary: "This page provides a comprehensive guide on integrating Cerb with Slack to send notifications to Slack channels. It covers the installation process, including the necessary requirements for both Slack and Cerb, and details on configuring the workflow within Cerb. The guide explains how to enable Slack notifications for specific groups, allowing users to receive alerts in designated Slack channels when new messages are received. Additionally, it offers a reference template for building custom Slack notification workflows, complete with configuration details and automation scripts for posting messages to Slack channels."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
  - [Requirements](#requirements)
  - [Slack](#slack)
  - [Cerb](#cerb)
    - [Configure the workflow](#configure-the-workflow)

- [Usage](#usage)
  - [Enabling Slack notifications per group](#enabling-slack-notifications-per-group)

- [Reference](#reference)

# Introduction

This workflow integrates Cerb with Slack for sending notifications to channels.

# Installation

## Requirements

[Create a Slack connected account](/solutions/integrations/slack/) if you haven't already.

## Slack

In the Slack app, enter a channel (e.g. `#testing`) and click on the triple dot menu in the top right.

Select **Edit settings**.

Select the **Integrations** tab.

Click on the **Add an App** button.

Click **Add** to the right of the **Cerb** app.

## Cerb

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Slack Notifications**.

### Configure the workflow

| Field | &nbsp; |
| --- | --- |
| **Slack Account:** | A Slack [connected account](/solutions/integrations/slack/). |

Click the **Continue** button twice.

# Usage

### Enabling Slack notifications per group

From **Search&nbsp;» Groups**, edit each group to enable Slack notifications for new messages where desirable.

Click the **Add Fieldset** button and select the **Slack Notifications** fieldset.

 

Enter a Slack **Channel:** to notify for new group messages.

 

Click the **Save Changes** button.

When a new ticket is received you'll receive a message in the Slack channel.

 

# Reference

You can build your own **Slack Notifications** workflow using this template as a reference.

Change occurrences of **cerb.integrations.slack.notifications** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.integrations.slack.notifications
  description: Notify a Slack channel about new ticket messages
  website: https://cerb.ai/workflows/cerb.integrations.slack.notifications/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core,
  config:
    chooser/slack_account_id:
      label: Slack Account:
      record_type: connected_account
      record_query: service:(slack)
      multiple@bool: no
  version: 2025-02-21T00:00:00Z
records:
  custom_fieldset/fieldset_slack_notifications:
    fields:
      name: Slack Notifications
      context: group
      owner__context: app
      owner_id@int: 0
  custom_field/field_slack_channel:
    fields:
      name: Channel
      context: group
      uri: slack_notifications_channel
      custom_fieldset_id: {{records.fieldset_slack_notifications.id}}
      type: S
      pos@int: 1
  automation/automation_postMessage:
    fields:
      name: cerb.integrations.slack.notifications.postMessage
      extension_id: cerb.trigger.mail.received
      description: Post a message to a Slack channel
      script@raw:
        start:
          outcome/ignore:
            if@bool: {{not message_id}}
            then:
              return:

          set:
            config@json: {{cerb_workflow_config('cerb.integrations.slack.notifications')|json_encode}}
            message:
              channel: {{message_ticket_group_slack_notifications_channel}}
              text@text:
                @channel SLA Notice:

                {{message_content|truncate(512)}}
              attachments:
                0:
                  color: #888888
                  footer: {{message_ticket_record_url}}
                  fields:
                    0:
                      title: Sender
                      value: {{message_sender__label}}
                      short@bool: yes
                    1:
                      title: Organization
                      value: {{message_ticket_org__label}}
                      short@bool: yes
                    2:
                      title: Ticket
                      value: {{message_ticket__label}}
                      short@bool: yes
                    3:
                      title: URL
                      value: {{message_record_url}}
                      short@bool: yes
                    4:
                      title: Group
                      value: {{message_ticket_group__label}}
                      short@bool: yes
                    5:
                      title: Bucket
                      value: {{message_ticket_bucket__label}}
                      short@bool: yes

          http.request:
            output: response
            inputs:
              # See: https://api.slack.com/methods/chat.postMessage
              url: https://slack.com/api/chat.postMessage
              method: POST
              authentication: cerb:connected_account:{{config.slack_account_id}}
              headers@text:
                Content-Type: application/json; charset=utf8
              body: {{message|json_encode}}
      policy_kata@raw:
        commands:
          http.request:
            deny/url@bool: {{inputs.url != 'https://slack.com/api/chat.postMessage'}}
            deny/method@bool: {{inputs.method != 'POST'}}
            deny/account@bool: {{inputs.authentication != 'cerb:connected_account:' ~ cerb_workflow_config('cerb.integrations.slack.notifications','slack_account_id')}}
            allow@bool: yes
  automation_event_listener/listener_mailReceived:
    fields:
      name: Slack Notifications
      event_name: mail.received
      priority@int: 100
      is_disabled: 0
      event_kata@raw:
        automation/slackNotifications:
          uri: cerb:automation:cerb.integrations.slack.notifications.postMessage
          disabled: {{not message_ticket_group_slack_notifications_channel}}
```
