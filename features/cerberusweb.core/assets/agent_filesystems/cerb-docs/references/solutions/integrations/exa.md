---
id: "solutions-integrations-exa"
title: "Exa"
url: "https://cerb.ai/solutions/integrations/exa/"
summary: "This page provides a step-by-step guide for integrating Cerb with Exa, a full-text search API. To begin, users need to obtain an Exa API key by logging into their Exa account and navigating to the API keys section. They then create a new service in Cerb's connected services list, selecting Exa and pasting the copied API key. The guide includes examples of how to use the Exa API for search, extract content from URLs, and provide answers to user queries, providing a comprehensive integration solution between Cerb and Exa."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Exa API Key.](#get-a-exa-api-key)
- [Create the Exa service in Cerb](#create-the-exa-service-in-cerb)
- [Examples](#examples)
  - [Search](#search)
  - [Extract](#extract)
  - [Answer](#answer)

# Introduction

In this guide we'll walk through the process of linking Cerb to Exa. You'll be able to use Exa's full API as an LLM tool for when you don't want or need semantic search.

# Get a Exa API Key.

Log in to your Exa Account or sign up if you don't already have one.

Click **API Keys** in the menu. From there you can copy your default key or create a new one if you wish.

# Create the Exa service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Exa**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Search

https://docs.exa.ai/reference/search

```
start:
  http.request/search:
    output: http_response
    inputs:
      method: POST
      url: https://api.exa.ai/search
      authentication: cerb:connected_account:exa
      headers:
        Content-Type: application/json
      body:
        query: What is KATA?
        includeDomains@csv: cerb.ai
        text@bool: true
   on_success:
    set:
      response@json: {{http_response.body}}
      http_response@json: null
```

## Extract

https://docs.exa.ai/reference/get-contents

```
start:
  http.request/contents:
    output: http_response
    inputs:
      method: POST
      url: https://api.exa.ai/contents
      authentication: cerb:connected_account:exa
      headers:
        Content-Type: application/json
      body:
        urls@csv: https://cerb.ai/docs/kata/
        text@bool: true
   on_success:
    set:
      response@json: {{http_response.body}}
      http_response@json: null
```

## Answer

https://docs.exa.ai/reference/answer

```
start:
  http.request/contents:
    output: http_response
    inputs:
      method: POST
      url: https://api.exa.ai/answer
      authentication: cerb:connected_account:exa
      headers:
        Content-Type: application/json
      body:
        query: Who is Beethoven
        text@bool: true
   on_success:
    set:
      response@json: {{http_response.body}}
      http_response@json: null
```
