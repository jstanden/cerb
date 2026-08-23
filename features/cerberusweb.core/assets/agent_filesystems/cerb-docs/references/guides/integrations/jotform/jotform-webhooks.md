---
id: "guides-integrations-jotform-jotform-webhooks"
title: "Send JotForm responses to Cerb bots using webhooks"
url: "https://cerb.ai/guides/integrations/jotform/jotform-webhooks/"
summary: "This webpage provides a comprehensive guide on integrating JotForm with Cerb using webhooks. It details the process of creating a bot in Cerb to handle JotForm submissions, setting up a webhook listener in Cerb, and configuring JotForm to send data to Cerb via webhooks. The guide includes step-by-step instructions for creating and testing the integration, allowing users to automate actions such as adding comments or creating records in Cerb based on form submissions. It emphasizes the advantages of using webhooks over email for handling form responses, particularly in terms of data parsing and automation capabilities."
tags: ["guides"]
---
# Introduction

JotForm[1](#fn:jotform) is an interactive form builder for surveys, contact forms, etc.

By default it sends new form responses to email. Using webhooks, you can also trigger Cerb bot behaviors when someone fills out the form.

This guide demonstrates how to configure webhooks for integration between JotForm and Cerb.

- [Creating the bot in Cerb](#creating-the-bot-in-cerb)
- [Understanding how the behavior works](#understanding-how-the-behavior-works)
- [Creating a webhook listener in Cerb](#creating-a-webhook-listener-in-cerb)
- [Sending the webhook from JotForm](#sending-the-webhook-from-jotform)
- [Testing the behavior](#testing-the-behavior)
- [References](#references)

# Creating the bot in Cerb

Navigate to **Search&nbsp;» Bots**.

- **Name:** JotForm Bot
- **Owner:** Cerb
- **Status:** Enabled
- **Image:** `http://webtoolswiki.com/wp-content/uploads/2014/12/jotform1.png`

 

Click the **Save Changes** button.

In the yellow notification above the worklist, click on **JotForm Bot**.

On the bot's card popup, click the **Behaviors** button.

Click on the **(+)** icon above the behaviors worklist to add a new behavior.

 

Switch to **Import** mode and paste the following behavior:

```
{
  "behavior": {
    "title": "Catch JotForm webhook",
    "is_disabled": false,
    "priority": 50,
    "event": {
      "key": "event.webhook.received",
      "label": "Webhook received"
    },
    "nodes": [
      {
        "type": "action",
        "title": "Create comment",
        "status": "live",
        "params": {
          "actions": [
            {
              "action": "_set_custom_var",
              "value": "{{http_params.rawrequest}}",
              "format": "json",
              "is_simulator_only": "0",
              "var": "json"
            },
            {
              "action": "create_comment",
              "on": "_trigger_va_id",
              "content": "{{json|json_encode|json_pretty}}"
            }
          ]
        }
      }
    ]
  }
}
```

Click the **Save Changes** button.

# Understanding how the behavior works

Each form has different fields, and there are countless actions you can automate from a bot once you receive a new form submission.

For a simple demonstration, this behavior adds each new response as a comment on the bot's record. You can modify this behavior to meet your specific needs.

# Creating a webhook listener in Cerb

Let's add a webhook listener in Cerb to catch webhooks from JotForm.

You'll need to be an administrator to create a new webhook.

Navigate to **Setup&nbsp;» Services&nbsp;» Webhooks**. If you don't see a _Webhooks_ option in the _Services_ menu then you'll need to [enable that plugin](/docs/plugins/#library).

Click the **(+)** above the webhook listeners worklist.

- **Name:** JotForm new survey response
- **Bot Behavior**: JotForm Bot&nbsp;» Catch JotForm webhook

 

Click the **Save Changes** button.

Copy the **URL** for the new webhook. You'll need it in the next section.

# Sending the webhook from JotForm

Create a form in JotForm:

 

Click into the **Settings** section in the orange bar at the top, then select **Integrations** on the left, and enable **Webhooks** on the right.

Paste your Cerb webhook listener URL from above:

 

Then click the green **Complete Integration** button in the lower right.

JotForm will send data to that URL every time there is a new form submission.

# Testing the behavior

Switch to the **Publish** section on JotForm. This will give you a public URL for your form.

Fill out your form with some sample data:

 

Click the **Submit** button.

The webhook should send data to Cerb nearly instantaneously.

Switch back to Cerb and view JotForm Bot's profile from **Search&nbsp;» Bots**.

Click on the **Comments** button at the top of the page.

You should see something like the following:

 

The comment shows the raw data from your JotForm response in JSON[2](#fn:json) format.

By modifying the behavior you imported above, you can do anything with the form submission data: create records like tickets and tasks, send email, forward the data to other APIs, etc.

For instance, you can create a ticket like this (refer to the comment created by the bot for your own field names):

 

You may be wondering why you wouldn't just have JotForm send responses to email rather than creating a ticket from a webhook. JotForm sends form responses in HTML format, which is difficult to parse if you have bot behaviors acting on new messages. Their email messages also add wrapping linefeeds to long-text fields.

# References

1. JotForm - https://www.jotform.com/&nbsp;[↩](#fnref:jotform)

2. Wikipedia: JSON - https://en.wikipedia.org/wiki/JSON&nbsp;[↩](#fnref:json)

