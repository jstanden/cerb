---
id: "solutions-integrations-huggingface"
title: "Hugging Face"
url: "https://cerb.ai/solutions/integrations/huggingface/"
summary: "This page provides a step-by-step guide to integrating Cerb with Hugging Face, allowing users of Hugging Face's full API within Cerb automations. To start, create a new access token in the Hugging Face Account settings and copy it for later use, then navigate to Cerb's Connected Services and follow the prompts to add the Hugging Face service."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Hugging Face API key](#get-a-hugging-face-api-key)
- [Create the Hugging Face service in Cerb](#create-the-hugging-face-service-in-cerb)
- [Examples](#examples)
  - [Search models](#search-models)

# Introduction

In this guide we'll walk through the process of linking Cerb to Hugging Face. You'll be able to use Hugging Face's full API in Cerb automations.

# Get a Hugging Face API key

Log in to your Hugging Face Account or sign up if you don't already have one.

Choose **Access Tokens** in the menu and then click the "Create new token" button in the top right.

Name the key (eg. `cerb`), select the proper token type and permissions for your uses, and click **Create token**.

Copy the token for use later.

# Create the Hugging Face service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Hugging Face**.

4. Paste the access token you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Search models

https://huggingface.co/docs/hub/api#get-apimodels

```
start:
  set:
    params:
      search: whisper
  
  http.request/models:
    output: http_response
    inputs:
      method: GET
      url: https://huggingface.co/api/models/?{{params|url_encode}}
      authentication: cerb:connected_account:huggingface
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
