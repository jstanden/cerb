---
id: "solutions-automations-http-get"
title: "HTTP GET request"
url: "https://cerb.ai/solutions/automations/http-get/"
summary: "This page explains how to make an HTTP GET request using the `http.request:` command in Cerb automations. It supports various authentication methods (OAuth, HTTP Basic, Bearer, API keys). The response headers and content are stored in a variable for further processing."
tags: ["solutions", "solutions-automations"]
---
## Basic GET request

You can use http.request: to make a request to any server. Here's an example of a GET request.

- [automation](#)
- [policy](#)

- 
```
start:
  http.request/get:
    output: http_response
    inputs:
      method: GET
      url: https://cerb.ai/
    on_success:
    on_error:
```
- 
```
commands:
  http.request:
    deny/method@bool: {{inputs.method not in ['GET']}}
    deny/url@bool: {{inputs.url is not prefixed ('http://','https://')}}
    allow@bool: yes
```

