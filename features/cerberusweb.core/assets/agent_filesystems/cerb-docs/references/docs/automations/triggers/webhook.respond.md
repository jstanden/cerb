---
id: "docs-automations-triggers-webhook-respond"
title: "webhook.respond"
url: "https://cerb.ai/docs/automations/triggers/webhook.respond/"
summary: "This page provides detailed information on the 'webhook.respond' automation feature in Cerb, which is activated by a webhook listener upon receiving an HTTP request. It outlines the use of event handler KATA to execute the first enabled automation. The page specifies the input parameters available in the automation dictionary, such as request body, client IP, headers, method, parameters, and path. It also describes the expected outputs, including the response body, headers, and HTTP status code, offering guidance on how to handle and return data in response to webhook requests."
tags: ["docs", "docs-automations"]
---
**webhook.respond** [automations](/docs/automations/) are triggered by a [webhook listener](/docs/webhooks/) that receives an HTTP request.

This trigger uses [event handler](/docs/automations/#events) KATA, and the first enabled automation is executed.

- [Inputs](#inputs)
- [Outputs](#outputs)
  - [return:](#return)

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `request_body` | string | The request body as text. |
| `request_client_ip` | string | The client IP making the requst (e.g. `1.2.3.4`). |
| `request_headers` | dictionary | The request headers. Keys are lowercase with dashes as underscores (e.g. `content_type`). |
| `request_method` | string | Method name in uppercase (e.g. `POST`). |
| `request_params` | dictionary | The query string parameters. Keys are lowercase with dashes as underscores (e.g. `query_string`). |
| `request_path` | string | The request path (e.g. `some/folder/file.ext`). |

# Outputs

## return:

| Key | Type | Notes |
| --- | --- | --- |
| `body:` | string | The body content to return. Use `body@base64:` for binary. Stream large content from automation resources (`cerb:automation_resource:TOKEN`) and resources (`cerb:resource:NAME`) by URI. |
| `headers:` | dictionary | A set of `key: value` paris (e.g. `Content-Type: application/json`). |
| `status_code:` | integer | HTTP status code (e.g. `200`=OK, `403`=Forbidden, `404`=Not Found, `500`=Error). |

