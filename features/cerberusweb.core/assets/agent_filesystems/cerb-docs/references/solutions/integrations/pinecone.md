---
id: "solutions-integrations-pinecone"
title: "Pinecone"
url: "https://cerb.ai/solutions/integrations/pinecone/"
summary: "This page provides a step-by-step guide for integrating Cerb and Pinecone, a search engine that uses LLM embeddings. To begin, create a Pinecone API key by logging into your account, selecting 'API keys,' and creating a new key named 'cerb.' Next, navigate to the Cerb Connected Services section, click the '+' icon, select Pinecone, paste the API key, and click 'Create.' The guide then provides an example of how to generate embeddings using Cerb automations, demonstrating how to send a POST request to Pinecone's API with authentication and specifying model parameters."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Pinecone API Key.](#get-a-pinecone-api-key)
- [Create the Pinecone service in Cerb](#create-the-pinecone-service-in-cerb)
- [Examples](#examples)
  - [Generate embeddings](#generate-embeddings)

# Introduction

In this guide we'll walk through the process of linking Cerb to Pinecone. You'll be able to use Pinecone's full API in Cerb automations for LLM embeddings.

# Get a Pinecone API Key.

Log in to your Pinecone Account or sign up if you don't already have one.

Choose **API keys** in the menu and then click the "Create API Key" button in the top right.

Name the key (eg. `cerb`) and click **Create key**.

Copy the API key for use later.

# Create the Pinecone service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Pinecone**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Examples

## Generate embeddings

https://docs.pinecone.io/guides/inference/generate-embeddings

```
start:
  http.request/embeddings:
    output: http_response
    inputs:
      method: POST
      url: https://api.pinecone.io/embed
      authentication: cerb:connected_account:pinecone
      headers:
        Content-Type: application/json
        X-Pinecone-API-Version: 2025-01
      body:
        model: multilingual-e5-large
        parameters:
          input_type: passage
          truncate: END
        inputs:
          0:
            text: Cerb automates helpdesk inboxes and workflows. It has evolved continuously since 2002 based on the feedback of thousands of teams; from solo founders to 1,000+ person enterprises managing millions of customer requests.
    on_success:
      set:
        response@json: {{http_response.body}}
        http_response@json: null
```
