---
id: "solutions-integrations-sambanova"
title: "SambaNova"
url: "https://cerb.ai/solutions/integrations/sambanova/"
summary: "This page provides a step-by-step guide for integrating Cerb and SambaNova, a platform that enables the automation of helpdesk inboxes and workflows. To begin, users must log into their SambaNova account, generate a new API key, and then create a new connected service in Cerb using this key. Once set up, users can leverage SambaNova's full API within Cerb automations, as demonstrated by the provided example of integrating chat completions with Meta-Llama-3.1-70B-Instruct model."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a SambaNova API Key.](#get-a-sambanova-api-key)
- [Create the SambaNova service in Cerb](#create-the-sambanova-service-in-cerb)
- [Examples](#examples)
  - [Chat completions](#chat-completions)

# Introduction

In this guide we'll walk through the process of linking Cerb to SambaNova. You'll be able to use SambaNova's full API in Cerb automations.

# Get a SambaNova API Key.

Log in to your SambaNova Account or sign up if you don't already have one.

Choose **APIs** in the menu and then click the "Generate New API Key" button.

Copy the API key for use later.

# Create the SambaNova service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **SambaNova**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Chat completions

```
start:
  http.request/chat:
    output: http_response
    inputs:
      method: POST
      url: https://api.sambanova.ai/v1/chat/completions
      authentication: cerb:connected_account:sambanova
      headers:
        Content-Type: application/json
      body:
        stream@bool: false
        model: Meta-Llama-3.1-70B-Instruct
        messages:
          0:
            role: system
            content: You are a helpful assistant for Cerb, an app to automate helpdesk inboxes and workflows
          1:
            role: user
            content: What is Cerb?
    on_success:
      set:
        response_json@json: {{http_response.body}}
        http_response@json: null
```
