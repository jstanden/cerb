---
id: "solutions-automations-http-post-json"
title: "HTTP POST request with a JSON payload"
url: "https://cerb.ai/solutions/automations/http-post-json/"
summary: "This page explains how to make an HTTP POST request with a JSON payload using the `http.request` command in Cerb. It shows an example of how to send a POST request to a server with a formatted JSON body, including fields such as name_first, name_last, and email. The code snippet demonstrates how to specify the Content-Type header as application/json and use the body input field to set the payload. Additionally, the page provides an example of how to implement deny policies for the `http.request` command to restrict certain types of requests or URLs from being sent."
tags: ["solutions", "solutions-automations"]
---
## HTTP POST with JSON payload

You can write an HTTP request in KATA and have Cerb format and send it to the server as a JSON payload.

When using `Content-Type: application/json`, a `body:` dictionary will automatically be encoded as JSON.

- [automation](#)
- [policy](#)

- 
```
start:
  http.request/post:
    output: http_response
    inputs:
      method: POST
      url: https://cerb.example/api/endpoint/
      headers:
        Content-Type: application/json
      body:
        name_first: Kina
        name_last: Halpue
        email: kina.halpue@cerb.example
```
- 
```
commands:
  http.request:
    deny/method@bool: {{inputs.method not in ['POST']}}
    deny/url@bool: {{inputs.url is not prefixed ('http://','https://')}}
    allow@bool: yes
```

