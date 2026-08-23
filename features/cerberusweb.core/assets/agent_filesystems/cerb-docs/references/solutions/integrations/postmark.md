---
id: "solutions-integrations-postmark"
title: "Postmark"
url: "https://cerb.ai/solutions/integrations/postmark/"
summary: "This page provides detailed instructions for integrating Postmark as an SMTP mail transport in Cerb for sending outgoing emails. It includes the necessary configuration settings such as host, port, encryption, and authentication using a Postmark API token. Additionally, it addresses the issue of Postmark replacing the `Message-Id:` header, which disrupts email threading in Cerb. To preserve the original message IDs, the page outlines a workflow that adds the `X-PM-KeepId: true` header to outgoing emails. This involves creating a workflow in Cerb that ensures the `X-PM-KeepId` header is included, thereby maintaining proper email threading. The instructions are aimed at administrators and include specific steps and code snippets to implement the solution."
tags: ["solutions"]
---
# Using Postmark for outgoing mail (SMTP)

Add a mail transport for delivering outgoing mail through Postmark.

Navigate to **Search&nbsp;» Mail Transports&nbsp;» (+)**

| **Name:** | Postmark |
| **Type:** | SMTP |
| **Host:** | `smtp.postmarkapp.com` |
| **Port:** | `587` |
| **Encryption:** | TLS |
| **Authentication:** | Enabled |
| **Username/Password:** | (your Postmark API token) |

Click the **Save Changes** button.

# Preserving message ID headers

By default, Postmark replaces the `Message-Id:` header on outgoing email. This breaks reply threading in Cerb.

This workflow adds the `X-PM-KeepId: true` header to outgoing mail.

As an administrator, navigate to **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» (Empty)** and paste the following template:

```
workflow:
  name: cerb.integrations.postmark
  version: 2024-11-27T20:22:16Z
  description: Add the `X-PM-KeepId:` header to outgoing mail when using Postmark SMTP.
  website: https://cerb.ai/resources/workflows/
  requirements:
    cerb_version: >=11.0 <11.2
    cerb_plugins: cerberusweb.core, 
records:
  automation/automation_mailSend:
    fields:
      name: cerb.integrations.postmark.keepMessageId
      extension_id: cerb.trigger.mail.send
      description@text:
      script@raw:
        start:
          return:
            draft:
              params:
                headers:
                  X-PM-KeepId: true
  
  automation_event_listener/listener_mailSend:
    fields:
      name: Postmark
      event_name: mail.send
      priority@int: 100
      is_disabled: 0
      event_kata@raw:
        automation/keepId:
          uri: cerb:automation:cerb.integrations.postmark.keepMessageId
```
