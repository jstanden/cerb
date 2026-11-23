---
id: "solutions-integrations-huggingface"
title: "Hugging Face"
url: "https://cerb.ai/solutions/integrations/huggingface/"
summary: "This page provides a step-by-step guide for integrating Cerb with Hugging Face as an LLM provider. It walks through creating an access token with the token type and permissions your use needs, adding the Hugging Face connected service in Cerb, and creating an agent model record that names the provider, endpoint and credentials so automations reference the model by name. It covers loading the live model list with Refresh, verifying the connection with Test, why the capability fields are almost always yours to fill in because Cerb ships no table for Hugging Face, the exception for model ids beginning gpt- that inherit OpenAI's vision and context window values, using the model from llm.chat and llm.agent, and generating vector embeddings with llm.embed, which names its provider inline rather than through the agent model record."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a Hugging Face API key](#get-a-hugging-face-api-key)
- [Create the Hugging Face service in Cerb](#create-the-hugging-face-service-in-cerb)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
  - [Embeddings](#embeddings)

- [Resources](#resources)

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

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **Hugging Face**.

4. Leave **API endpoint URL** blank. Its placeholder reads `(auto)`, and blank uses `https://router.huggingface.co`. Set it only for a proxy or a self-hosted endpoint.

5. Set **Authentication** to the connected account you created above. The field isn't marked required, because a local provider needs none – but a hosted one will fail to authenticate without it.

6. Click the **refresh** button beside **Model** to load the provider's live model list, and pick one.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Refresh is on demand, and it never happens quietly.** Until a refresh succeeds, the suggestions under **Model** are a hardcoded list rather than your account's – there's deliberately no silent fallback, so a failed fetch looks like one instead of looking like nothing happened. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list. The field is free text throughout, so a model id works the day it ships even if the list hasn't caught up.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than your account's models, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded.

**The capability fields are almost always yours to fill in on this provider.** Cerb has no table of its own for Hugging Face, so **Context window**, **Vision**, **Thinking** and the four **Ratings** normally stay exactly as you set them. The context window matters most – compaction ratios are fractions of it.

The exception to watch for is a model id beginning `gpt-`. Cerb falls back to its built-in OpenAI table for those, which sets **Vision to Yes** and a context window sized for OpenAI's own hosting rather than for this one. **If either appears by itself, check it against the model's documentation** rather than assuming it was measured here – a wrong Vision setting offers workers an image attachment the model then rejects, which is the more visible of the two failures. This only happens when you pick from a **refreshed** list; the shipped suggestions carry no metadata at all, so before a refresh nothing is filled in on any provider.

# Use the model in automations

Reference the model by the **name** you gave the record.

- [automation](#)
- [policy](#)

- 
```
start:
  llm.chat/summarize:
    output: results
    inputs:
      model: hf-llama
      messages:
        0:
          role: user
          content: Summarize this conversation in one sentence.
  return:
    summary@key: results:content
```
- 
```
commands:
  llm.chat:
    allow@bool: yes
```

The same record works with [llm.agent:](/docs/automations/commands/llm.agent/) for tool-using conversations, and with [llm.router:](/docs/automations/commands/llm.router/) to pick between several models as data. An automation that doesn't name a model resolves a **pool** instead – a search across agent models rather than a named record. Omit a search entirely and you get every available model, in the `priority` order an admin set.

## Embeddings

Hugging Face also generates vector embeddings. `llm.embed:` names its provider **inline** rather than through an agent model record, so this one doesn't use the record you just created:

```
start:
  llm.embed/vectorize:
    output: embedding
    inputs:
      llm:
        huggingface:
          model: sentence-transformers/all-MiniLM-L6-v2
      input: The quick brown fox
```

# Resources

- Reference: [Agent models](/docs/records/types/agent_model/)

