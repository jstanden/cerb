---
id: "docs-automations-events-worker-authenticate-failed"
title: "worker.authenticate.failed"
url: "https://cerb.ai/docs/automations/events/worker.authenticate.failed/"
summary: "This page provides information on the 'worker.authenticate.failed' automations in Cerb, which are triggered when a worker's login attempt fails due to issues like an invalid password. It details the use of event handler KATA to execute all enabled automations upon such an event. The page outlines the placeholders available in the automation dictionary, including custom input values, client browser details (name, platform, version), client IP address, and worker record information. There are no outputs specified for this automation trigger."
tags: ["docs", "docs-automations"]
---
**worker.authenticate.failed** [automations](/docs/automations/) are triggered when a [worker](/docs/workers/) login fails to authenticate (e.g. invalid password).

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

(none)

