---
id: "docs-setup-mail-relay"
title: "Setup: External Relay"
url: "https://cerb.ai/docs/setup/mail/relay/"
summary: "This page provides detailed information on setting up an external email relay for Cerb, allowing workers to respond to messages using external mail applications like Gmail, Outlook, or mobile phones, while maintaining the appearance of sending from Cerb. This setup ensures the privacy of workers' personal email addresses and retains Cerb's features such as shared history and assignments. The page explains the authentication process for relayed messages, which involves checking mail headers and using a secret key in the `Message-Id:` header. It also addresses potential issues with certain email applications that may not adhere to standard conventions and offers guidance on disabling built-in authentication with caution. Additionally, it emphasizes the importance of setting up alternative authentication methods to prevent unauthorized message relaying and advises on handling 'spoofed' sender messages. The page includes a resource guide for responding to messages from an external email client."
tags: ["docs"]
---
The email relay allows workers to respond to messages from external mail applications (e.g. Gmail, mobile phones, Outlook, etc) instead of requiring them to always use Cerb in the web browser.

Relayed responses are received from a worker's personal email address and rewritten so they appear to be from Cerb before being sent to a conversation's recipients. This process protects the privacy of personal worker email addresses, while still providing the benefits of Cerb (e.g. shared history, assignments, etc).

## Authentication

By default, relayed messages are authenticated by checking the mail headers. Copies of mail that are relayed to workers outside of Cerb using Virtual Attendant behavior are "signed" with a secret key in the `Message-Id:` header. According to the RFC-5322 standard, this `Message-Id:` should be referenced in the `In-Reply-To:` header of any reply.

Unfortunately, some email applications _"break the Internet"_ by ignoring these many decade old conventions. Common culprits include Microsoft Exchange and some Android or Blackberry mobile devices.

In the event that the worker relay doesn't function properly in your environment, you may disable the built-in authentication. Be careful when doing this! When authentication is disabled, anyone can forge a message From: one of your workers and have it relayed to arbitrary conversations. It is very important that you set up alternative authentication using [mail.filter:](/docs/automations/events/mail.filter/) [automations](/docs/automations/) to approve or deny inbound worker replies through the relay.

Many mail servers will reject messages sent from "spoofed" senders. You should leave the From: setting at the default unless you have verified that your mail server allows for spoofed messages.

## Resources

- [Guide: Respond to messages from an external email client](/guides/mail/relaying/)

