---
id: "guides-integrations-slack-slash-commands"
title: "Send commands to Cerb automations using Slack"
url: "https://cerb.ai/guides/integrations/slack/slash-commands/"
summary: "This is a step-by-step guide to integrating Cerb with Slack using a webhook and slash commands."
tags: ["guides"]
---
# Introduction

Slack makes it very easy to interact with third-party apps and services using their _slash commands_ feature.

To demonstrate this functionality, we'll add a new chat command named **/cerb** and instruct Slack to send those messages to a webhook[1](#fn:webhook) that triggers automations in Cerb.

- [Installation](#installation)
  - [Requirements](#requirements)
  - [Slack](#slack)
  - [Cerb](#cerb)
    - [Configure the workflow](#configure-the-workflow)
    - [Fill the custom field](#fill-the-custom-field)

  - [Add the new command in Slack](#add-the-new-command-in-slack)
  - [Test the new /cerb command in Slack](#test-the-new-cerb-command-in-slack)
  - [Where to go from here](#where-to-go-from-here)
  - [References](#references)

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

Click **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Empty** and paste the following KATA into the large text box:

```
workflow:
  name: wgm.integrations.slack.bot
  version: 2026-08-11T02:23:08Z
  description: A demo of integrations with a slack bot
  website: https://cerb.ai/resources/workflows/
  requirements:
    cerb_version: >=11.0 <12.0
    cerb_plugins: cerberusweb.core,
  config:
    chooser/account:
      label: Slack Account
      record_type: connected_account
      multiple@bool: no
records:
  webhook_listener/slack:
    fields:
      name: Slack
      guid: {{random_string(40)}}
      automations_kata@raw:
        automation/slack:
          uri: cerb:automation:wgm.integrations.slack.webhook
          disabled@bool: no
    updatePolicy: name
  automation/router:
    fields:
      name: wgm.integrations.slack.webhook
      extension_id: cerb.trigger.webhook.respond
      description@text:
      script@raw:
        start:
          set/config:
            config@json: {{cerb_workflow_config('wgm.integrations.slack.bot')|json_encode}}
            
          record.search/worker:
            output: results_worker
            inputs:
              record_type: worker
              record_query: slack.username:${username} limit:1
              record_query_params:
                username: {{request_params.user_name}}
         
          decision/found:
            outcome/notfound:
              if@bool: {{results_worker.id is empty}}
              then:
                set:
                  message:
                    channel: {{request_params.channel_name}}
                    text@text:
                      Worker not found. Have you set your Slack username in your Cerb profile?
            outcome/found:
             then:
               decision/route:
                 outcome/help:
                   if@bool: {{request_params.text == "help"}}
                   then:
                    set:
                      message:
                        channel: #{{request_params.channel_name}}
                        text@text:
                          You can use the following commands:
                        attachments:
                          0:
                            color: #888888
                            fields:
                              0:
                                title: /cerb help
                                value: This help text.
                                short@bool: yes
                              1:
                                title: /cerb hello
                                value: Say hello!
                                short@bool: yes
                              2:
                                title: /cerb calendar
                                value: Respond with your next calendar event.
                 outcome/hello:
                   if@bool: {{request_params.text == "hello"}}
                   then:
                     set:
                      message:
                        channel: #{{request_params.channel_name}}
                        text@text: 
                          Hello!!
                          How are you today, {{results_worker.first_name}}?
                 outcome/key:
                   if@bool: {{request_params.text == "calendar"}}
                    then:
                      function/calendar:
                        uri: cerb:automation:wgm.integrations.slack.calendar
                        inputs:
                          worker: {{results_worker.id}}
                          channel: {{request_params.channel_name}}
                        output: results_function
        
        
         http.request:
           output: response
           inputs:
                url: https://slack.com/api/chat.postMessage
                method: POST
                authentication: cerb:connected_account:{{config.account}}
                headers@text:
                  Content-Type: application/json; charset=utf8
                body@key: message
          
      policy_kata@raw:
        commands:
          http.request:
            deny/url@bool: {{inputs.url is not prefixed ('https://slack.com/api/')}}
            deny/method@bool: {{inputs.method not in ['POST']}}
            allow@bool: yes
          function:
            deny/uri@bool: {{uri != 'cerb:automation:wgm.integrations.slack.calendar'}}
            allow@bool: yes
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('worker')}}
            allow@bool: yes
  automation/calendar:
    fields:
      name: wgm.integrations.slack.calendar
      extension_id: cerb.trigger.automation.function
      description@text:
      script@raw:
        inputs:
          record/worker:
            record_type: worker
            required@bool: yes
          text/channel:
            type: freeform
            required@bool: yes
            
        start:
          set/config:
            config@json: {{cerb_workflow_config('wgm.integrations.slack.bot')|json_encode}}
            
          record.search/event:
            output: results_calendar_event
            inputs:
              record_type: calendar_event
              record_query@text:
                calendar:(owner.worker:(id:${username}))
                startDate:(since:"now" until:"+7 days") 
                sort:startDate
                limit:1
              record_query_params:
                username: {{inputs.worker.id}}
         
          decision/events:
            outcome/found:
              if@bool: {{results_calendar_event.id is not empty}}
              then:
                set/message:
                  message:
                    channel: #{{inputs.channel}}
                    text@text:
                      Your next calendar event is:
                    attachments:
                      0:
                        color: #888888
                        footer: {{results_calendar_event.record_url}}
                        fields:
                          0:
                            title: Event
                            value: {{results_calendar_event._label}}
                            short@bool: yes
                          1:
                            title: Starts
                            value: in {{results_calendar_event.date_start|date_pretty}}
                            short@bool: yes
                          2:
                            title: Ends
                            value: in {{results_calendar_event.date_end|date_pretty}}
                            short@bool: yes
            outcome/else:
              then:
                set/message:
                  message:
                    channel: #{{inputs.channel}}
                    text@text:
                      No upcoming calendar events found.
                
                
          http.request:
            output: response
            inputs:
              url: https://slack.com/api/chat.postMessage
              method: POST
              authentication: cerb:connected_account:{{config.account}}
              headers@text:
                Content-Type: application/json; charset=utf8
              body@key: message
          
      policy_kata@raw:
        commands:
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('calendar_event')}}
            allow@bool: yes
          http.request:
            deny/url@bool: {{inputs.url is not prefixed ('https://slack.com/api/')}}
            deny/method@bool: {{inputs.method not in ['POST']}}
            allow@bool: yes
  custom_fieldset/slack:
    fields:
      name: Slack
      context: worker
      owner__context: app
      owner_id: 0
  custom_field/slackname:
    fields:
      name: Username
      context: worker
      uri: slackid
      type: S
      custom_fieldset_id: {{records.slack.id}}
      pos@int: 0
```

### Configure the workflow

| Field | &nbsp; |
| --- | --- |
| **Slack Account:** | A Slack [connected account](/solutions/integrations/slack/). |

Click the **Continue** button twice.

### Fill the custom field

Click your name in the top right corner and select "my card". Click "Edit" and then "Add Fieldset" to add the Slack fieldset if it's not already there. Then enter your Slack username in the "Username" box at the bottom.

## Add the new command in Slack

Now that we have our webhook listener and automations in place, we're ready to hook them up in Slack.

Open up your Slack App and select "Slash Commands" in the menu on the left.

 

Click "Add a New Command", name the command **/cerb**, and paste the URL from your webhook in Cerb (Search&nbsp;» Webhooks) in the "Request URL" field. Then hit the green "Save" button.

 

## Test the new /cerb command in Slack

Join one of your Slack channels and try out the new **/cerb** command.

**/cerb help** will present you with a list of available commands.

 

**/cerb hello** will greet you by name.

 

If you filled in the "Slack ID" custom field on your profile in Cerb, **/cerb calendar** will tell you the next event on your calendar.

 

## Where to go from here

Your friendly new app doesn't do much yet, but you have a great starting point with endless possibilities.

You could modify the automations we created to do anything that automations are capable of (which is a lot): add events to calendars, create reminders, add tasks, report about Cerb metrics, trigger webhooks in other services, post to social media, etc.

Using that custom field we made, automations can look up a message sender's worker record. From there, it can perform all sorts of personalized actions using the worker's tasks, calendars, and so on.

You could use our [classifiers](/docs/classifiers/) feature to support natural language in your Slack app. A classifier can convert freeform text into _"intents"_. For instance, instead of only supporting the _"hello"_ command, your app could learn the various ways people _intend_ to **say\_hello**: _hi, hello, what's up?, how are you?, hola, allo, yo, hey, etc_.

## References

1. https://en.wikipedia.org/wiki/Webhook&nbsp;[↩](#fnref:webhook)

