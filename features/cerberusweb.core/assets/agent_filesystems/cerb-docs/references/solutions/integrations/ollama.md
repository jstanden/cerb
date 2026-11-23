---
id: "solutions-integrations-ollama"
title: "Ollama"
url: "https://cerb.ai/solutions/integrations/ollama/"
summary: "This page is a step-by-step guide for using Ollama from Cerb, centred on Ollama Cloud. It covers why this provider speaks Ollama's own native API rather than the OpenAI-compatible one, so the base URL must be the bare host with no version path, and why the API endpoint URL field is required here rather than having a default the way other providers do. Because Ollama ships no connected service package, the guide walks through building one by hand as a Token Bearer service with an account holding the API key. It also covers creating an agent model record, why no capability values are filled in for you on this provider, loading the model list with Refresh, using the model from the llm.chat and llm.agent automation commands, how reasoning effort is translated into Ollama's own think parameter, and pointing Cerb at a local Ollama server instead."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [The base URL](#the-base-url)
- [Create the connected service](#create-the-connected-service)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
  - [Reasoning](#reasoning)
  - [Streaming](#streaming)

- [Using a local Ollama server](#using-a-local-ollama-server)
- [Resources](#resources)

# Introduction

**Ollama Cloud** runs Ollama's models as a hosted service, so you reach them with an API key instead of running a server yourself. The same Cerb provider also talks to a local Ollama install – see [Using a local Ollama server](#using-a-local-ollama-server) at the end.

**Cerb speaks Ollama's own native API, not the OpenAI-compatible one.** That single fact drives most of this page: it decides the base URL you enter, and it's why a base that works with other tools may not work here.

You'll need an Ollama account and an API key from it.

# The base URL

Give Cerb the bare host, and nothing more:

```
https://ollama.com
```

**Don't add `/v1`.** Cerb uses Ollama's native API — `/api/chat` for a turn, `/api/tags` for the model list, and `/api/embed` for embeddings. A path suffix like `/v1` is what switches Ollama _into_ OpenAI-compatibility mode, which is not what Cerb wants. Enter `https://ollama.com/v1` and Cerb requests `https://ollama.com/v1/api/chat`, which 404s.

**This field is required on this provider.** Most providers fall back to a default when **API endpoint URL** is left blank. Ollama doesn't — a blank field fails validation with `api_endpoint_url: is required` rather than quietly choosing a host for you.

# Create the connected service

**Ollama ships no package in the Library tab**, so the service that holds your key is built by hand. It's a **Token Bearer** service with an account under it – two records, created from two different lists.

First the service:

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select the **Build** tab, beside **Library** in the strip at the top of the dialog.

4. Set **Type** to **Token Bearer**. The list also offers **API Key**, which is a different mechanism – this one is the `Authorization` header.

5. **Token Name** is already filled in with `Bearer`, which is what Ollama wants. Leave it alone. The field exists for services whose header uses some other word in that position.

6. Give the service a **Name** and a **URI**, then click **Save Changes**. The **URI** is the handle automations use to refer to the record, and it stays blank until you type it – it doesn't derive itself from the name the way the Library route's **Alias** does.

Then the account that holds the key:

1. Navigate to **Search&nbsp;» Connected Accounts**.

2. Click the **(+)** icon. The dialog asks which service to connect to – accounts are created service-first, so pick the Token Bearer service you just built rather than looking for an _add account_ button on the service itself.

3. Give the account its own **Name** and **URI**. Neither fills itself in here either.

4. Paste your Ollama Cloud API key into **Token**, then click **Save Changes**. It's a text area rather than a single-line field, so a long key won't look the way it does elsewhere.

Cerb then sends `Authorization: Bearer <your key>` on every request, substituting **Token Name** for the word `Bearer` if you ever change it.

**The Build tab's button reads `Save Changes`, not `Create`**, which is what the Library tab uses. Same dialog, two tabs, two button labels — so a step that tells you to click _Create_ belongs to the other route.

**An empty API key is not the same as no account.** An account whose token is blank still produces an `Authorization` header — the word `Bearer` followed by nothing — which some services reject outright. If you have no key to supply, leave **Authentication** blank on the agent model rather than attaching an empty account.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **Ollama**.

4. Set **API endpoint URL** to `https://ollama.com`. This field is required – see above.

5. Set **Authentication** to the connected account you created above.

6. Click the **refresh** button beside **Model** to load the model list, and pick one. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Name the record after you pick the model, not before.** Picking from the list rewrites **Name** with a sanitized version of the model id every time, including on a record you already named.

**The capability fields are yours to fill in.** Cerb keeps no table for this provider and reads no metadata from it, so **Context window**, **Vision**, **Thinking** and the four **Ratings** stay exactly as you set them – including for a model id beginning `gpt-`, which on some other providers would pick up OpenAI's own figures. The context window matters most, since compaction ratios are fractions of it.

**Give the refresh a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than the list that was fetched, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded.

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
      model: ollama-cloud
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

## Reasoning

`effort:` works on this provider, but it's **translated rather than passed through**:

```
llm:
  ollama:
    model: llama3.2
    effort: medium
```

Ollama has no effort scale. It has a `think` parameter, and Cerb maps onto it:

| Authored `effort:` | What Ollama receives | Meaning |
| --- | --- | --- |
| `none` | `think: false` | Reasoning off |
| `low`, `medium`, `high` | The matching string | For models that expose a depth, such as gpt-oss |
| Anything else | `think: true` | Reason, at the model's own depth |
| Not authored at all | Nothing is sent | The model's own default |

Levels outside the scale **clamp rather than fail** – `minimal` becomes `low`, and `xhigh` or `max` become `high`. No [`extra_body:`](/docs/automations/commands/llm.agent/#passing-through-provider-extensions) escape hatch is needed.

## Streaming

This provider streams by default. Set `stream@bool:` to `no` if something between Cerb and Ollama buffers responses rather than passing them through.

# Using a local Ollama server

The same provider reaches an Ollama install on your own machine. Nothing above changes except the base URL, and that a local server normally needs no key – in which case leave **Authentication** blank rather than attaching an account with an empty token.

| Where Cerb runs | Base URL |
| --- | --- |
| Directly on the same machine | `http://localhost:11434` |
| In a container on that machine | `http://host.docker.internal:11434` |

Inside a container, `localhost` is the container rather than the machine, which is why a [Dockerized Cerb](/docs/installation/docker/) needs the second form to reach a server running beside it. There's still no default – fill the field in either way.

**Refresh means something different locally.** `/api/tags` reports the models you've actually pulled, not a catalog, so pull a model before you reach for **Refresh**.

# Resources

- Reference: [Agent models](/docs/records/types/agent_model/)
- Reference: [Connected services](/docs/connected-services/)
- Guide: [Launch Cerb in Docker](/docs/installation/docker/)

