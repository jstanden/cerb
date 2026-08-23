---
id: "solutions-integrations-deepl"
title: "DeepL"
url: "https://cerb.ai/solutions/integrations/deepl/"
summary: "This page provides a step-by-step guide to integrating Cerb and DeepL, allowing users to access DeepL's full API in Cerb automations for translations."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a DeepL API key](#get-a-deepl-api-key)
- [Create the DeepL service in Cerb](#create-the-deepl-service-in-cerb)
- [Examples](#examples)
  - [Translate text](#translate-text)

- [More Resources](#more-resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to DeepL. You'll be able to use DeepL's full API in Cerb automations for translations.

# Get a DeepL API key

Log in to your DeepL Account or sign up if you don't already have one.

Choose **API Keys and Limits** in the menu and click the copy button next to the default key.

If no key is provided, click the **Create key** button.

Name the key (e.g. `cerb`) and click **Create key**.

Copy the API key for use later.

# Create the DeepL service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **DeepL**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Translate text

https://developers.deepl.com/docs/api-reference/translate

Note: DeepL uses a different endpoint for their free and paid tiers. If you are using the free tier, change the url to `https://api-free.deepl.com`.

```
start:
  http.request/translate:
    output: http_response
    inputs:
      method: POST
      url: https://api.deepl.com/v2/translate
      authentication: cerb:connected_account:deepl
      headers:
        Content-Type: application/json
      body:
        target_lang: JA
        text:
          0: I want this text to be translated.
    on_success:
      set:
        response@json: {{http_response.body}}
```

# More Resources

Workflow: [Translate (DeepL)](/workflows/cerb.integrations.deepl.translate)

