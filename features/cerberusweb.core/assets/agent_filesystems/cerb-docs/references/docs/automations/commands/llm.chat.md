---
id: "docs-automations-commands-llm-chat"
title: "Automations: llm.chat"
url: "https://cerb.ai/docs/automations/commands/llm.chat/"
summary: "This page describes the `llm.chat` automation command in Cerb, which interfaces with Large Language Models (LLMs) for single-turn chat completions without transcripts, memory, or tools. The command automatically handles authentication and API calls. To use this command, you provide a `system_prompt`, a single message, and the LLM provider configuration. The LLM provider can be one of several options, including Anthropic, Groq, Hugging Face, Ollama, OpenAI, and Together. The `model:` key specifies the model to use, while the `authentication:` key provides a connected account for API authentication. This command is useful for text classification, summarization, and other single-turn AI tasks."
tags: ["docs", "docs-automations"]
---
The **llm.chat:** [automation](/docs/automations/) command interfaces with Large Language Model (LLM) providers for single-turn chat completions without transcripts, memory, or tools.

Authentication and API calls are automatically handled by the command.

You simply provide a `system_prompt` with instructions, one or more `messages`, and the LLM provider configuration. The final message must be a user turn.

This is useful for text classification, summarization, and other single-turn AI tasks.

```
start:
  llm.chat:
    output: results
    inputs:
      llm:
        anthropic:
          model: claude-haiku-4-5
          authentication: cerb:connected_account:anthropic
      system_prompt@text:
        You are a helpful AI assistant that classifies customer messages 
        as positive, negative, or neutral. Return only the classification.
      messages:
        0:
          role: user
          content: Thank you for the quick response! This solved my problem perfectly.
    on_success:
      return:
        classification@key: results:messages:0:content
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
    - [model:](#model)
    - [llm:](#llm)
      - [Streaming](#streaming)
      - [Reasoning](#reasoning)

    - [system\_prompt:](#system_prompt)
    - [messages:](#messages)

  - [output:](#output)

- [Examples](#examples)
  - [Text classification](#text-classification)
  - [Text summarization](#text-summarization)
  - [Content generation](#content-generation)

# Syntax

## inputs:

| Key | Type | Notes |
| --- | --- | --- |
| `llm:` | list | The LLM provider and model to use. |
| `messages:` | list | The messages to send. |
| `model:` | text | An optional [agent model](/docs/records/types/agent_model/) by name. |
| `system_prompt:` | text | The optional instructions for the LLM. |

### model:

Reference a configured [agent model](/docs/records/types/agent_model/) by name instead of repeating a provider block inline:

```
llm.chat:
  output: results
  inputs:
    model: haiku
    messages:
      0:
        role: user
        content: Summarize this conversation in one sentence.
```

Autocompletion reads the model records rather than a static list, so a newly added model appears on reload. Disabled records are excluded, since they can't be referenced.

List several names to provide a fallback chain – the first name resolving to an enabled record wins, and missing or disabled names are skipped. Optional overrides ride under the name in that model's provider grammar. If names were given and none of them resolve, the command fails rather than substituting something else.

The list doesn't have to be written by hand. Resolve a router with [`llm.router:`](/docs/automations/commands/llm.router/) and pass its models straight through:

```
llm.router:
  output: routed
  inputs:
    router: fast

llm.chat:
  output: results
  inputs:
    model@key: routed:models
    messages:
      0:
        role: user
        content: Summarize this conversation in one sentence.
```

**Leaving `model:` out entirely is the easiest path**: with no `llm:` block either, the call falls through to every [available](/docs/records/types/agent_model/#availability) [agent model](/docs/records/types/agent_model/), in the [`priority`](/docs/records/types/agent_model/#priority) order an admin set on the records. The command fails only if that resolves nothing – every model is unlisted, disabled, or missing.

To narrow that pool by what the work needs rather than by name, resolve one with [`llm.router:`](/docs/automations/commands/llm.router/) as above and pass its models here.

Unlike [`llm.agent:`](/docs/automations/commands/llm.agent/), this command has no `agent:` input and no session, so it can't inherit a model from a transcript. Name the models, resolve a pool with [`llm.router:`](/docs/automations/commands/llm.router/), or rely on the default.

An explicit `llm:` block wins for the call and `model:` is ignored.

### llm:

**Prefer an [agent model](/docs/records/types/agent_model/) record over an inline `llm:` block.** A model record keeps credentials and tuning in one place, offers the provider's live model list so nobody has to remember an ID, and makes a model swap one edit instead of one per automation. Setting a [priority](/docs/records/types/agent_model/#priority) on those records goes further -- it orders every call that doesn't configure something of its own, so most automations can omit both `llm:` and `model:` entirely.

The LLM provider is one of:

```
llm:
  anthropic:
    model: claude-haiku-4-5
    authentication: cerb:connected_account:anthropic
  aws_bedrock:
    model: us.anthropic.claude-haiku-4-5-20251001-v1:0
    api_endpoint_url: https://bedrock-runtime.us-east-1.amazonaws.com
    authentication: cerb:connected_account:aws
  docker:
    api_endpoint_url: http://model-runner.docker.internal/
    model: ai/llama3.2
  gemini:
    model: gemini-2.0-flash
    authentication: cerb:connected_account:gemini
  groq:
    model: gemma2-9b-it
    authentication: cerb:connected_account:groq
  huggingface:
    model: google/gemma-2-2b-it
    authentication: cerb:connected_account:huggingface
  ollama:
    api_endpoint_url: http://host.docker.internal:11434
    model: llama3.2
  openai:
    model: gpt-4o
    authentication: cerb:connected_account:openai
  qwen:
    model: qwen3.7-plus
    authentication: cerb:connected_account:qwen
  together:
    model: meta-llama/Llama-3.3-70B-Instruct-Turbo
    authentication: cerb:connected_account:together-ai
  zai:
    model: glm-4.6
    authentication: cerb:connected_account:zai
```

The `model:` key is the name of the model to use. This must be a chat model.

The `authentication:` key is a connected account in URI format (e.g. `cerb:connected_account:name`) for API authentication. This may be omitted for local models like Ollama.

The optional `api_endpoint_url:` key overrides the default endpoint. For instance, this can be used with the `openai:` provider for any compatible API (e.g. SambaNova), or a locally hosted Ollama server.

#### Streaming

Every chat provider streams its response, and streaming is on by default wherever it's supported.

This matters for long turns. A streamed turn replaces the total request timeout with an **inactivity** cutoff, so a turn that's actively producing output is never interrupted – only a genuinely stalled one is. It also means a running turn can be stopped, keeping whatever it had already written.

| Key | Notes |
| --- | --- |
| `stream@bool:` | Set to `no` to send a single blocking request instead |
| `stream_stall_secs:` | How long the provider may go silent before the turn is cut off |

Turn streaming off when something between Cerb and the provider buffers responses rather than passing them through – a proxy in front of Ollama, or an OpenAI-compatible endpoint that doesn't stream correctly.

Raise `stream_stall_secs:` when the wait before the _first_ token is long. Ollama sends no keepalive while it works, so a local server loading a large model off disk, or Ollama Cloud queueing behind other requests, counts as silence for that whole wait. The default allowance is generous, but a very large local model can outlast it.

On AWS Bedrock, streaming is used automatically on the models that support it. Bedrock reports that per model, and a model that can't stream falls back to a normal request rather than failing, so neither key is needed there.

#### Reasoning

On reasoning models, the optional `effort:` key controls how much compute is spent on reasoning. It's the same canonical key [`llm.agent:`](/docs/automations/commands/llm.agent/#reasoning) uses, so one authoring form drives every provider that supports reasoning.

| Key | Values | Description |
| --- | --- | --- |
| `effort:` | e.g. `low`, `medium`, `high`, `xhigh`, `max` | How much compute to spend on reasoning. Higher levels improve quality at the cost of tokens and latency. |

```
llm:
  openai:
    model: o4-mini
    authentication: cerb:connected_account:openai
    effort: medium
```

The level is passed through to the provider verbatim. Cerb doesn't clamp or whitelist it, because the levels a model accepts vary by model and version; the provider's API validates it.

Gemini additionally accepts `thinking_include@bool:`, which includes the model's thinking content in the response – useful when debugging.

On **AWS Bedrock**, `effort:` and the grouped `thinking:` block apply to **Anthropic models only**. Bedrock forwards the parameter straight to the model, so asking a Nova, DeepSeek, or Kimi model to think fails the request rather than being ignored – leave both keys off there. Use `thinking:` with `type: adaptive` on current Anthropic models, or `type: enabled` on older ones (Haiku 4.5, Sonnet 4.5, Opus 4.5), where `effort:` becomes a thinking budget sized to fit inside `max_tokens`. Nothing is sent unless you set one of the keys.

```
llm:
  gemini:
    model: gemini-2.5-pro
    authentication: cerb:connected_account:gemini
    effort: medium
    thinking_include@bool: no
```

The older provider-specific keys -- Gemini's `thinking_level:` and OpenAI's `reasoning_effort:` -- were **removed** in Cerb 11.2 in favor of `effort:`. Update any automation still authoring them.

### system\_prompt:

```
system_prompt@text:
  You are a helpful AI assistant that classifies customer support messages.
  Classify each message as positive, negative, or neutral.
  Return only the classification word.
```

### messages:

The messages to send to the LLM. Each message has `role:` and `content:` keys. The final message must have a `role:` of `user`.

For a simple single-turn completion:

```
messages:
  0:
    role: user
    content: Thank you so much for your help! The issue is now resolved.
```

For few-shot prompting with examples:

```
messages:
  0:
    role: user
    content: Great service! Very helpful staff.
  1:
    role: assistant
    content: positive
  2:
    role: user
    content: This product is terrible and doesn't work.
  3:
    role: assistant
    content: negative
  4:
    role: user
    content: Thank you for the quick response! This solved my problem perfectly.
```

## output:

The key specified in `output:` is set to a dictionary with the following structure:

| Key | Description |
| --- | --- |
| `messages` | An array of response messages from the LLM. |

Each message in the `messages` array has the following structure:

| Key | Description |
| --- | --- |
| `content` | The text response from the LLM. |
| `type` | Currently only `text` is supported. |

```
output:
  messages:
    0:
      type: text
      content: positive
```

# Examples

## Text classification

```
start:
  llm.chat:
    output: classification_result
    inputs:
      llm:
        anthropic:
          model: claude-haiku-4-5
          authentication: cerb:connected_account:anthropic
      system_prompt@text:
        Classify customer messages as: positive, negative, or neutral.
        Return only the classification.
      messages:
        0:
          role: user
          content: {{ticket_latest_message_content}}
    on_success:
      return:
        sentiment@key: classification_result:messages:0:content
```

## Text summarization

```
start:
  llm.chat:
    output: summary_result
    inputs:
      llm:
        openai:
          model: gpt-4o-mini
          authentication: cerb:connected_account:openai
      system_prompt@text:
        Summarize the following text in 2-3 sentences. 
        Focus on the key points and main outcomes.
      messages:
        0:
          role: user
          content@text:
            Please summarize this conversation:
            
            {{ticket_conversation_history}}
    on_success:
      return:
        summary@key: summary_result:messages:0:content
```

## Content generation

```
start:
  llm.chat:
    output: response_result
    inputs:
      llm:
        gemini:
          model: gemini-2.0-flash
          authentication: cerb:connected_account:gemini
      system_prompt@text:
        You are a helpful customer support agent. Generate a professional
        and friendly response to the customer's question. Keep it concise
        and actionable.
      messages:
        0:
          role: user
          content: Customer asked: "How do I reset my password?"
    on_success:
      return:
        suggested_response@key: response_result:messages:0:content
```
