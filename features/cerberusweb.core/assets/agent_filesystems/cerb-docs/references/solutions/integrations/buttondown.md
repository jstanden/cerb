---
id: "solutions-integrations-buttondown"
title: "Buttondown"
url: "https://cerb.ai/solutions/integrations/buttondown/"
summary: "This page is a step-by-step guide for integrating Cerb and Buttondown, allowing users to leverage Buttondown's full API in Cerb automations for email newsletters."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Buttondown API key](#get-a-buttondown-api-key)
- [Create the Buttondown service in Cerb](#create-the-buttondown-service-in-cerb)
- [Examples](#examples)
  - [List newsletters](#list-newsletters)
  - [List subscribers](#list-subscribers)
  - [Add a subscriber](#add-a-subscriber)

# Introduction

In this guide we'll walk through the process of linking Cerb to Buttondown. You'll be able to use Buttondown's full API in Cerb automations for email newsletters.

# Get a Buttondown API key

Log in to your Buttondown Account or sign up if you don't already have one.

Navigate to the https://buttondown.com/requests page.

Click the **API Key** button in the top bar, or hit the `K` button on your keyboard.

 

Copy the API key for use later.

# Create the Buttondown service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Buttondown**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## List newsletters

```
start:
  http.request/newsletters:
    output: http_response
    inputs:
      method: GET
      url: https://api.buttondown.com/v1/newsletters
      authentication: cerb:connected_account:buttondown
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## List subscribers

```
start:
  http.request/subs:
    output: http_response
    inputs:
      method: GET
      url: https://api.buttondown.com/v1/subscribers
      authentication: cerb:connected_account:buttondown
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Add a subscriber

```
start:
  http.request/addSub:
    output: http_response
    inputs:
      method: POST
      url: https://api.buttondown.com/v1/subscribers
      authentication: cerb:connected_account:buttondown
      headers:
        Content-Type: application/json
      body:
        email_address: customer@cerb.example
        #tags@csv: tag-1, tag-2
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
