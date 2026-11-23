---
id: "solutions-integrations-sambanova"
title: "SambaNova"
url: "https://cerb.ai/solutions/integrations/sambanova/"
summary: "This page provides a step-by-step guide for integrating Cerb with SambaNova as an LLM provider. It walks through generating a SambaNova API key, adding the SambaNova connected service in Cerb, and creating an agent model record. There's no SambaNova entry in the Provider list and there doesn't need to be -- SambaNova speaks the OpenAI API, so the record names OpenAI as the provider and points the API endpoint URL at SambaNova, which is mandatory here rather than optional. It covers loading the live model list with Refresh, which works because SambaNova serves the OpenAI model list shape, verifying the connection with Test, why the capability fields are almost always yours to fill in, the exception for model ids beginning gpt- that inherit OpenAI's own values, and using the model from llm.chat and llm.agent."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get a SambaNova API Key.](#get-a-sambanova-api-key)
- [Create the SambaNova service in Cerb](#create-the-sambanova-service-in-cerb)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
- [Resources](#resources)

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

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

**There's no SambaNova entry in the Provider list, and there doesn't need to be.** SambaNova speaks the OpenAI API, so you select **OpenAI** as the provider and point the endpoint at SambaNova. That's the same arrangement any OpenAI-compatible service uses -- the provider names the _protocol_, not the vendor.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **OpenAI**.

4. Set **API endpoint URL** to SambaNova's API base URL. This one you must fill in – leaving it blank falls back to OpenAI's own endpoint, which isn't what you want here.

5. Set **Authentication** to the connected account you created above. The field isn't marked required, because a local provider needs none – but a hosted one will fail to authenticate without it.

6. Click the **refresh** button beside **Model** to load the provider's live model list, and pick one.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Refresh is on demand, and it never happens quietly.** Until a refresh succeeds, the suggestions under **Model** are a hardcoded list rather than your account's – there's deliberately no silent fallback, so a failed fetch looks like one instead of looking like nothing happened. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list. The field is free text throughout, so a model id works the day it ships even if the list hasn't caught up.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than your account's models, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded.

Refresh works here only because SambaNova serves the OpenAI `/v1/models` shape. An endpoint that doesn't will fail the fetch, and the model id gets typed by hand.

**The capability fields are almost always yours to fill in on this provider.** Cerb has no table of its own for SambaNova, so **Context window**, **Vision**, **Thinking** and the four **Ratings** normally stay exactly as you set them. The context window matters most – compaction ratios are fractions of it.

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
      model: sambanova-llama
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

# Resources

- Reference: [Agent models](/docs/records/types/agent_model/)

