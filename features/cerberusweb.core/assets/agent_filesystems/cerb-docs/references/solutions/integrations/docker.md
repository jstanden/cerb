---
id: "solutions-integrations-docker"
title: "Docker Model Runner"
url: "https://cerb.ai/solutions/integrations/docker/"
summary: "This page is a step-by-step guide for running large language models locally from Cerb using Docker Model Runner. Model Runner exposes an OpenAI-compatible API on Docker's own network, so models run on your own hardware rather than at a hosted provider. The guide covers the base URL to give Cerb and why it must end at /engines rather than /v1, why the default assumes Cerb itself is running in a container on the same Docker host, why this provider needs no connected service and no API key at all, creating an agent model record, why the capability fields are almost always yours to fill in along with the exception for model ids beginning gpt- that inherit OpenAI's vision and context window values, and using the model from the llm.chat and llm.agent automation commands including reasoning effort."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [The base URL](#the-base-url)
- [No connected service is needed](#no-connected-service-is-needed)
- [Create an agent model](#create-an-agent-model)
- [Use the model in automations](#use-the-model-in-automations)
  - [Reasoning](#reasoning)

- [Resources](#resources)

# Introduction

**Docker Model Runner** runs language models on your own Docker host and exposes an **OpenAI-compatible API**, so anything that speaks that protocol can talk to it. In Cerb it's the provider named **Docker**.

Running a model locally changes the trade rather than removing it. Nothing leaves your hardware, there is no per-token cost and no rate limit – and in exchange you're bounded by the machine, by the model you can fit in memory, and by a model list that is whatever you've pulled rather than whatever a vendor publishes.

**Pull a model before you reach for Refresh in Cerb.** That button reports what Model Runner actually has, not what it could fetch.

# The base URL

Cerb reaches Model Runner at the hostname it exposes on Docker's own network:

```
http://model-runner.docker.internal/engines
```

That's both the placeholder under **API endpoint URL** and what a blank field falls back to, so leaving it empty and typing it in are the same thing.

**The base ends at `/engines`. Don't add `/v1`.** Cerb appends the version and the path itself, so that base becomes `http://model-runner.docker.internal/engines/v1/chat/completions` when it runs a turn and `.../engines/v1/models` when it loads the model list. A base that already ends in `/v1` produces `/v1/v1/chat/completions`, and every request 404s. If you copy a base URL from somewhere that includes the version, strip it.

**The default assumes Cerb is running in a container on the same Docker host.** `model-runner.docker.internal` is resolvable from inside Docker's network and nowhere else – a Cerb installed directly on the machine can't resolve that name at all, and has to point at whatever host port you've exposed Model Runner on, keeping the same `/engines` path.

| Where Cerb runs | Base URL |
| --- | --- |
| In a container on the same Docker host | `http://model-runner.docker.internal/engines` |
| Directly on the host | The exposed host port, ending in `/engines` |

# No connected service is needed

Every other provider guide has a step here for creating a [connected service](/docs/connected-services/). **This one doesn't, and that's not an omission.**

There is no Docker row in the **Library** tab to select, and this provider performs no authentication check at all – so there's nothing to hold a credential and nothing that needs one. Leave **Authentication** blank on the agent model and go straight to creating it.

**An empty API key is not the same as no account.** If you attach a connected account that holds no key, Cerb sends an `Authorization: Bearer` header with an empty value -- which some servers reject outright. Leave **Authentication** blank rather than pointing it at an empty account.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

The fields below are in the order the form presents them. **API endpoint URL** comes before **Model** because it feeds both Refresh and Test.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **Docker**.

4. Leave **API endpoint URL** blank if Cerb is containerized on the same Docker host. Otherwise set it to the host port you've exposed, ending in `/engines`.

5. **Leave Authentication empty.** This provider makes no authentication check – see above.

6. Click the **refresh** button beside **Model** to load what Model Runner has pulled, and pick one. Model Runner's ids are namespaced like `ai/llama3.2`.

7. Give the record a short **Name** – this is what automations will use. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

8. Click **Test** to verify the connection, then **Create**.

**Name the record after you pick the model, not before.** Picking from the list rewrites **Name** with a sanitized version of the model id every time, including on a record you already named.

**The capability fields are almost always yours to fill in.** Cerb keeps no table of local models and Model Runner publishes no metadata Cerb reads, so **Context window**, **Vision**, **Thinking** and the four **Ratings** normally stay exactly as you set them. The context window matters most, since compaction ratios are fractions of it.

The exception to watch for is a model id beginning `gpt-`. Cerb falls back to its built-in OpenAI table for those, which sets **Vision to Yes** and a context window sized for OpenAI's own hosting rather than for a model running on your hardware. That fallback reads the model id alone and never looks at the endpoint, so nothing about serving the model yourself prevents it. **If either value appears by itself, check it against the model's documentation** rather than assuming it was measured here. **It only fires when you pick the id out of a refreshed list.** Typing a model id by hand fills nothing at all, and neither do the shipped suggestions – the capability values arrive with a refreshed list and from nowhere else.

Refresh is worth using here more than anywhere else: on a hosted provider the live list is simply the current catalog, but on a local runner it's the only thing that knows what you've actually pulled. Refreshing requires an administrator; a non-admin gets a permission error rather than an empty list.

**Give it a moment to land before you open the Model menu.** Opening it too early shows the shipped suggestions rather than what Model Runner has, and the menu keeps showing them until you close and reopen it. Those are real model ids, so there's nothing on screen to tell you apart from a list that loaded.

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
      model: docker-local
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

`effort:` works normally on this provider, and takes `low`, `medium` or `high`:

```
llm:
  docker:
    model: ai/llama3.2
    effort: medium
```

Cerb sends it as the OpenAI-compatible top-level `reasoning_effort`, so no [`extra_body:`](/docs/automations/commands/llm.agent/#passing-through-provider-extensions) escape hatch is needed. Whether the model you pulled acts on it is the model's business rather than Cerb's.

**Effort survives tool-using turns here.** On OpenAI's own chat endpoint an authored effort is stripped once a turn carries tools; this provider applies no such guardrail, so an [llm.agent:](/docs/automations/commands/llm.agent/) turn keeps the level you set.

# Resources

- Reference: [Agent models](/docs/records/types/agent_model/)
- Reference: [Connected services](/docs/connected-services/)
- Guide: [Launch Cerb in Docker](/docs/installation/docker/)

