---
id: "solutions-automations-http-post-form"
title: "HTTP POST request with a form payload"
url: "https://cerb.ai/solutions/automations/http-post-form/"
summary: "This page demonstrates how to make HTTP POST requests with form-encoded data. It shows how to set headers, send form data, and process JSON responses, making it useful for API integrations and web service interactions."
tags: ["solutions", "solutions-automations"]
---
## HTTP POST with form-encoded payload

Here is an example of making an HTTP POST request with form-encoded data and handling a JSON response.

When using `Content-Type: application/x-www-form-urlencoded`, a `body:` dictionary will automatically be encoded as form data.

- [automation](#)
- [policy](#)

- 
```
start:
  http.request/post:
    output: http_response
    inputs:
      method: POST
      url: https://api.cerb.cloud/docs/search
      headers:
        Content-Type: application/json
      body:
        query: How much does it cost?
        limit: 10
    on_success:
      outcome/200:
        if@bool: {{200 == http_response.status_code and 'application/json' == http_response.content_type}}
        then:
          set:
            status_code@int: {{http_response.status_code}}
            http_response@json: {{http_response.body}}
    on_error:
```
- 
```
commands:
  http.request:
    deny/method@bool: {{inputs.method not in ['POST']}}
    deny/url@bool: {{inputs.url is not prefixed ('http://','https://')}}
    allow@bool: yes
```

