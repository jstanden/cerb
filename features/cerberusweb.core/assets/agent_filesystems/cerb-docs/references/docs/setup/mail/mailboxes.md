---
id: "docs-setup-mail-mailboxes"
title: "Setup: Mailboxes"
url: "https://cerb.ai/docs/setup/mail/mailboxes/"
summary: "This page provides instructions for configuring mailboxes in Cerb, emphasizing the efficiency of using a single 'dropbox' mailbox to consolidate emails from multiple addresses. It explains how to redirect emails from various addresses to a central mailbox, allowing for streamlined mail routing and filtering. For Cerb Cloud users, it suggests redirecting incoming mail to a specific Cerb email address for instant delivery, eliminating the need for additional mailbox setup. The page also details the process of adding a new mailbox, including fields like host, protocol, and credentials, and offers guidance on testing mailbox connections.  It covers authenticating with OAuth2 instead of a password -- required by Google for Gmail and by Microsoft for Microsoft 365 -- as a connected service, a connected account, and the mailbox's XOAuth2 field, with the Microsoft Entra ID library package added in 12.0."
tags: ["docs"]
---
This page configures the mailboxes that will be checked for new mail.

It is highly recommended that you configure a single mailbox as a "dropbox". You can redirect mail to a single mailbox even if you have dozens of email addresses.

For example, you can redirect `sales@example.com` and `support@example.com` to `cerb@example.com`.

Your [mail routing](/docs/setup/mail/routing/) and [filtering](/docs/setup/mail/filtering/) rules will still be able to identify the original destination. This is much more efficient than checking several mailboxes every few minutes.

If you're using **Cerb Cloud**, you can alternatively redirect your incoming mail to `support@<you>.cerb.email` for instant delivery. Replace `<you>` with the name of your instance. With this delivery method you won't need to set up a mailbox here.

## Adding a mailbox

To add a new mailbox, click the **(+)** icon in the blue bar of the [worklist](/docs/worklists/).

The editor has ten fields, in this order:

- **Enabled**

- **Name**

- **Protocol**

- **Port**

- **Host**

- **User**

- **Password**

- **XOAuth2** _(optional)_

- **Timeout**

- **Max Message Size**

Below the fields are three buttons – **Save Changes**, **Test**, and **Delete**. Deleting a mailbox will **not** affect any previously downloaded mail.

Messages in a mailbox are deleted once downloaded (unless the mail server prevents it, as Google Workspace does). If that isn't desirable, create a disposable mailbox to use instead and have copies of your incoming mail sent to it.

## Authenticating with OAuth2

Major providers have retired passwords for mail access. Google no longer accepts one for Gmail over POP3 or IMAP, and Microsoft has been retiring them for Microsoft 365. Both use **XOAUTH2** instead, which authenticates with a rotating, time-limited access token rather than a stored password.

Cerb has supported XOAUTH2 for IMAP mailboxes since [9.6](/releases/9.6/) and for POP3 mailboxes since [11.1.1](/releases/11.1.1/).

Setting one up is three records, in this order:

1. A [connected service](/docs/records/types/connected_service/) holding the provider's OAuth2 endpoints and your application's credentials.
2. A [connected account](/docs/records/types/connected_account/) that authenticates against that service. This is the record that holds the tokens, and it is shared – one account can serve a mailbox, an automation, and a [transport](/docs/setup/mail/transports/).
3. The mailbox itself, with that connected account chosen in its **XOAuth2** field and its **Password** left blank.

Then use **Test** to confirm the token works before saving.

Add the connected service from the **library** rather than building it by hand. A library package fills in the authorize and token URLs and prefills the scopes, including the `offline_access` scope that lets Cerb refresh an expiring token on its own. Cerb 12.0 added a **Microsoft Entra ID** package covering SMTP, POP3, and IMAP for Microsoft 365; you supply the client ID, client secret, and directory ID from your app registration.

For worked examples end to end, see [Authenticate a Gmail mailbox using IMAP or POP3 with XOAUTH2](/guides/integrations/google/gmail-xoauth/) and [Authenticate an Office365 mailbox using XOAUTH2](/guides/integrations/azure/o365-xoauth/). For Gmail, [Gmail](/solutions/integrations/gmail/) covers creating the connected account those steps start from.

**The library's Google service ships without a mail scope.** It's scoped for calendar and profile access, which is what it was built for. Gmail over IMAP with XOAUTH2 also needs `https://mail.google.com/`, so add it to the service's scopes yourself -- nothing will prompt you, and the connected account will authorize successfully and then fail to read mail. The **Microsoft Entra ID** package doesn't have this problem; its scopes are prefilled for mail.

Microsoft 365 uses `outlook.office365.com` on port `993` for IMAP, and `smtp.office365.com` on port `587` with TLS for the matching [transport](/docs/setup/mail/transports/). Both leave their passwords blank and select the same connected account.

## Testing a mailbox

You can verify your mailbox connection details by clicking the **Test** button.

# References

1. Wikipedia: Post Office Protocol (POP3) - https://en.wikipedia.org/wiki/Post\_Office\_Protocol&nbsp;[↩](#fnref:pop3)

2. Wikipedia: Port (computer networking) - https://en.wikipedia.org/wiki/Port\_(computer\_networking))&nbsp;[↩](#fnref:port)

