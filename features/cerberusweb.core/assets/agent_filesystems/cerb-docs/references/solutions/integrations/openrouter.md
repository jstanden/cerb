---
id: "solutions-integrations-openrouter"
title: "OpenRouter"
url: "https://cerb.ai/solutions/integrations/openrouter/"
summary: "This page provides a step-by-step guide for integrating Cerb with OpenRouter as an LLM provider. OpenRouter is a router rather than a model vendor -- one key and one endpoint reach models from many companies, and model ids are namespaced like anthropic/claude-sonnet-5. It walks through creating an OpenRouter API key and capping it with an optional credit limit, adding the OpenRouter connected service in Cerb, and creating an agent model record that names the provider, endpoint and credentials so automations reference the model by name. It covers loading the live model list with Refresh, verifying the connection with Test, why the Vision and Context window values on this provider describe the model you actually picked because they come from the router's own catalog rather than from a table built into Cerb, why the Name Cerb generates comes out wrong on a router while the Model field has to keep the vendor prefix exactly, why a router makes the Icon field worth setting, and using the model from llm.chat and llm.agent."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get an OpenRouter API key](#get-an-openrouter-api-key)
- [Create the OpenRouter service in Cerb](#create-the-openrouter-service-in-cerb)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
- [Resources](#resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to OpenRouter.

OpenRouter isn't a model vendor. It's a router in front of many of them, so one key and one endpoint reach models from several companies, and each model id names the company it came from – `anthropic/claude-sonnet-5`, `openai/gpt-5.6-terra`, `google/gemini-3-pro-preview`. In Cerb that means one [connected account](/docs/records/types/connected_account/) can back as many [agent models](/docs/records/types/agent_model/) as you want to name, across vendors.

https://www.youtube.com/embed/Ovt0i2g6KN0

# Get an OpenRouter API key

Log in to your OpenRouter account or sign up if you don't already have one. You land on the dashboard. The key page is at openrouter.ai/settings/keys if you'd rather go straight there.

1. Click **API Keys** in the left menu.

2. Click **+ New Key**, in the top right of the list. The dialog asks for a **Name**, an **Expiration**, and a **Key limit**.

3. **Expiration** starts on **No expiration**. A key that never expires is one less thing to replace, and a standing credential worth rotating on your own schedule.

4. **Key limit** is where you cap what the key can spend – **Credit limit (optional)** takes an amount, and **Reset limit every** takes **N/A**, **Daily**, **Weekly** or **Monthly**. Leave the limit blank and the key can spend whatever the account has.

5. Click **Create** to save the key.

6. The key is shown once. Copy it somewhere safe for the next step.

**This is the same question as an API key's permissions, answered differently.** Where other providers scope a key by _what it may call_, OpenRouter caps it by _what it may spend_. A credit limit with a monthly reset is the closest thing here to a restricted key -- it won't stop a runaway automation from calling, but it will stop it from costing more than the number you set.

# Create the OpenRouter service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list. A **Connected Service** dialog opens on its **Library** tab, with the list of services already on screen.

3. Select the **OpenRouter** row – _"Integration with OpenRouter"_.

4. Paste the key you copied earlier in the **API Key** field. The **Alias** is pre-filled with `openrouter`; change it if you keep more than one OpenRouter account. It becomes the account's URI, so it takes only `a-z`, `0-9` and `_`.

5. Click the **Create** button.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **OpenRouter**.

4. Leave **API endpoint URL** blank. Its placeholder reads `https://openrouter.ai/api`, which is what blank uses. Set it only for a proxy.

5. Set **Authentication** to the connected account you created above.

6. Click the **refresh** button beside **Model** to load the router's live model list, and pick one.

7. Set **Icon** to the brand mark of whoever actually makes the model. This matters more here than anywhere else – see below.

8. Replace the **Name**. Cerb generates one from the model id, and on a router that comes out badly – see below. This is what automations will use, and colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

9. Click **Test** to verify the connection, then **Create**.

**Refresh is on demand, and it never happens quietly.** Until a refresh succeeds, the suggestions under **Model** are a hardcoded list rather than the router's – there's deliberately no silent fallback, so a failed fetch looks like one instead of looking like nothing happened. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list. The field is free text throughout, so a model id works the day it ships even if the list hasn't caught up.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than the router's catalog, and the menu keeps showing them until you close and reopen it.

**The capability values here are the router's, not Cerb's.** This is the one place these pages differ in a way that's worth knowing. On most providers, picking a model fills **Vision** and **Context window** from a table built into Cerb and keyed on the model id's _prefix_, so the values describe a family rather than a model. OpenRouter publishes a catalog, and Cerb reads the picked model's own entry from it – its input modalities and its context length. So the numbers describe the model you actually picked, and they change when the router changes them rather than when Cerb is updated. **Label** isn't filled in by any of this; what you see in it before you type is placeholder text rather than a value.

**Model ids are namespaced, and the shipped suggestions show why.** Before any refresh, the suggestions under **Model** span four different companies – Anthropic, OpenAI, Google and DeepSeek – because that's the point of a router. Every id carries its vendor as a prefix, so `anthropic/claude-sonnet-5` and `openai/gpt-5.6-terra` sit in one list and one account reaches both.

**Name and Model sit next to each other and take opposite rules.** **Model** has to match the router's id exactly, slash included – `z-ai/glm-5.3`, not `glm-5.3`. **Name** is yours, and the one Cerb generates for you is built by sanitizing that id, stripping everything outside letters, digits, `_`, `.` and `-`. On every other provider that's invisible, because there's nothing in the id to strip. On a router the ids are `vendor/model`, so the slash vanishes and you get a record called `z-aiglm-5.3`. Type something you'd want to see in an automation instead.

**Set the Icon, because the default is misleading on a router.** The field's hint reads _"Overrides the provider's brand mark and color. Blank uses the provider's own."_ On any other provider the default is right – an Anthropic model showing Anthropic's mark. Here, blank means every model shows **OpenRouter's** logo wherever it appears, whoever actually built it, because nothing in the record says who that is. Pick the maker's glyph from the [icon set](/docs/developers/icons/) instead. The picker has a **Filter icons** box, which is the fast way to find one.

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
      model: openrouter-sonnet
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

A router is the case where a pool earns its keep. One account can back a record per vendor, and a [`llm.router:`](/docs/automations/commands/llm.router/) search or a pool then chooses between them without an automation naming any of them.

# Resources

- Reference: [Agent models](/docs/records/types/agent_model/)
- Reference: [Connected services](/docs/connected-services/)
- Reference: [Icons](/docs/developers/icons/)

