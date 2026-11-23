---
id: "solutions-integrations-gemini"
title: "Gemini"
url: "https://cerb.ai/solutions/integrations/gemini/"
summary: "This page provides a step-by-step guide for integrating Cerb with Google's Gemini as an LLM provider. It walks through creating an API key for the Gemini API, adding the Gemini connected service in Cerb, and creating an agent model record that names the provider, endpoint and credentials so automations reference the model by name. It covers loading the live model list with Refresh, verifying the connection with Test, why every model id beginning gemini- is given the same Vision setting and the same 1,000,000 context window from a table built into Cerb rather than from Google, and using the model from llm.chat and llm.agent."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Gemini API](#gemini-api)
  - [Create an API key](#create-an-api-key)

- [Configure Cerb](#configure-cerb)
  - [Create the connected service](#create-the-connected-service)

- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
- [Resources](#resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to Google Gemini.

# Gemini API

### Create an API key

Log in to Google AI Studio with your Google account.

Click the blue **Create API key** button in the top right.

Create the key in a new or existing project.

Copy the API key to your clipboard for use in Cerb.

# Configure Cerb

### Create the connected service

(Added in [11.1.3](/releases/11.1.3/))

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. In the **Library** tab, select the **Gemini** row – _"Integration with Gemini"_.

4. Paste the key you copied earlier in the **API Key** field.

5. The **Alias** is pre-filled with `gemini`. It becomes the account's URI, so it takes only `a-z`, `0-9` and `_` – keep it unless you have more than one Gemini account.

6. Click the **Create** button.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **Google Gemini**.

4. Leave **API endpoint URL** blank. Its placeholder reads `(auto)`, and blank uses `https://generativelanguage.googleapis.com/v1beta/openai`. Set it only for a proxy or a self-hosted endpoint.

5. Set **Authentication** to the connected account you created above. The field isn't marked required, because a local provider needs none – but a hosted one will fail to authenticate without it.

6. Click the **refresh** button beside **Model** to load the provider's live model list, and pick one.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Refresh is on demand, and it never happens quietly.** Until a refresh succeeds, the suggestions under **Model** are a hardcoded list rather than your account's – there's deliberately no silent fallback, so a failed fetch looks like one instead of looking like nothing happened. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list. The field is free text throughout, so a model id works the day it ships even if the list hasn't caught up.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than your account's models, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded – which is the other reason to check the context window on whatever you picked.

Picking a model **from a refreshed list** fills exactly three things: the **Name**, **Vision** and **Context window** – and the last two **only for ids beginning `gemini-`**, which is what Cerb's table is gated on. Nothing else on the form changes – **Thinking** and the four **Ratings** are yours to set, and Cerb emits no per-model description for this provider, so the hint line under **Model** stays empty. Both values come from a table built into Cerb rather than from Google. The **Name** is rewritten to a sanitized version of the model id every time, including on a record you already named, so give the record its name _after_ you pick, not before.

**Check both, because on this provider neither varies by model at all.** Every id beginning `gemini-` is given **Vision: Yes** and a **1,000,000** context window, whichever Gemini model it actually is. There's no per-model map behind either value and no fallback that would distinguish a model Cerb knows from one it doesn't – a smaller-context model such as a Flash-Lite receives the same 1M figure, and nothing catches it.

Set the window from Google's own documentation; compaction ratios are fractions of it. A wrong **Vision** hands workers an image attachment the model rejects.

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
      model: gemini-flash
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

