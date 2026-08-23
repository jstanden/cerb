---
id: "docs-automations-events-mail-send"
title: "mail.send"
url: "https://cerb.ai/docs/automations/events/mail.send/"
summary: "This page provides detailed information on the 'mail.send' automation events in Cerb, which allow for the modification of sent message drafts before delivery. It explains how users can append unique content, set custom fields, or add custom mail headers to outgoing messages, specifically targeting text or HTML content. The page outlines the structure of the automation event dictionary, including keys for draft records and content modifications. It also describes the outputs, such as content and draft parameter modifications, and details the processes for appending, prepending, and replacing text in message content, with options to specify the target format (HTML, text, saved, or sent)."
tags: ["docs", "docs-automations"]
---
**mail.send** [automation](/docs/automations/) [events](/docs/automations/#events) can modify sent message drafts before they are delivered.

For instance, appending a unique survey link to only the sent html message (not text, nor the copy Cerb saves), setting custom fields, or adding custom mail headers to prevent Sendgrid from rewriting the `Message-Id:` header.

Content modifications can target any combination of text/html on the sent/saved message.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `draft_*` | record | The [draft](/docs/records/types/draft/) record. Supports key expansion. |

# Outputs

| Key | Type | Notes |
| --- | --- | --- |
| `content:` | dictionary | A dictionary of content modifications |
| `draft:params:` | dictionary | A dictionary of [draft parameter](/docs/records/types/draft/) modifications |

## content:

| Key | Type | Notes |
| --- | --- | --- |
| `append:` | object | Append text to the message content |
| `prepend:` | object | Prepend text to the message content |
| `replace:` | object | Replace text in the message content |

Multiple instances of a content modification should have a unique name (e.g. `append/alias:`).

### append:

| Key | Type | Notes |
| --- | --- | --- |
| `on:` | object | `html@bool:`, `text@bool:`, `saved@bool:`, `sent@bool:` (default `yes` on all) |
| `text:` | string | The text to append |

### prepend:

| Key | Type | Notes |
| --- | --- | --- |
| `on:` | object | `html@bool:`, `text@bool:`, `saved@bool:`, `sent@bool:` (default `yes` on all) |
| `text:` | string | The text to prepend |

### replace:

| Key | Type | Notes |
| --- | --- | --- |
| `on:` | object | `html@bool:`, `text@bool:`, `saved@bool:`, `sent@bool:` (default `yes` on all) |
| `text:` | string | The text to replace |
| `with:` | string | The replacement text |

