---
id: "solutions-integrations-linkup"
title: "Linkup"
url: "https://cerb.ai/solutions/integrations/linkup/"
summary: "This page provides a step-by-step guide for integrating Cerb with Linkup, a search tool. To begin, log in to your Linkup account and obtain an API key, which is then used to create a new connected service in Cerb's Search settings. The process involves navigating to the Connected Services list, clicking the 'Create' button, and pasting the API key into the specified field. Once created, the Linkup service can be used as an LLM tool for semantic search when needed, utilizing the full API through a custom function that can be configured in Cerb's workflow editor, such as by making a POST request to the Linkup API with specific parameters like query text and depth settings."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Linkup API Key.](#get-a-linkup-api-key)
- [Create the Linkup service in Cerb](#create-the-linkup-service-in-cerb)
- [Examples](#examples)
  - [Search](#search)

# Introduction

In this guide we'll walk through the process of linking Cerb to Linkup. You'll be able to use Linkup's full API as an LLM tool for when you don't want or need semantic search.

# Get a Linkup API Key.

Log in to your Linkup Account or sign up if you don't already have one.

Your API key should be available on your Linkup home page. Click the copy button to copy it to your clipboard.

# Create the Linkup service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Linkup**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Search

https://docs.linkup.so/pages/documentation/api-reference/endpoint/post-search

```
start:
  http.request/search:
    output: http_response
    inputs:
      method: POST
      url: https://api.linkup.so/v1/search
      authentication: cerb:connected_account:linkup
      headers:
        Content-Type: application/json
      body:
        q: Who is Beethoven
        depth: standard
        outputType: sourcedAnswer
   on_success:
    set:
      response@json: {{http_response.body}}
      http_response@json: null
```
