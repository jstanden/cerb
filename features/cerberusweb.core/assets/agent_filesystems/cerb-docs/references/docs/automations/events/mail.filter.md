---
id: "docs-automations-events-mail-filter"
title: "mail.filter"
url: "https://cerb.ai/docs/automations/events/mail.filter/"
summary: "This page provides detailed information on the 'mail.filter' automation events in Cerb, which are used to modify or reject inbound email messages based on various properties such as sender, subject, recipients, headers, and body before acceptance. It outlines the placeholders available in the automation event dictionary, including sender email records, message subject, headers, body in plaintext and HTML, and recipient addresses. The page also describes the outputs of these events, which include options to reject delivery or modify message properties. Specific modifications can be made to custom fields, email body, subject, and headers, and the sender address can be linked to an organization."
tags: ["docs", "docs-automations"]
---
**mail.filter** [automation](/docs/automations/) [events](/docs/automations/#events) can modify or reject an inbound message based on its properties (e.g. sender, subject, recipients, headers, body) before it is accepted.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `email_sender_*` | record | The sender [email](/docs/records/types/address/) record. Supports key expansion. |
| `email_subject` | text | The message subject. |
| `email_headers` | object | A set of header/value pairs. Keys are lowercase with dashes as underscores (e.g. content\_type). |
| `email_body` | text | The email body as plaintext. |
| `email_body_html` | text | The email body as HTML (if provided). |
| `email_recipients` | array | An array of recipient email addresses in the To:/Cc:/Envelope-To:/Delivered-To: headers. |
| `parent_ticket_*` | record | The parent [ticket](/docs/records/types/ticket/) record (if a reply). Supports key expansion. |

# Outputs

| Key | Type | Notes |
| --- | --- | --- |
| `reject:` | bool | `true` to reject delivery |
| `set:` | object | Modify properties of the inbound message |

## set:

| Key | Type | Notes |
| --- | --- | --- |
| `custom_fields:` | object | An object of ticket custom field keys (ID/URIs) and values |
| `email_body:` | string | Rewrite the email plaintext body |
| `email_body_html:` | string | Rewrite the email HTML body |
| `email_sender_org_id:` | number | Link the sender [address](/docs/records/types/address/) to an [organization](/docs/records/types/org/) |
| `email_subject:` | string | Rewrite the email subject |
| `headers:` | object | An object of header keys (names) and values |

