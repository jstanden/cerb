---
id: "docs-automations-events-mail-route"
title: "mail.route"
url: "https://cerb.ai/docs/automations/events/mail.route/"
summary: "This page provides detailed information on the 'mail.route' automation events in Cerb, which are used to determine the appropriate destination group inbox for incoming messages based on various properties such as sender, subject, recipients, headers, and body content. It outlines the placeholders available in the automation event dictionary, including sender email records, message subject, headers, body in plaintext and HTML, and recipient addresses. Additionally, it describes the outputs of the automation events, specifying how to designate the group or bucket for message delivery using either IDs or names."
tags: ["docs", "docs-automations"]
---
**mail.route** [automation](/docs/automations/) [events](/docs/automations/#events) determine a destination group inbox given properties of an incoming message (e.g. sender, subject, recipients, headers, body).

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
| `group_id:` | number | The group ID to deliver the message to. Alternative to `group_name`. |
| `group_name:` | string | The group name to deliver the message to. Alternative to `group_id`. |
| `bucket_id:` | number | The optional bucket ID to deliver the message to. This can be provided instead of `group_id`. |
| `bucket_name:` | string | The optional bucket name to deliver the message to. A `group_id` or `group_name` must also be provided to disambiguate names like 'Inbox'. |

# Examples

Route based on LLM classification:

- [automation](#)
- [policy](#)
- [event](#)

- 
```
start:
  function/classify:
    uri: cerb:automation:example.llm.classify
    inputs:
      text: {{email_body}}
    output: results

  return:
    group_name: {{results.group}}
    bucket_name: {{results.bucket}}
```
- 
```
commands:
  function:
    deny/uri@bool: {{uri != 'cerb:automation:example.llm.classify'}}
    allow@bool: yes
```
- 
```
automation/llm:
  uri: cerb:automation:example.llm.route
  disabled@bool: no
```

See [Mail Routing](/docs/setup/mail/routing/) for more information about routing and Routing KATA.

