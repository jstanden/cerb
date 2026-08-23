---
id: "docs-automations-events-worker-authenticated"
title: "worker.authenticated"
url: "https://cerb.ai/docs/automations/events/worker.authenticated/"
summary: "This page details the 'worker.authenticated' automations in Cerb, which are triggered when a worker successfully logs in. It explains the use of event handler KATA to execute all enabled automations and provides a list of placeholders available in the automation dictionary, such as client browser details and worker records. The page also outlines possible outputs, including denying login with a specific error message, setting a button label for acknowledging a Message of the Day (MOTD), and displaying an optional MOTD formatted with Markdown."
tags: ["docs", "docs-automations"]
---
**worker.authenticated** [automations](/docs/automations/) are triggered when a [worker](/docs/workers/) successfully logs in.

This trigger uses [event handler](/docs/automations/#events) KATA, and all enabled automations are executed.

# Placeholders

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller. |
| `client_browser_name` | string | The client browser name (e.g. Safari). |
| `client_browser_platform` | string | The client browser platform (e.g. Macintosh). |
| `client_browser_version` | string | The client browser version. |
| `client_ip` | string | The client IP address. |
| `worker_*` | record | The [worker](/docs/records/types/worker/#dictionary-placeholders) record. Supports key expansion. |

# Outputs

## return:

| Key | &nbsp; |
| --- | --- |
| `deny:` | If defined, the worker login is denied with the given error message. For instance, combine this with an approved list of known client IPs, or reject very old browser versions. |
| `motd:button:` | The label of the button to acknowledge the MOTD (e.g. 'I accept'). Defaults to 'Continue'. |
| `motd:message:` | An optional MOTD (Message of the Day) to display before logging in. Formatted with Markdown. |

