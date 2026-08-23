---
id: "docs-automations-triggers-resource-get"
title: "resource.get"
url: "https://cerb.ai/docs/automations/triggers/resource.get/"
summary: "This page provides detailed information about the 'resource.get' automation trigger in Cerb, which is activated by dynamic resources. It explains the use of event handler KATA, where the first enabled automation is executed. The page outlines the structure of the automation dictionary, including inputs such as the current actor dictionary, custom input values, and the resource record. It also describes the outputs, specifically focusing on the return of file metadata, which includes the file content and expiration timestamp for caching purposes."
tags: ["docs", "docs-automations"]
---
**resource.get** [automations](/docs/automations/) are triggered by dynamic [resources](/docs/resources/).

This trigger uses [event handler](/docs/automations/#events) KATA, and the first enabled automation is executed.

- [Inputs](#inputs)
- [Outputs](#outputs)
  - [return:](#return)
    - [file:](#file)

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `actor_*` | record | The current actor dictionary. Supports key expansion. |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller. |
| `resource_*` | record | The [resource](/docs/records/types/resource/) record. Supports key expansion. |

# Outputs

## return:

| Key | Type | Notes |
| --- | --- | --- |
| `file:` | object | The file metadata |

### file:

| Key | Type | Notes |
| --- | --- | --- |
| `content:` | string | The file contents in text (Base64 encode if binary) |
| `expires_at:` | timestamp | Cache this response until this UNIX timestamp |

