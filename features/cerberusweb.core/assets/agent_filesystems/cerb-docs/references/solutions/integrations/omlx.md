---
id: "solutions-integrations-omlx"
title: "oMLX"
url: "https://cerb.ai/solutions/integrations/omlx/"
summary: "This page is a step-by-step guide for running large language models locally on Apple silicon from Cerb, using oMLX. oMLX is an inference server built on Apple's MLX framework that exposes an OpenAI-compatible API, so models run on the machine rather than at a hosted provider. oMLX is a Mac app rather than a command line tool, started from a menu and managed from a web dashboard. The guide covers starting the server that way, downloading a model from Hugging Face in the dashboard, the base URL to give Cerb and why it must not include the version path, why the right address depends on where Cerb runs rather than where oMLX runs, creating the oMLX connected service without an API key, creating an agent model record, and using the model from the llm.chat and llm.agent automation commands including reasoning effort."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Run oMLX](#run-omlx)
  - [Download a model](#download-a-model)
  - [The menu bar](#the-menu-bar)

- [The base URL](#the-base-url)
- [Create the oMLX service in Cerb](#create-the-omlx-service-in-cerb)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
  - [Reasoning](#reasoning)

- [Resources](#resources)

# Introduction

**oMLX** is an inference server built on MLX, Apple's array framework for Apple silicon. It runs models on the machine's own GPU and exposes an **OpenAI-compatible API**, so anything that speaks that protocol can talk to it.

**It's a Mac app, not a command line.** You start the server from a menu and manage it from a web dashboard – there's no terminal at any point, which is a good deal of the appeal.

Running a model locally changes the trade rather than removing it. Nothing leaves the machine, there is no per-token cost and no rate limit – and in exchange you're bounded by the hardware, by the model you can fit in memory, and by a model list that is whatever you've loaded rather than whatever a vendor publishes.

# Run oMLX

Download a binary for your Mac from the project's releases:

https://github.com/jundot/omlx

oMLX runs as a macOS app rather than as a command you leave in a terminal. It puts an icon in the **menu bar**, at the top right of the screen, and that menu is where the server lives.

1. Click the oMLX icon in the menu bar and choose **Start Server**.

2. The top line of the menu then reads **Server: running (port 8000)**. **That line is the answer to which port to give Cerb** – read it there rather than assuming, since it reflects whatever the server is actually doing.

A fresh install has no models, so the next step is downloading one.

## Download a model

1. Click the oMLX icon in the menu bar and choose **Open Web Dashboard**.

2. Click **Models** at the top, then **Downloader** from the options below it.

3. From **HuggingFace**, search for a model – for instance `mlx-community/Qwen3.8-27B-8bit` (27.5GB).

4. Once it's downloaded, load it from the **Models** submenu in the menu bar.

## The menu bar

**Open Web Dashboard** puts the same thing in a browser, which is the easier place to work when you're doing more than starting and stopping. **Chat with oMLX** talks to the server directly – useful for confirming a model answers at all before you go looking for a fault in Cerb. **System Stats** and **Serving Stats** show what the machine and the server are doing under load, which is where to look when a turn is slow. **Settings…** and **Stop Server** are there too.

Load a model _before_ you reach for **Refresh** in Cerb. That button reports what the server actually has, not what it could fetch.

# The base URL

Give Cerb the server's **base** address, and nothing more:

```
http://127.0.0.1:8000
```

**Don't include `/v1`.** Cerb appends the version and the path itself, so a base of `http://127.0.0.1:8000` becomes `http://127.0.0.1:8000/v1/chat/completions` when it runs a turn and `.../v1/models` when it loads the model list. A URL that already ends in `/v1` produces `/v1/v1/chat/completions`, and every request 404s.

**Which address is right depends on where Cerb runs, not where oMLX runs.** oMLX always runs on the Mac itself; Cerb may not.

| Where Cerb runs | Base URL |
| --- | --- |
| Directly on the same Mac | `http://127.0.0.1:8000` |
| In a container on that Mac | `http://host.docker.internal:8000` |

Inside a container, `127.0.0.1` is the container rather than the Mac – which is why a [Dockerized Cerb](/docs/installation/docker/) needs the second form to reach a server running beside it.

Leaving the endpoint blank in Cerb uses `http://host.docker.internal:8000` – the container case, with oMLX on its default port. That's the one combination you can leave empty. Anything else, fill it in: Cerb running directly on the Mac, or a server you started on a different port.

# Create the oMLX service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. In the **Library** tab, select **oMLX**.

4. **Leave the API Key blank** unless you started your server with one. Most local servers have no key, and the field's placeholder says so.

5. The **Alias** defaults to `omlx`. Keep it unless you're running more than one.

6. Click the **Create** button.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **oMLX**.

4. Set **API endpoint URL** to your server's base address, from the table above. Leave it blank only if Cerb is containerized and oMLX is on its default port.

5. **Leave Authentication empty** – unless you actually entered an API key when creating the service. See the warning below.

6. Click the **refresh** button beside **Model** to load what your server currently has loaded, and pick one.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**An empty API key is not the same as no account.** If you attach a connected account that holds no key, Cerb sends an `Authorization: Bearer` header with an empty value -- which some servers reject outright. When your server has no key, leave **Authentication** blank rather than pointing it at an empty account.

**The capability fields are yours to fill in.** Cerb keeps no table of local models and your server publishes no metadata Cerb reads, so **Context window**, **Vision**, **Thinking** and the four **Ratings** stay exactly as you set them – including for a model id beginning `gpt-`, which on some other providers would pick up OpenAI's own figures. The context window matters most, since compaction ratios are fractions of it.

Refresh is worth using here more than anywhere else: on a hosted provider the live list is simply the current catalog, but on a local server it's the only thing that knows what you actually have loaded.

**Give it a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than what your server has, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded.

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
      model: omlx-local
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

`effort:` works normally on this provider:

```
llm:
  omlx:
    model: qwen3
    effort: medium
```

That's worth stating because it isn't true of every local server. oMLX reads a reasoning level from `chat_template_kwargs` rather than from the top-level key the OpenAI API uses, so a server reached generically through the `openai` provider needs [`extra_body:`](/docs/automations/commands/llm.agent/#passing-through-provider-extensions) to get a level through. The **oMLX provider does that mapping for you** – llama.cpp, vLLM, SGLang and unsloth still need the escape hatch.

The level is then validated by the model's own chat template rather than by Cerb, so the levels a model accepts vary with it. Qwen3, for instance, takes `low`, `medium` and `xhigh`, and fails the request on anything else.

# Resources

- Reference: [Agent models](/docs/records/types/agent_model/)
- Reference: [Connected services](/docs/connected-services/)
- Guide: [Launch Cerb in Docker](/docs/installation/docker/)

