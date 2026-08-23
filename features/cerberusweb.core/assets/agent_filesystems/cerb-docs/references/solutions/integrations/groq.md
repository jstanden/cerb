---
id: "solutions-integrations-groq"
title: "Groq"
url: "https://cerb.ai/solutions/integrations/groq/"
summary: "This page provides a step-by-step guide for integrating Cerb with Groq, allowing users to leverage Groq's full API in Cerb automations for AI inference. To begin, create a new Groq API key by logging into the Groq account, selecting 'API Keys,' and clicking 'Create API Key.' The generated API key is then used to connect the Groq service in Cerb, which can be done by navigating to 'Search >> Connected Services' and creating a new service with the copied API key. Examples of automations using this integration include creating chat completions and listing available models, demonstrating how users can utilize Groq's capabilities within Cerb."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Groq API Key.](#get-a-groq-api-key)
- [Create the Groq service in Cerb](#create-the-groq-service-in-cerb)
- [Examples](#examples)
  - [Create Chat Completion](#create-chat-completion)
  - [List Models](#list-models)

# Introduction

In this guide we'll walk through the process of linking Cerb to Groq. You'll be able to use Groq's full API in Cerb automations for AI inference.

# Get a Groq API Key.

Log in to your Groq Account or sign up if you don't already have one.

Choose **API Keys** in the menu and then click the "Create API Key" button in the top right.

Name the key (eg. `cerb`) and click **Submit**.

Copy the API key for use later.

# Create the Groq service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Groq**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Create Chat Completion

https://console.groq.com/docs/api-reference#chat-create

```
start:
  http.request/hwu7xs:
    output: http_response
    inputs:
      method: POST
      url: https://api.groq.com/openai/v1/chat/completions
      authentication: cerb:connected_account:groq
      headers:
        Content-Type: application/json
      body:
        model: llama-3.3-70b-versatile
        messages:
          0:
            role: user
            content: Tell me who is Beethoven
    on_success:
        set:
          response@json: {{http_response.body}}
          http_response@json: null
```

## List Models

https://console.groq.com/docs/api-reference#models

```
start:
  http.request/hwu7xs:
    output: http_response
    inputs:
      method: GET
      url: https://api.groq.com/openai/v1/models
      authentication: cerb:connected_account:groq
   on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
