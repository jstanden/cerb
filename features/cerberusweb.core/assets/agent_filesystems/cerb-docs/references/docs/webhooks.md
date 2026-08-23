---
id: "docs-webhooks"
title: "Webhooks"
url: "https://cerb.ai/docs/webhooks/"
summary: "This page provides an overview of Cerb's webhooks plugin, which allows users to create automated responses to real-time events from various services without needing to write custom code. It explains how webhooks work as efficient 'push notifications' compared to traditional polling methods. The page details how users can set up webhook listeners with unique URLs for integration with services like Amazon CloudWatch, Freshbooks, Github, JIRA, Mailgun, Slack, Twilio, and Zapier. These integrations enable automated notifications and actions, such as sending alerts to Slack, managing invoices, tracking issues, and more, all through Cerb's web-based interface."
tags: ["docs"]
---
Many modern apps and services provide **webhooks** [1](#fn:webhooks) for real-time _"push notifications"_ about events. These notifications are usually simple HTTP POSTs[2](#fn:http-post) with either request parameters or JSON/XML payloads that are sent to a URL you provide. This is in contrast to the less efficient _"poll"_ approach, where a service is constantly polled at regular intervals to check for new changes.

With Cerb's [webhooks plugin](/guides/webhooks/configure/) you can build powerful automation behaviors in response to webhooks, entirely from your web browser, and without writing custom code or plugins. Each _webhook listener_ is given a unique URL that is assigned to a specific [automation](/docs/automations/). With that URL in hand, you can simply paste it into the appropriate field in whatever service you're integrating with. By default, these URLs don't require authentication, but you can enforce any authentication method you need within the bot behavior.

Here are some common webhook integrations:

- **Amazon CloudWatch**
  - Send alerts to a Slack room when any metric or log conditions are met. For instance: HTTP 5XX errors, consecutive failed password attempts, SSH sessions, etc.

- **Freshbooks**
  - Send invoice and payment notifications to a Slack room.

- **Github**
  - Send issue and commit notifications to a Slack room.

- **JIRA**
  - Send notifications about new/resolved issues to a Slack room.

- **Mailgun**
  - Send notifications about bounced or dropped messages to a Slack room along with a permalink to the ticket. The bot can also adds a sticky note to the affected messages with the specific error message; and, when appropriate, the recipient is marked as defunct.

- **Slack**
  - [Send slash commands to a Cerb bot](/guides/integrations/slack/slash-commands/)

- **Twilio**
  - [Correspond with a Cerb bot through mobile text messages](/solutions/integrations/twilio/)

- **Zapier**
  - Zapier[3](#fn:zapier) helps integrate hundreds of apps and services. You can use their existing 'webhook' action to create new webhooks that ping Cerb bots for thousands of different events. For instance, _"when an RSS feed changes"_, _"when a new file is added to Dropbox"_, _"when your brand is mentioned in Google Alerts"_, _"when a new event is added to your Google Calendar"_, etc.

# References

1. Webhooks http://en.wikipedia.org/wiki/Webhook&nbsp;[↩](#fnref:webhooks)

2. HTTP POST https://en.wikipedia.org/wiki/POST\_(HTTP))&nbsp;[↩](#fnref:http-post)

3. Zapier https://zapier.com/zapbook/&nbsp;[↩](#fnref:zapier)

