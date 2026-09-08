---
id: "docs-automations-commands-llm-embed"
title: "Automations: llm.embed"
url: "https://cerb.ai/docs/automations/commands/llm.embed/"
summary: "This page describes the `llm.embed` automation command in Cerb, which interfaces with Large Language Models (LLMs) to generate text vector embeddings."
tags: ["docs", "docs-automations"]
---
The **llm.embed:** [automation](/docs/automations/) command interfaces with Large Language Model (LLM) providers to generate text vector embeddings.

Authentication and API calls are automatically handled by the command.

You simply provide a list of `texts:` to embed.

```
llm.embed:
  output: results
  inputs:
    llm:
      ollama:
        api_endpoint_url: http://host.docker.internal:11434
        model: nomic-embed-text
    texts:
      0: What is Cerb?
      1: Cerb automates customer service inboxes and workflows.
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [llm:](#llm)
    - [texts:](#texts)

  - [output:](#output)

# Syntax

## inputs:

| Key | Type | Notes |
| --- | --- | --- |
| `llm:` | list | **Required.** The LLM provider and model to use. |
| `texts:` | list or text | **Required.** The text passages to embed. |

The `output:` key is **required** as well.

### llm:

The LLM provider is one of:

```
llm:
  aws_bedrock:
    api_endpoint_url: https://bedrock-runtime.us-east-1.amazonaws.com
    authentication: cerb:connected_account:aws
    dimensions: 1024
    model: amazon.titan-embed-text-v2:0
  docker:
    api_endpoint_url: http://model-runner.docker.internal/
    model: ai/mxbai-embed-large:latest
  gemini:
    api_endpoint_url: https://generativelanguage.googleapis.com/v1beta/openai
    authentication: cerb:connected_account:gemini
    model: text-embedding-004
  huggingface:
    api_endpoint_url: https://router.huggingface.co
    authentication: cerb:connected_account:huggingface
    model: BAAI/bge-large-en-v1.5
  ollama:
    api_endpoint_url: http://host.docker.internal:11434
    model: nomic-embed-text
  openai:
    api_endpoint_url: https://api.openai.com
    authentication: cerb:connected_account:openai
    model: text-embedding-3-large
  pinecone:
    api_endpoint_url: https://api.pinecone.io
    authentication: cerb:connected_account:pinecone
    model: multilingual-e5-large
  together:
    api_endpoint_url: https://api.together.xyz
    authentication: cerb:connected_account:together
    model: BAAI/bge-base-en-v1.5
  voyage:
    api_endpoint_url: https://api.voyageai.com
    authentication: cerb:connected_account:voyage
    model: voyage-3
```

The `model:` key is the name of the model to use. This must be a text embedding model.

The `authentication:` key is a connected account in URI format (e.g. `cerb:connected_account:name`) for API authentication. This may be omitted for local models like Ollama.

The optional `api_endpoint_url:` key overrides the default endpoint. For instance, this can be used with the `openai:` provider for any compatible API (e.g. SambaNova), or a locally hosted Ollama server.

Unlike [`llm.chat:`](/docs/automations/commands/llm.chat/#model) and [`llm.agent:`](/docs/automations/commands/llm.agent/#model), this command doesn't accept a `model:` key for naming an [agent model](/docs/records/types/agent_model/) or router. An `llm:` block that names the provider and model inline is always required.

Not every provider offers embeddings. Ollama, OpenAI, AWS Bedrock, Pinecone, and VoyageAI support them, as does any provider that inherits the OpenAI-compatible dialect (like those above). Groq is the exception: it speaks the OpenAI dialect but has no embeddings API, so it returns an `LLM provider does not support vector embeddings` error.

### texts:

The text passages to embed.

```
texts:
  0: What is Cerb?
  1@text:
    Cerb automates customer service inboxes and workflows.
    Build helpful AI agents, share high-productivity workspaces, and integrate with any API.
```

The text keys must be unique but are arbitrary.

A plain string is also accepted for `texts:` and is treated as a single passage:

```
texts: What is Cerb?
```

## output:

The `output:` key is **required**. It's set to a dictionary with the following structure:

| Key | Description |
| --- | --- |
| `embeddings` | A list of text vector embeddings. |

```
output:
  embeddings:
    0@list:
      0.0071278084
      4.2720723E-5
      0.14210764
      # ...
    1@list:
      -0.0115925
      -0.005974933
      -0.14457184
      # ...
```
