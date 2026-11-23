---
id: "solutions-integrations-anthropic"
title: "Anthropic"
url: "https://cerb.ai/solutions/integrations/anthropic/"
summary: "This page provides a step-by-step guide for integrating Cerb with Anthropic as an LLM provider. The process involves obtaining an Anthropic API key from the Claude platform, creating the Anthropic connected service and account in Cerb, and then creating an agent model record that names the provider, endpoint and credentials so automations reference the model by name. It covers loading the live model list with Refresh, verifying the connection with Test, which capability fields are filled in for you and which you set yourself, and using the model from llm.chat and llm.agent. Anthropic does not offer embeddings."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Get an Anthropic API Key.](#get-an-anthropic-api-key)
- [Create the Anthropic service in Cerb](#create-the-anthropic-service-in-cerb)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
- [Resources](#resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to Anthropic. You'll be able to use Anthropic's full API in Cerb automations as a LLM provider.

https://www.youtube.com/embed/9D7brwaAnpM

# Get an Anthropic API Key.

Log in to your Anthropic account or sign up if you don't already have one.

1. Select **API keys** in the left navigation. It's a top-level item, second under **Dashboard** – there's no Settings step to go through first.

2. Click the **Create key** button.

3. **A modal appears offering identity federation instead**, headed _"With identity federation, you don't need an API key"_. Its primary button sets that up; the one you want is the secondary, **Continue with an API key**.

4. In the **Create API key** dialog, give the key a **Name** (eg. `cerb`) and choose when it **Expires**. **Linked account** and **Scope** are there too. Click **Create key**.

5. The key is shown once, under **Save your API key** – _"Keep a record of the key below. You won't be able to view it again."_ Click **Copy key** and keep it somewhere safe for the next step, then **Done**.

# Create the Anthropic service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Anthropic**.

4. Paste the key you copied earlier in the **API Key** field.

5. Click the **Create** button.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **Anthropic**.

4. Leave **API endpoint URL** blank. Its placeholder reads `(auto)`, and blank uses `https://api.anthropic.com`. Set it only for a proxy or a self-hosted endpoint.

5. Set **Authentication** to the connected account you created above. The field isn't marked required, because a local provider needs none – but a hosted one will fail to authenticate without it.

6. Click the **refresh** button beside **Model** to load the provider's live model list, and pick one.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Refresh is on demand, and it never happens quietly.** Until a refresh succeeds, the suggestions under **Model** are a hardcoded list rather than your account's – there's deliberately no silent fallback, so a failed fetch looks like one instead of looking like nothing happened. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list. The field is free text throughout, so a model id works the day it ships even if the list hasn't caught up.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than your account's models, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded – which is the other reason to check the context window on whatever you picked.

Picking a model **from a refreshed list** fills exactly three things: the **Name**, **Vision** and **Context window** – and the last two **only for ids beginning `claude-`**, which is what Cerb's table is gated on. Nothing else on the form changes – **Thinking** and the four **Ratings** are yours to set, and Cerb emits no per-model description for this provider, so the hint line under **Model** stays empty. Both values come from a table built into Cerb rather than from Anthropic, so **check both** and set either yourself if it's wrong for your model. The **Name** is rewritten to a sanitized version of the model id every time, including on a record you already named, so give the record its name _after_ you pick, not before.

**Vision is asserted for the whole `claude-` family**, not looked up per model. The note beside it in Cerb's source reads _"Every current Claude model is multimodal; only the context window varies by tier"_ – accurate when it was written, and it stops being accurate the day Anthropic ships a text-only model, which would then be marked as accepting images. A wrong **Vision** hands workers an image attachment the model rejects.

**The window does vary by tier, with a blanket fallback**: any `claude-` id Cerb doesn't recognize is given `200000`. So a model released after your version of Cerb still gets a context window – a plausible one rather than a correct one, with nothing saying which you got. Compaction ratios are fractions of it.

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
      model: anthropic-sonnet
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

Anthropic doesn't offer embeddings, so there's no [llm.embed:](/docs/automations/commands/llm.embed/) example here. Use a provider that does – [OpenAI](/solutions/integrations/openai/), [Together AI](/solutions/integrations/together-ai/), [Hugging Face](/solutions/integrations/huggingface/) or [Pinecone](/solutions/integrations/pinecone/).

# Resources

- Guide: [Summarize long ticket conversations](/guides/ai-agents/ticket-summarize/)
- Guide: [Update organization contact details from their website](/guides/ai-agents/find-and-update-org-contact-fields/)
- Reference: [Agent models](/docs/records/types/agent_model/)

