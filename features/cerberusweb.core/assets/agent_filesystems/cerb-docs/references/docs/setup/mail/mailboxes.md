---
id: "docs-setup-mail-mailboxes"
title: "Setup: Mailboxes"
url: "https://cerb.ai/docs/setup/mail/mailboxes/"
summary: "This page provides instructions for configuring mailboxes in Cerb, emphasizing the efficiency of using a single 'dropbox' mailbox to consolidate emails from multiple addresses. It explains how to redirect emails from various addresses to a central mailbox, allowing for streamlined mail routing and filtering. For Cerb Cloud users, it suggests redirecting incoming mail to a specific Cerb email address for instant delivery, eliminating the need for additional mailbox setup. The page also details the process of adding a new mailbox, including necessary fields like protocol and credentials, and offers guidance on testing mailbox connections."
tags: ["docs"]
---
This page configures the mailboxes that will be checked for new mail.

It is highly recommended that you configure a single mailbox as a "dropbox". You can redirect mail to a single mailbox even if you have dozens of email addresses.

For example, you can redirect `sales@example.com` and `support@example.com` to `cerb@example.com`.

Your [mail routing](/docs/setup/mail/routing/) and [filtering](/docs/setup/mail/filtering/) rules will still be able to identify the original destination. This is much more efficient than checking several mailboxes every few minutes.

If you're using **Cerb Cloud**, you can alternatively redirect your incoming mail to `support@<you>.cerb.email` for instant delivery. Replace `<you>` with the name of your instance. With this delivery method you won't need to set up a mailbox here.

## Adding a mailbox

To add a new mailbox, click the **(+)** icon in the blue bar of the [worklist](/docs/worklists/).

A mailbox has the following fields:

- **Enabled**

- **Nickname**

- **Protocol**

- **Username**

- **Password**

- **Port**

- **Delete**

## Testing a mailbox

You can verify your mailbox connection details by clicking the **Test Mailbox** button.

# References

1. Wikipedia: Post Office Protocol (POP3) - https://en.wikipedia.org/wiki/Post\_Office\_Protocol&nbsp;[↩](#fnref:pop3)

2. Wikipedia: Port (computer networking) - https://en.wikipedia.org/wiki/Port\_(computer\_networking))&nbsp;[↩](#fnref:port)

