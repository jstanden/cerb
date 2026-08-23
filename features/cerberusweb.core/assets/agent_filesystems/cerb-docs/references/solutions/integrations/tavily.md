---
id: "solutions-integrations-tavily"
title: "Tavily"
url: "https://cerb.ai/solutions/integrations/tavily/"
summary: "This page provides a step-by-step guide for integrating Cerb and Tavily, allowing users to leverage Tavily's full API as a Large Language Model (LLM) tool when semantic search is not required. The integration process begins by obtaining a Tavily API key from the user's account, then creating the Tavily service in Cerb by navigating to Connected Services and entering the API key. Examples of using Tavily's API within Cerb are provided for Search and Extract endpoints, showcasing how users can utilize Tavily's capabilities through pre-built functions in Cerb."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Tavily API Key.](#get-a-tavily-api-key)
- [Create the Tavily service in Cerb](#create-the-tavily-service-in-cerb)
- [Examples](#examples)
  - [Search](#search)
  - [Extract](#extract)

# Introduction

In this guide we'll walk through the process of linking Cerb to Tavily. You'll be able to use Tavily's full API as an LLM tool for when you don't want or need semantic search.

# Get a Tavily API Key.

Log in to your Tavily Account or sign up if you don't already have one.

Your API key should be available on your Tavily home page. Click the copy button to copy it to your clipboard.

 

# Create the Tavily service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Tavily**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Search

https://docs.tavily.com/api-reference/endpoint/search

```
start:
  http.request/search:
    output: http_response
    inputs:
      method: POST
      url: https://api.tavily.com/search
      authentication: cerb:connected_account:tavily
      headers:
        Content-Type: application/json
      body:
        query: What is KATA?
        include_domains@csv: cerb.ai
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```

## Extract

https://docs.tavily.com/api-reference/endpoint/extract

```
start:
  http.request/extract:
    output: http_response
    inputs:
      method: POST
      url: https://api.tavily.com/extract
      authentication: cerb:connected_account:tavily
      headers:
        Content-Type: application/json
      body:
        urls@list:
          https://cerb.ai/docs/automations/
        include_images@bool: no
        extract_depth: basic
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
