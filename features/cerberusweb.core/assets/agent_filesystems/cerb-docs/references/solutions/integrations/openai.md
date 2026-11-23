---
id: "solutions-integrations-openai"
title: "OpenAI"
url: "https://cerb.ai/solutions/integrations/openai/"
summary: "This page provides a step-by-step guide for integrating Cerb with OpenAI as an LLM provider. It walks through creating an API key on the OpenAI platform, including the two restricted permissions Cerb needs and what a key expiration means for automations, adding the OpenAI connected service and account in Cerb, and then creating an agent model record that names the provider, endpoint and credentials so automations reference the model by name. It covers loading the live model list with Refresh, verifying the connection with Test, why autofill sets Vision and Context window only for model ids beginning gpt- and fills nothing at all for other ids such as the o-series, using the model from llm.chat and llm.agent, and generating vector embeddings with llm.embed, which names its provider inline rather than through the agent model record."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Configuration](#configuration)
  - [OpenAI](#openai)
  - [Cerb](#cerb)

- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
  - [Embeddings](#embeddings)

- [Related Resources](#related-resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to OpenAI. You'll be able to use OpenAI's full API from automations in Cerb.

https://www.youtube.com/embed/etOn5YA1IN8

# Configuration

## OpenAI

Log in at https://platform.openai.com/. You land in the **Playground**.

1. Click **API keys** in the left sidebar. It's a top-level item, second under **Codex** – there's no Settings step to go through first.

2. Click **+ Create new secret key**, in the top right of the list.

3. Give the key a **Name** (eg. `Cerb Automations`). **Owned by**, **Project**, **Expiration** and **Permissions** are in the same dialog. **Project** starts on **Default project** – choose another if you keep one for Cerb, but you don't have to create one first.

4. **Expiration** defaults to **30 days**. A key that expires takes every automation using it down on the day it does, so either set a reminder to replace it before then, or choose never and treat it as a standing credential you rotate on your own schedule.

5. **Permissions** starts on **All**, which works. To narrow it, switch to **Restricted**: the scopes appear set to **None**, and **Create secret key** stays disabled until at least one is set. Cerb makes two kinds of call, so a restricted key needs **at least** the scopes covering both.

6. Click **Create secret key**.

7. The key is shown once, under **Save your key** – _"Please save your secret key in a safe place since you won't be able to view it again."_ Copy it somewhere safe for the next step, then click **Done**.

## Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list. A **Connected Service** dialog opens on its **Library** tab, with the list of services already on screen. The other tab, **Build**, is for defining a service by hand.

3. Select the **OpenAI** row – _"Integration with OpenAI"_.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **OpenAI**.

4. Leave **API endpoint URL** blank. Its placeholder reads `(auto)`, and blank uses `https://api.openai.com`. Set it only for a proxy or a self-hosted endpoint.

5. Set **Authentication** to the connected account you created above. The field isn't marked required, because a local provider needs none – but a hosted one will fail to authenticate without it.

6. Click the **refresh** button beside **Model** to load the provider's live model list, and pick one. The live list is broader than the models that work here – realtime, transcription and speech ids can come back on it – so pick a chat model.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Refresh is on demand, and it never happens quietly.** Until a refresh succeeds, the suggestions under **Model** are a hardcoded list rather than your account's – there's deliberately no silent fallback, so a failed fetch looks like one instead of looking like nothing happened. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list. The field is free text throughout, so a model id works the day it ships even if the list hasn't caught up.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than your account's models, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded – which is the other reason to check the context window on whatever you picked.

Picking a model **from a refreshed list** fills exactly three things: the **Name**, **Vision** and **Context window** – and the last two **only for ids beginning `gpt-`**, from a table built into Cerb keyed on that prefix rather than on the model itself. A `gpt-` id is given **Vision: Yes** and a **400,000** window. Nothing else on the form changes – **Thinking** and the four **Ratings** are yours to set, and Cerb emits no per-model description for this provider, so the hint line under **Model** stays empty. The **Name** is rewritten to a sanitized version of the model id every time, including on a record you already named, so give the record its name _after_ you pick, not before.

**Check both** and set either from OpenAI's own documentation if it's wrong for your model. Compaction ratios are fractions of the window, and a wrong **Vision** hands workers an image attachment the model rejects.

**An id that doesn't begin `gpt-` fills no capability fields at all** – an `o`-series model, or one on a compatible endpoint reached through this provider. Empty capability fields there are correct behavior rather than a refresh that failed, and on a record that already has values, a model with no metadata **leaves them as you typed them** rather than clearing them.

**Only a refreshed list carries that metadata.** Before you click **refresh**, the suggestions under **Model** are a shipped list of hints, and picking one of those fills in the **Name** and nothing else – no context window, no capabilities. These values arrive with the model list, not with the model id.

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
      model: openai-gpt
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

OpenAI also generates vector embeddings. `llm.embed:` names its provider **inline** rather than through an agent model record, so this one doesn't use the record you just created:

```
start:
  llm.embed/vectorize:
    output: embedding
    inputs:
      llm:
        openai:
          model: text-embedding-3-small
      input: The quick brown fox
```

# Related Resources

- [Guide: Build a question answering chat bot with Cerb and ChatGPT](/guides/machine-learning/question-answering/faq-bot-chatgpt/)
- Reference: [Agent models](/docs/records/types/agent_model/)

