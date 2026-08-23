---
id: "solutions-automations-http-response-xpath"
title: "HTTP responses with XPath"
url: "https://cerb.ai/solutions/automations/http-response-xpath/"
summary: "This page demonstrates how to make HTTP GET requests and process HTML responses using XPath. It shows how to extract specific elements from web pages, making it useful for web scraping and content extraction tasks."
tags: ["solutions", "solutions-automations"]
---
## Making the GET request and parsing HTML

Here is an example of making an HTTP GET request and using XPath to extract data from an HTML response.

- [automation](#)
- [policy](#)
- [output](#)

- 
```
start:
  http.request/get:
    output: http_response
    inputs:
      method: GET
      url: https://cerb.ai/docs/history/
    on_success:
      set:
        status_code@int: {{http_response.status_code}}
        versions@list:
          {% set html = xml_decode(http_response.body,namespaces,'html') %}
          {% for link in xml_xpath(html, '//a[starts-with(@href,"/releases/")]') %}
          {{link}} - {{xml_attr(link,'href')}}
          {% endfor %}
        http_response@json: null
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
- 
```
status_code: 200
versions:
- 11.0.3 - /releases/11.0.3/
- 11.0.2 - /releases/11.0.2/
- 11.0.1 - /releases/11.0.1/
- 11.0 - /releases/11.0/
- 10.4.22 - /releases/10.4.22/
- 10.4.21 - /releases/10.4.21/
- 10.4.20 - /releases/10.4.20/
- 10.4.19 - /releases/10.4.19/
# ...
```

