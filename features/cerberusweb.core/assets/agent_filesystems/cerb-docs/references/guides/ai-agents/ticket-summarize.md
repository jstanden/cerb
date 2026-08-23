---
id: "guides-ai-agents-ticket-summarize"
title: "Summarize a ticket conversation"
url: "https://cerb.ai/guides/ai-agents/ticket-summarize/"
summary: "This page provides a guide on using Anthropic Claude to automatically summarize long ticket conversations into a single detailed paragraph for quick worker reference. It covers installation steps, including connecting to an Anthropic account and importing an example workflow, and outlines usage instructions for summarizing ticket histories and creating text embeddings for semantic search. The workflow involves prompting Claude to read ticket history, generate a summary, and store it as a comment on the ticket."
tags: ["guides"]
---
# Introduction

An [AI agent](/docs/automations/commands/llm.agent/) can summarize long ticket conversations in a comment to help workers catch up quickly.

This is also useful for summarizing closed tickets before creating [text embeddings](/docs/automations/commands/llm.embed/) for semantic search and RAG.

 

In this example we'll use Anthropic's Claude Haiku 4.5 model. You can use any language model from popular providers.

When dealing with personally identifying information (PII) you should use a model with strong privacy protection. Amazon Bedrock runs Anthropic Claude within an AWS region, or you can use a locally hosted model with tools like Ollama or Docker Model Runner.

- [Introduction](#introduction)
- [Installation](#installation)
  - [Connect to an Anthropic account](#connect-to-an-anthropic-account)
  - [Import the example workflow](#import-the-example-workflow)

- [Usage](#usage)

# Installation

## Connect to an Anthropic account

[Create an Anthropic connected account](/solutions/integrations/anthropic/) in Cerb.

## Import the example workflow

Create a new workflow from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Empty**.

Copy and paste the following workflow template:

```
workflow:
  name: wgm.ai_agent.ticket_summarize
  description: Summarize ticket history using an LLM
  requirements:
    cerb_plugins: cerberusweb.core,
    cerb_version: >=11.0 <12.0
  version: 2026-08-11T00:00:00Z
  website: https://cerb.ai/resources/workflows/
  config:
    chooser/anthropic_account_id:
      label: Anthropic Account:
      record_type: connected_account
      record_query: anthropic
      multiple@bool: no
records:
  toolbar_section/toolbarRecordProfile:
    fields:
      name: Summarize
      toolbar_name: record.profile
      priority@int: 100
      is_disabled: 0
      toolbar_kata@raw:
        interaction/summarize:
          label: Summarize
          uri: cerb:automation:wgm.ai_agent.ticket_summarize.interaction
          icon: notes
          hidden@bool: {{record__context is not record type ('ticket')}}
          inputs:
            ticket: {{record_id}}
          after:
            refresh_widgets@csv: Conversation
            refresh_toolbar@bool: no
  automation/scriptLlmSummarize:
    fields:
      name: wgm.ai_agent.ticket_summarize.interaction
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          record/ticket:
            record_type: ticket
            required@bool: yes

        start:
          set:
            config@json: {{cerb_workflow_config('wgm.ai_agent.ticket_summarize')|json_encode}}

          await/start:
            form:
              title: Ticket Summary
              elements:
                say:
                  content@text:
                    Summarizing...
                    --------------
                submit:
                  is_automatic@bool: yes

          record.search/msgs:
            output: messages
            inputs:
              record_type: message
              record_query: ticket.id:${ticket_id} sort:id limit:50
              record_query_params:
                ticket_id: {{inputs.ticket.id}}
              record_expand: headers,content

          llm.agent:
            output: results
            inputs:
              llm:
                #aws_bedrock:
                # api_endpoint_url: https://bedrock-runtime.us-west-2.amazonaws.com
                # authentication: cerb:connected_account:{{config.aws_account_id}}
                # model: us.anthropic.claude-haiku-4-5-20251001-v1:0
                anthropic:
                  model: claude-haiku-4-5
                  authentication: cerb:connected_account:{{config.anthropic_account_id}}
              system_prompt@text:
                You are a helpful AI assistant who summarizes long threaded email conversations into a single detailed paragraph.
                Your response will be stored as a summary on helpdesk tickets. Don't start "Here's a summary".
                Just respond with the summarized conversation.
                For instance: "Bob asked how to configure the software to show his signature at the top rather than the bottom..."
              messages:
                message:
                  role: user
                  content@text:
                    Summarize this conversation:

                    {% for message in messages %}
                    -------------------------------------------------------
                    ```rfc822
                    From: {{message.headers.from}} {% if message.sender_org_id %}from {{message.sender.org__label}}{% endif %}

                    To: {{message.headers.to}}
                    Subject: {{message.headers.subject}}
                    Date: {{message.headers.date}}

                    {{message.content|strip_lines(prefixes='>')|truncate(5000)}}
                    ```

                    {% endfor %}

          record.create/comment:
            output: record_comment
            inputs:
              record_type: comment
              fields:
                author__context: app
                author_id@int: 0
                target__context: ticket
                target_id@int: {{inputs.ticket.id}}
                is_pinned@int: 1
                is_markdown@int: 1
                comment@text:
                  ### Summary

                  {{results.messages|first.content}}
          await:
            form:
              title: Ticket Summary
              elements:
                textarea/prompt_textarea:
                  default: {{results.messages|first.content}}
      policy_kata@raw:
        commands:
          llm.agent:
            allow@bool: yes
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('message')}}
            allow@bool: yes
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('comment')}}
            allow@bool: yes
```

Click the **Continue** button. Link your Anthropic account and click **Continue** again.

The following records will be created:

 

# Usage

From **Search&nbsp;» Tickets**, open any ticket profile page.

Click the new **Summarize** button at the bottom of the profile page.

 

The AI agent will read the ticket history and summarize it in a paragraph.

 
