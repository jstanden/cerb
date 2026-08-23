---
id: "guides-integrations-azure-o365-xoauth"
title: "Authenticate an Office365 mailbox using XOAUTH2"
url: "https://cerb.ai/guides/integrations/azure/o365-xoauth/"
summary: "This page provides a comprehensive guide on how to authenticate an Office365 mailbox using XOAUTH2 in Cerb. It outlines the steps to configure an Azure app and set up mail transport with specific details for SMTP settings. The guide also includes instructions on configuring a mailbox in Cerb, testing the mailbox connection, and ensuring proper functionality by running the scheduler. The document emphasizes the transition from legacy passwords to OAuth2 access tokens for Office365, highlighting Cerb's support for XOAUTH2 authentication since version 9.6."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Configure an Entra app](#configure-an-entra-app)
- [Configure your mail transport](#configure-your-mail-transport)
  - [Is SMTP authentication not enabled?](#is-smtp-authentication-not-enabled)

- [Configure your mailbox in Cerb](#configure-your-mailbox-in-cerb)
  - [Test your mailbox](#test-your-mailbox)

# Introduction

Microsoft is in the process of retiring legacy passwords when connecting to Office365 using SMTP, POP3, and IMAP.

To authenticate in the near future you will need to use OAuth2 access tokens instead.

Cerb supports XOAUTH2 authentication for IMAP mailboxes since version [9.6](/releases/9.6/).

This guide explains how to configure the new feature.

https://www.youtube.com/embed/tYsXwX653sI

# Configure an Entra app

If you haven't already configured an Office365 connected account in Cerb, [follow these instructions](/solutions/integrations/azure/).

Once your connected account is created you can continue to configuring the mailbox.

# Configure your mail transport

In Cerb, navigate to **Search&nbsp;» Email Transports**.

Create or edit a transport with the following details:

| Name: | Office365 SMTP |
| Type: | SMTP |
| Host: | smtp.office365.com |
| Port: | 587 |
| Encryption: | TLS |
| Authentication: | Enabled |
| Username: | (your email address; you@example.com) |
| Password: | (blank) |
| XOAuth2: | (your O365 connected account from above) |

Click the **Save Changes** button.

### Is SMTP authentication not enabled?

If SMTP authentication is not enabled for this tenant, you can enable it with these instructions: https://docs.microsoft.com/en-us/exchange/clients-and-mobile-in-exchange-online/authenticated-client-smtp-submission

# Configure your mailbox in Cerb

In Cerb, navigate to **Search&nbsp;» Email Mailboxes**.

Create or edit a mailbox with the following details:

| Name: | Office365 |
| Protocol: | IMAP (TLS/SSL) |
| Host: | outlook.office365.com |
| User: | (your O365 address) |
| Password: | (blank) |
| XOAuth2: | (your O365 connected account from above) |
| Port: | 993 |

Click the **Test** button at the bottom of the popup.

If connected successfully, click the **Save Changes** button.

### Test your mailbox

You can verify your mailbox is working properly by running the scheduler manually.

Navigate to **Setup&nbsp;» Configure&nbsp;» Scheduler**.

In the section for **Mailbox Checker and Email Downloader**, click the **run now** link.

This will show you a detailed log of your mailbox connection. You should see 'Connected to mailbox' and 'Closed mailbox' without any authentication errors.

If any new messages were downloaded, Cerb will automatically process them. You can manually process new messages by clicking the **run now** link in the **Inbound Email Message Processor** section.

You do not need to manually run the scheduler in the future.

