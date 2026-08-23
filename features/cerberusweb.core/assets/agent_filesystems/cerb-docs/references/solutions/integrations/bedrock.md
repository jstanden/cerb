---
id: "solutions-integrations-bedrock"
title: "Amazon Bedrock"
url: "https://cerb.ai/solutions/integrations/bedrock/"
summary: "This page is a step-by-step guide for running large language models on Amazon Bedrock from Cerb. Bedrock uses the same AWS keypair and built-in Amazon Web Services connected account as any other AWS integration, but it needs its own IAM policy granting the Bedrock invoke and list actions plus the AWS Marketplace subscription actions. The guide covers creating that policy, attaching it to a user, model access for self-subscribing versus third-party marketplace models, choosing a region through the API endpoint URL, understanding cross-region inference profile IDs, and creating an agent model record so that automations can reference the model by name from the llm.chat, llm.agent, and llm.embed commands."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Create the IAM policy](#create-the-iam-policy)
- [Create a user and attach the policy](#create-a-user-and-attach-the-policy)
- [Model access](#model-access)
- [Create the AWS service in Cerb](#create-the-aws-service-in-cerb)
- [Create an agent model](#create-an-agent-model)
  - [Model IDs and inference profiles](#model-ids-and-inference-profiles)

- [Use the model in automations](#use-the-model-in-automations)
  - [Prompt caching](#prompt-caching)
  - [Reasoning](#reasoning)

- [Embeddings](#embeddings)
- [Resources](#resources)

# Introduction

Amazon Bedrock runs large language models from Anthropic, Amazon, DeepSeek, Meta, Mistral and others on AWS infrastructure, billed through your existing AWS account.

Cerb reaches Bedrock through its **Converse API**, so every model family works the same way – reply text, token counts, and tool calls all come back regardless of vendor, and models that emit reasoning keep it separate from the answer.

This guide covers running those models from Cerb. If you want general access to the AWS API from automations, see the [Amazon Web Services](/solutions/integrations/aws/) guide instead – it's the same credentials and the same connected account, and the two can share one IAM user.

The only thing Bedrock genuinely needs beyond that guide is **its own IAM policy**. Without it, a correctly signed request comes back as an authorization failure that looks like a credential problem but isn't.

# Create the IAM policy

Log in to the AWS Management Console and select the **IAM** service.

Select **Policies** in the left navigation, click **Create policy**, and select the **JSON** tab.

Paste the following policy:

```
{
    "Version": "2012-10-17",
    "Statement": [
        {
            "Sid": "VisualEditor0",
            "Effect": "Allow",
            "Action": [
                "bedrock:InvokeModel",
                "bedrock:InvokeModelWithResponseStream",
                "bedrock:ListFoundationModels",
                "bedrock:ListInferenceProfiles",
                "aws-marketplace:ViewSubscriptions",
                "aws-marketplace:Subscribe"
            ],
            "Resource": "*"
        }
    ]
}
```

Click **Next**, name the policy **CerbBedrockPolicy**, and click **Create policy**.

Each group of actions buys something specific:

| Actions | Purpose |
| --- | --- |
| `bedrock:InvokeModel`  
`bedrock:InvokeModelWithResponseStream` | Running a model. Without these, nothing works. |
| `bedrock:ListFoundationModels`  
`bedrock:ListInferenceProfiles` | Lets Cerb ask Bedrock which models your account can actually use, so the model list fills itself in. Without these you have to type model IDs by hand and find out whether they're valid by invoking them. |
| `aws-marketplace:ViewSubscriptions`  
`aws-marketplace:Subscribe` | Lets a model subscribe itself the first time you invoke it, rather than failing until someone visits the console. |

# Create a user and attach the policy

If you already followed the [Amazon Web Services](/solutions/integrations/aws/) guide, you have a `CerbAutomations` IAM user. Attach **CerbBedrockPolicy** to it and skip ahead – one user can hold both policies.

Otherwise, follow that guide's [Create a new user](/solutions/integrations/aws/#create-a-new-user) and [Generate programmatic credentials](/solutions/integrations/aws/#generate-programmatic-credentials) sections, attaching **CerbBedrockPolicy** where it says to attach `CerbAutomationsPolicy`.

Keep the access key and secret key. You'll need them in a moment.

# Model access

**Most models subscribe themselves the first time you invoke them**, so for the models most people want there is no console step at all. Anthropic's Claude, DeepSeek, Moonshot AI's Kimi, and Amazon's own Nova models all work this way. The `aws-marketplace:Subscribe` permission in the policy above is what makes that possible – without it, the same models fail until someone visits the console.

The exception is a smaller set of **third-party marketplace** models that still require explicit approval before they'll run. If you invoke one of those without enabling it first, the request fails with an authorization error even though your keys and signature are correct – which is worth knowing, because the symptom points at your credentials and the cause isn't there.

If that happens, open **Bedrock** in the AWS console, select **Model access** in the left navigation, and enable the specific model you're trying to use. Access is granted per region.

# Create the AWS service in Cerb

Bedrock uses the standard **Amazon Web Services** connected service. If you already created one, reuse it – you don't need a second.

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Amazon Web Services**.

4. Enter your Access Key and Secret Key.

5. Click the **Create** button.

# Create an agent model

An [agent model](/docs/records/types/agent_model/) record holds one model's configuration – its provider, ID, endpoint and credentials – so automations reference it **by name** instead of repeating a provider block.

1. Navigate to **Search&nbsp;» Agent Models**.

2. Click the **(+)** icon in the top right of the list.

3. Set **Provider** to **AWS Bedrock**.

4. Set **API endpoint URL** to the Bedrock runtime endpoint for your region (`https://bedrock-runtime.us-east-1.amazonaws.com`). This is how you choose a region – there's no separate region field. Leave it blank to use `us-east-1`.

5. Set **Authentication** to the Amazon Web Services connected account you created above.

6. Click the **refresh** button beside **Model** to load the models your account can use, and pick one. This is what the `ListFoundationModels` and `ListInferenceProfiles` permissions are for.

7. Picking a model fills in **Name** and sets **Vision** from the provider's own list, so you don't have to know whether a model accepts images. **Context window** is not filled in – set it yourself from the model's documentation, since compaction ratios are fractions of it.

8. Give the record a short **Name** – this is what automations will use. Something like `bedrock-kimi` is easier to type than the model ID, and swapping the underlying model later becomes one edit instead of one per automation. Colons aren't allowed, since the name is referenced as `cerb:agent_model:<name>`.

9. Click **Test** to verify the connection, then **Save Changes**.

### Model IDs and inference profiles

Bedrock model IDs come in two shapes, and the difference catches people out:

```
deepseek.v3.2
global.anthropic.claude-sonnet-5
us.anthropic.claude-sonnet-5
```

The first is a plain model ID. The leading `global.` and `us.` on the others are **not** part of the model name – they identify a **cross-region inference profile**, which routes the request across a set of regions for capacity. Some models are invoked by their plain ID, others only through a profile, and the same model may be offered under more than one prefix.

This is exactly why the `ListFoundationModels` and `ListInferenceProfiles` permissions are worth having. Loading the list from your own account gives you the ID that actually works there, which beats copying one out of documentation and finding out by invoking it.

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
      model: bedrock-kimi
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

The same model works with [llm.agent:](/docs/automations/commands/llm.agent/) for tool-using conversations:

```
start:
  llm.agent/research:
    output: results
    inputs:
      model: bedrock-kimi
      system_prompt@text:
        You are a helpful support assistant. Answer from the tools available to you.
      messages:
        0:
          role: user
          content: {{prompt_question}}
```

To make a model the default for automations that don't name one, add it to an [agent model router](/docs/records/types/agent_model_router/) and flag that router as the default.

## Prompt caching

A long agent conversation re-sends the same prefix every turn, and caching it cuts what that costs. Cerb enables prompt caching on the Bedrock models that offer it, checking your account's model catalog to find them – so a model without caching is never sent a cache marker. There's nothing to configure.

## Reasoning

`effort:` and the grouped `thinking:` block work on Bedrock the same way they do on every other provider, but they apply to **Anthropic models only**.

```
llm:
  aws_bedrock:
    model: us.anthropic.claude-sonnet-4-5-20250929-v1:0
    authentication: cerb:connected_account:aws
    thinking:
      type: adaptive
    effort: high
```

Use `type: adaptive` on current Anthropic models, or `type: enabled` on older ones (Haiku 4.5, Sonnet 4.5, Opus 4.5), where `effort:` becomes a thinking budget sized to fit inside `max_tokens`.

Leave both keys off for Nova, DeepSeek, Kimi, Llama, Mistral, and the rest. Bedrock forwards the parameter straight to the model, so asking one of those to think **fails the request** rather than being ignored. Nothing is sent unless you set a key, so a model without reasoning needs no special handling.

# Embeddings

Bedrock also serves embedding models through [llm.embed:](/docs/automations/commands/llm.embed/), for [search indexes](/docs/records/types/search_index/) and semantic comparison:

```
start:
  llm.embed/text:
    output: results
    inputs:
      llm:
        aws_bedrock:
          api_endpoint_url: https://bedrock-runtime.us-east-1.amazonaws.com
          authentication: cerb:connected_account:aws
          model: amazon.titan-embed-text-v2:0
          dimensions: 1024
      texts:
        0: {{content}}
```

# Resources

- Guide: [Amazon Web Services](/solutions/integrations/aws/) – the generic AWS integration
- Docs: [AI agents](/docs/agents/) – agents, models, routers, and filesystems
- Docs: [Agent Model](/docs/records/types/agent_model/) records
- Workflow: [Generate Profile Images (Amazon Bedrock)](/workflows/cerb.integrations.aws_bedrock.profile_images/)

