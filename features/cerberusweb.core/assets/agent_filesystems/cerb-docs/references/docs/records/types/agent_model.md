---
id: "docs-records-types-agentmodel"
title: "Agent Model Records"
url: "https://cerb.ai/docs/records/types/agent_model/"
summary: "An agent model record configures one LLM model that automations and AI agents can use -- its provider, model ID, optional API endpoint, credentials from an encrypted connected account, context window, and provider-specific parameters. It also records what the model can do and how it compares -- vision and extended thinking as capabilities, and intelligence, speed, privacy, and cost as ratings -- so an automation can search for the model it needs instead of naming one. Automations reference a model by name instead of repeating a provider block inline, so credentials and tuning live in one place. This page documents the availability statuses, priority ordering, capability and rating fields, Records API fields, and search filters available on agent model records."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Agent Model |
| **Name (plural):** | Agent Models |
| **Alias (uri):** | agent\_model |
| **Identifier (ID):** | cerb.contexts.agent.model |

- [Choosing a model](#choosing-a-model)
- [Providers](#providers)
- [Endpoint overrides](#endpoint-overrides)
- [Icons](#icons)
- [Availability](#availability)
- [Priority](#priority)
- [Capabilities](#capabilities)
- [Ratings](#ratings)
- [Usage tracking](#usage-tracking)
- [Records API](#records-api)
- [Search Query Fields](#search-query-fields)

An **agent model** configures a single LLM model that [automations](/docs/automations/) and [AI agents](/docs/agents/) can use. It holds the provider, the model ID, an optional API endpoint override, the [connected account](/docs/records/types/connected_account/) supplying credentials, the size of the model's context window, a block of provider-specific parameters, and a description of what the model can do and how it compares to the others you've configured.

https://www.youtube.com/embed/vF-F3vkKoCI

Once a model exists, an automation references it **by name** rather than repeating a provider block inline:

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

### Choosing a model

You don't need to know a provider's model IDs. Once the provider and its [connected account](/docs/records/types/connected_account/) are set, the record's editor asks the provider which models that key can actually use and offers the live list.

This matters most for self-hosted OpenAI-compatible endpoints – llama.cpp, LM Studio, vLLM, or a local Ollama – where no hardcoded list could know what's currently loaded. For hosted providers, the live list is simply the current one.

A provider whose endpoint doesn't offer a model list falls back to a built-in set of known IDs.

### Providers

The `provider` field is one of: `anthropic`, `aws_bedrock`, `docker`, `gemini`, `groq`, `huggingface`, `ollama`, `openai`, `openrouter`, `pinecone`, `qwen`, `together`, `voyage`, or `zai`.

A local provider such as `ollama` or `docker` needs no credentials, so `connected_account_id` is optional.

That list isn't a limit. Any service that speaks the **OpenAI API** or **Anthropic API** protocol works: set `provider` to `openai` or `anthropic` to match the protocol, then point `api_endpoint_url` at the service.

### Endpoint overrides

`api_endpoint_url` overrides the provider's default endpoint. Leave it blank to use the provider's own. When set, it **wins** over any `api_endpoint_url:` left in `params_kata`.

This is what makes an unlisted provider usable – a self-hosted runtime like llama.cpp, LM Studio, or vLLM, or a hosted gateway, proxy, or vendor that emulates either protocol.

The field suggests endpoints as you type, drawn from the ones the selected provider is known to use. It stays free text, so an endpoint that isn't in the list still works. This matters most on `aws_bedrock`, which is addressed by **region** – the suggestions save looking up the hostname for the region your models are in.

### Icons

Most models arrive over an OpenAI-compatible API, so the provider ID often can't name the actual vendor. `icon` and `icon_color` override the brand mark this model displays with; blank falls back to the provider's own icon. The icon must be a name from the shipped [icon set](/docs/developers/icons/) – an unknown name renders as an empty box.

### Availability

Every model has one of three availability statuses, set under **Status** in the editor:

| Status | Value | Meaning |
| --- | --- | --- |
| Available | `0` | Offered to any pool that matches it, and to the implicit default |
| Unlisted | `1` | Skipped by pools, but still runs when an automation names it directly |
| Disabled | `2` | Refused everywhere, and excluded from autocompletion |

Unlisted is the state for a model you want reachable by name without it turning up wherever something asks for "a model" – an expensive model kept for one script, or one you're still evaluating.

Availability is re-checked against each record every time a pool resolves, so unlisting or disabling a model takes it out of every pool immediately. Because unlisted and disabled models are excluded _before_ a caller's own search runs, no search ever has to mention status, and no automation can widen a pool past what an admin allowed.

### Priority

`priority` is the admin's fixed default order for a resolved pool, ascending – `0` is offered first and `255` last. Set it on the models you prefer and every automation that doesn't name a model follows that order.

It's the same `priority` convention used by [automation event listeners](/docs/records/types/automation_event_listener/), [mail routing rules](/docs/records/types/mail_routing_rule/), and [search indexes](/docs/records/types/search_index/). Any explicit `sort:` in a search overrides it entirely.

### Capabilities

Capabilities are facts about the model. They're yes-or-no, and they **filter** – a model that can't accept images isn't eligible for work that sends one, because sending it one is an error rather than a degraded result.

| Field | Filter | Meaning |
| --- | --- | --- |
| Vision | `hasVision:` | Does the model accept image inputs? |
| Thinking | `hasThinking:` | Does the model support extended thinking? |

Attaching an image to a model without vision fails the request rather than quietly dropping the image, and the error names the model.

### Ratings

Ratings are comparisons rather than facts. Each is a four-tier scale you fill in like a star rating, and each tier has a name you can type in a [search query](/docs/search/):

| Rating | Filter | Tiers (low to high) |
| --- | --- | --- |
| Intelligence | `intelligence:` | `basic`, `efficient`, `advanced`, `frontier` |
| Speed | `speed:` | `slow`, `moderate`, `fast`, `instant` |
| Privacy | `privacy:` | `standard`, `no-training`, `zdr`, `local` |
| Cost | `cost:` | `free`, `cheap`, `moderate`, `premium` |

They work as floors and as sort orders, so one query can express several different kinds of requirement:

```
intelligence:>=advanced privacy:>=zdr cost:<=cheap
```

**Privacy is a ladder** where each step includes the ones below it, so `privacy:>=zdr` picks up self-hosted models too without naming them. It's a hard requirement rather than a preference on purpose: a retention rule that was requested and quietly ignored looks exactly like one that was honored.

**Cost is the one rating where less is better**, and the editor says so: the other three fields are hinted "Higher is better", while Cost reads "Lower is better. Price per token and caching." It's a rough tier for routing, not billing.

Ratings start blank, and an unrated model sorts last. An installation that never fills them in behaves exactly as it did before.

Each rating stores a **sparse tier value** – `10`, `20`, `30`, `40`, with `0` meaning unrated – rather than 1 through 4. The decade gaps leave room to insert a tier without a migration, and they keep the _name_ separate from the value behind it, so the scale can move as the field does. When today's frontier models become ordinary, `frontier` is repointed and existing records read as the tier below it without a row being rewritten.

**Repointing a tier is safe; renaming one is not.** The labels double as quick-search values -- `privacy:zdr` is the stored name -- so renaming a tier breaks every saved search, worklist, and automation that filters on it.

Queries and bulk updates use the lowercase names above; the interface capitalizes them for display, so `zdr` reads as `ZDR` and `no-training` reads as `No-training`.

### Usage tracking

Agent Model [worklists](/docs/worklists/) offer two [sparklines columns](/docs/worklists/#sparkline-columns) – **Usage** and **Tokens** – each with a 2h/1d/30d range toggle, drawn from the [agent turn metrics](/docs/metrics/#built-in-metrics). They're two columns rather than two series in one, because turns and tokens differ by orders of magnitude and bars sharing a stack share one scale.

Matching `usage:` and `tokens:` [quick search filters](/docs/search/#parameterized-metrics-filters) query the same data. Each takes its own series names in parentheses:

| Filter | Series | Meaning |
| --- | --- | --- |
| `usage:` | `turns` | Every turn this model ran |
| `usage:` | `rate_limited` | Turns the provider answered with `429` |
| `usage:` | `overloaded` | Turns the provider answered with `529` |
| `usage:` | `unreachable` | Turns where no response arrived at all |
| `usage:` | `latency` | Response time of successful turns, in milliseconds, averaged by default |
| `tokens:` | `input` | Uncached prompt tokens |
| `tokens:` | `output` | Completion tokens |
| `tokens:` | `cache_read` | Prompt tokens served from the provider's cache |
| `tokens:` | `cache_write` | Prompt tokens written to the provider's cache |

```
usage:(rate_limited:>0 since:today)
tokens:(output:>1000000 since:-30 days)
```

Token counts are their own key rather than a series inside `usage:` so that one autocomplete list never mixes token counts with millisecond durations.

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `api_endpoint_url` | [url](/docs/records/fields/types/url/) | An optional endpoint override; blank uses the provider default |
| &nbsp; | `connected_account_id` | [number](/docs/records/fields/types/number/) | The ID of the [connected account](/docs/records/types/connected_account/) supplying credentials |
| &nbsp; | `context_window` | [number](/docs/records/fields/types/number/) | The size of the model's context window, in tokens |
| &nbsp; | `created_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was created |
| &nbsp; | `has_thinking` | [boolean](/docs/records/fields/types/boolean/) | Does this model support extended thinking? (`0` or `1`) |
| &nbsp; | `has_vision` | [boolean](/docs/records/fields/types/boolean/) | Does this model accept images? (`0` or `1`) |
| &nbsp; | `icon` | [text](/docs/records/fields/types/text/) | An optional [icon](/docs/developers/icons/) name overriding the provider's brand mark |
| &nbsp; | `icon_color` | [text](/docs/records/fields/types/text/) | An optional color for the icon |
| &nbsp; | `id` | [number](/docs/records/fields/types/number/) | The ID of this agent model |
| &nbsp; | `label` | [text](/docs/records/fields/types/text/) | A display label for this model |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `model` | [text](/docs/records/fields/types/text/) | The provider's model ID |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this agent model |
| &nbsp; | `params_kata` | [text](/docs/records/fields/types/text/) | Provider-specific parameters in [KATA](/docs/kata/) |
| &nbsp; | `priority` | [number](/docs/records/fields/types/number/) | The default order in a resolved pool, `0` (first) to `255` (last) |
| **x** | **`provider`** | [text](/docs/records/fields/types/text/) | The LLM provider ID |
| &nbsp; | `rating_cost` | [number](/docs/records/fields/types/number/) | The cost tier: `0` unrated, or `10`/`20`/`30`/`40` |
| &nbsp; | `rating_intelligence` | [number](/docs/records/fields/types/number/) | The intelligence tier: `0` unrated, or `10`/`20`/`30`/`40` |
| &nbsp; | `rating_privacy` | [number](/docs/records/fields/types/number/) | The privacy tier: `0` unrated, or `10`/`20`/`30`/`40` |
| &nbsp; | `rating_speed` | [number](/docs/records/fields/types/number/) | The speed tier: `0` unrated, or `10`/`20`/`30`/`40` |
| &nbsp; | `status` | [number](/docs/records/fields/types/number/) | Availability: `0` available, `1` unlisted, `2` disabled |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Search Query Fields

These [filters](/docs/search/#filters) are available in agent model [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `apiEndpointUrl` | text | The endpoint override, if any |
| `authentication` | virtual | Filter by the [connected account](/docs/records/types/connected_account/) supplying credentials |
| `authentication.id` | number | The ID of the connected account supplying credentials |
| `contextWindow` | number | The size of the context window, in tokens |
| `cost` | text | The cost tier: `free`, `cheap`, `moderate`, or `premium` |
| `created` | date | When the record was created |
| `fieldset` | virtual | Filter by [custom fieldset](/docs/records/types/custom_fieldset/) |
| `hasThinking` | boolean | Does the model support extended thinking? |
| `hasVision` | boolean | Does the model accept images? |
| `icon` | text | The [icon](/docs/developers/icons/) name overriding the provider's brand mark |
| `id` | number | The record ID |
| `intelligence` | text | The intelligence tier: `basic`, `efficient`, `advanced`, or `frontier` |
| `label` | text | The display label (partial match) |
| `model` | text | The provider's model ID (partial match) |
| `name` | text | The model name (partial match) |
| `priority` | number | The default order in a resolved pool |
| `privacy` | text | The privacy tier: `standard`, `no-training`, `zdr`, or `local` |
| `provider` | text | The LLM provider ID |
| `speed` | text | The speed tier: `slow`, `moderate`, `fast`, or `instant` |
| `status` | text | Availability: `available`, `unlisted`, or `disabled` |
| `status.id` | number | Availability as a number: `0`, `1`, or `2` |
| `tokens` | virtual | Filter by [token usage](#usage-tracking) – `input`, `output`, `cache_read`, `cache_write` |
| `updated` | date | When the record was last modified |
| `usage` | virtual | Filter by [turn usage](#usage-tracking) – `turns`, `rate_limited`, `overloaded`, `unreachable`, `latency` |
| `watchers` | virtual | Filter by [watchers](/docs/watchers/) |

