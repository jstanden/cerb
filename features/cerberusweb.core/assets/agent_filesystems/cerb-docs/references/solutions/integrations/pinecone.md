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
- [Generate embeddings](#generate-embeddings)
- [Resources](#resources)

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

# Generate embeddings

**Pinecone has no agent model record, and it isn't in the Provider list.** An [agent model](/docs/records/types/agent_model/) exists to be named by a chat automation, and Pinecone generates vector embeddings rather than holding a conversation. So its provider is named **inline** on the command instead -- there's nothing to create first beyond the connected account above.

```
start:
  llm.embed/vectorize:
    output: embedding
    inputs:
      llm:
        pinecone:
          model: multilingual-e5-large
      input: Cerb automates helpdesk inboxes and workflows.
```

The first key under `llm:` is the provider, so `pinecone:` is what selects it. Everything nested under that key configures the provider itself.

```
commands:
  llm.embed:
    allow@bool: yes
```

# Resources

- Reference: [llm.embed:](/docs/automations/commands/llm.embed/)
- Reference: [Agent models](/docs/records/types/agent_model/) – for chat providers

