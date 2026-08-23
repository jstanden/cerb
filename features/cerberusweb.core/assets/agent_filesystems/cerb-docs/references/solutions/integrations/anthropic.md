---
id: "solutions-integrations-anthropic"
title: "Anthropic"
url: "https://cerb.ai/solutions/integrations/anthropic/"
summary: "This page provides a step-by-step guide for integrating Cerb with Anthropic, enabling the use of Anthropic's full API in Cerb automations as an LLM provider. The process involves obtaining an Anthropic API key, creating the Anthropic service in Cerb, and configuring examples such as chat completions automation and policy."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get an Anthropic API Key.](#get-an-anthropic-api-key)
- [Create the Anthropic service in Cerb](#create-the-anthropic-service-in-cerb)
- [Examples](#examples)
  - [Chat completions](#chat-completions)

- [Resources](#resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to Anthropic. You'll be able to use Anthropic's full API in Cerb automations as a LLM provider.

# Get an Anthropic API Key.

Log in to your Anthropic Account or sign up if you don't already have one.

Click the **gear icon** ("Settings") in the bottom left menu.

Select **API keys** in the left menu.

Click the **Create Key** button.

Name the key (eg. `cerb`) and click **Add**.

Copy the API key for use later.

# Create the Anthropic service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Anthropic**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Chat completions

https://docs.anthropic.com/en/api/messages

- [automation](#)
- [policy](#)

- 
```
start:
  http.request/chatCompletion:
    output: http_response
    inputs:
      method: POST
      url: https://api.anthropic.com/v1/messages
      authentication: cerb:connected_account:anthropic
      headers:
        anthropic-version: 2023-06-01
        content-type: application/json
      body:
        model: claude-3-opus-20240229
        max_tokens@int: 1024
        system: You are Beethoven Bot. You know everything about music theory and piano. Don't answer questions outside of this scope.
        messages:
          0:
            role: user
            content: What is G Major?
```
- 
```
commands:
  http.request:
    deny/url@bool: {{inputs.url is not prefixed ('https://api.anthropic.com/')}}
    allow@bool: yes
```

# Resources

- Guide: [Summarize long ticket conversations](/guides/ai-agents/ticket-summarize/)
- Guide: [Update organization contact details from their website](/guides/ai-agents/find-and-update-org-contact-fields/)

