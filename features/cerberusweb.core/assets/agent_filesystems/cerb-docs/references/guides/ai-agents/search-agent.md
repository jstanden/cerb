---
id: "guides-ai-agents-search-agent"
title: "Search agent"
url: "https://cerb.ai/guides/ai-agents/search-agent/"
summary: "This page describes the Search Agent in Cerb, a workflow that uses a natural language query to search records from various record types, such as tickets or virtual filters, using an AI-powered language model from Anthropic. The workflow is integrated with the Anthropic account, allowing users to search records with a natural language query, and the search results are displayed in a user-friendly interface. The workflow can be customized and extended using various tools and commands, such as the `get_record_types` and `get_record_filters` tools, to build and test search queries."
tags: ["guides"]
---
# Introduction

An [AI agent](/docs/automations/commands/llm.agent/) can generate search queries for any record type from natural language.

In this example we'll use Anthropic's Claude Haiku 4.5 model. You can use any language model from popular providers.

https://www.youtube.com/embed/3quC6aj6FqA

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
  name: wgm.agent.search
  description: An intelligent search agent in the search menu.
  version: 2026-08-11T00:55:51Z
  requirements:
    cerb_plugins: cerberusweb.core, 
    cerb_version: >=11.1.2 <12.0
  config:
    chooser/anthropic_account_id:
      label: Anthropic Account:
      record_type: connected_account
      record_query: anthropic
records:
  automation/interaction_search:
    fields:
      name: wgm.agent.search
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          text/query:
            type: freeform
            description: The natural language query describing a desired search query.
            #required@bool: yes
        
        start:
          set/config:
            config@json: {{cerb_workflow_config('wgm.agent.search')|json_encode}} 
            
          outcome/noQuery:
            if@bool: {{inputs.query is empty}}
            then:
              await:
                form:
                  title: Search
                  elements:
                    text/prompt_query:
                      label: What would you like to search for?
                      placeholder: Open tickets from Fiaflux so far this year
                      required@bool: yes
                      type: freeform
        
          # Load all possible record types
          data.query/record_types:
            output: results
            inputs:
              query@text:
                type:record.types
                format:dictionaries
            on_success:
              set:
                prompt_record_types@text:
                  {{results.data|column('uri')|sort|join(', ')}}
          
          # Cascade the search query
          set/init:
            search_query: {{inputs.query|default(prompt_query)}}
          
          llm.agent:
            output: results
            inputs:
              llm:
                anthropic:
                  #model: claude-sonnet-4-20250514
                  model: claude-haiku-4-5
                  authentication: cerb:connected_account:{{config.anthropic_account_id}}
                  max_tokens@int: 2048
              system_prompt@text:
                You are a search agent within that lives within Cerb helpdesk software.
                
                You are talking to a user named {{worker_first_name}} (ID: {{worker_id}}).
                The current date/time is: {{'now'|date('r')}}
        
                Your job is to return search query syntax matching natural language queries for a given record type.
                
                Cerb query syntax uses the space-delimited format `filterName:expression otherFilter:expression`.
                
                ALWAYS double quote string expressions (especially with spaces).
                
                ALWAYS handle multiple-value expressions with square brackets ([]) and not the 'OR' operator. `expression:[value1,"value2 with spaces"]`
                
                Date filters use relative expressions ("-1 year") or absolute ranges ("2025-01-01 to now").
                
                YOU MUST use one of these record types:
                {{prompt_record_types}}
                
                Use the `get_record_types` tool to get filters by record type.
                
                ALWAYS check record filters. Don't guess.
                
                If the user asks for "my" they always mean either "owner:me" or "owner.worker:me" (check record type filters).
                
                You MAY search into linked `virtual` filters with parenthesis. For instance `org:(country:"United States")` on `ticket` records.
                ALWAYS use the `get_record_types` tool to see what filters you can use for the linked record type.
                
                Use your tools to build and test queries.
                
                Always use the `return_query` tool to return just the query. Then you're done.
                
                <examples>
                org:(country:["Germany","United States"]) created:"big bang to -2 years" owner:me
                </examples>
                
              messages:
                message:
                  role: user
                  content: {{search_query}}
              tools:
                #automation/get_record_types:
                # uri: cerb:automation:wgm.agent.search.tool.getRecordTypes
                automation/get_record_filters:
                  uri: cerb:automation:wgm.agent.search.tool.getRecordFilters
                automation/test_search_query:
                  uri: cerb:automation:wgm.agent.search.tool.testSearchQuery
                tool/return_query:
                  description: Use this tool to return the generated search query.
                  parameters:
                    string/record_type:
                      description: The record type to search.
                      required@bool: yes
                    string/search_query:
                      description: The generated search query without extra formatting or commentary.
                      required@bool: yes
            on_tool:
              decision/tool:
                outcome/return_query:
                  if@bool: {{__tool.name == 'return_query'}}
                  then:
                    return:
                      search:
                        record_type: {{__tool.parameters.record_type}}
                        query: {{__tool.parameters.search_query}}
                      data:
                        query: {{__tool.parameters.search_query}}
        
                outcome/else:
                  then:
                    await:
                      form:
                        title: Search Agent
                        elements:
                          llmTranscript/prompt_transcript:
                            session_id: {{results.session_id}}
                            tool_labels:
                              get_record_filters@raw:
                                Looking up filters for {{record_type}} records
                              test_search_query@raw:
                                Verifying the {{record_type}} query:
                                {{record_query}}
                              return_query@raw:
                                Returning the query:
                                {{query}}
                          submit:
                            is_automatic@bool: yes
          
      policy_kata@raw:
        commands:
          data.query:
            deny/type@bool: {{query.type != 'record.types'}}
            allow@bool: yes
          llm.agent:
            allow@bool: yes
  automation/tool_get_record_types:
    fields:
      name: wgm.agent.search.tool.getRecordTypes
      extension_id: cerb.trigger.llm.tool
      description: Return a list of record type names and IDs
      script@raw:
        start:
          data.query/getTypes:
            output: results
            inputs:
              query@text:
                type:record.types
                limit:1000
                exclude_custom:no
                options:[search]
                format:dictionaries
          return:
            content@text:
              These record types are available:
              
              {% for record_type in results.data|sort((a,b) => a.uri <=> b.uri) %}
              {{record_type.uri}}: {{record_type.label_plural}}
              {% endfor %}
      policy_kata@raw:
        commands:
          data.query:
            allow@bool: yes
  automation/tool_get_record_filters:
    fields:
      name: wgm.agent.search.tool.getRecordFilters
      extension_id: cerb.trigger.llm.tool
      description: Return a list of filters for a given record type
      script@raw:
        inputs:
          text/record_type:
            type: record_type
            description: The ID of the record type (e.g. `ticket`)
            required@bool: yes
        
        start:
          data.query/getFields:
            output: results
            inputs:
              query@text:
                type:record.filters
                of:{{inputs.record_type}}
                exclude_links:yes
                format:dictionaries
          return:
            content@text:
              These filters are available on `{{inputs.record_type}}` records:
              
              {% for filter_key, filter_data in results.data %}
              ---
              filter: {{filter_key}}
              type: {{filter_data.type}}
              {% if filter_data.examples and filter_data.examples|first is not iterable %}
              examples:
              {{filter_data.examples|join('\n')|indent('* ')}}
              {% elseif filter_data.examples and filter_data.examples[0].type == 'list' %}
              examples:
              {{filter_data.examples[0].values|kata_encode|indent('* ')}}
              {% endif %}
              
              {% endfor %}
      policy_kata@raw:
        commands:
          data.query:
            allow@bool: yes
  automation/tool_test_search_query:
    fields:
      name: wgm.agent.search.tool.testSearchQuery
      extension_id: cerb.trigger.llm.tool
      description@text:
      script@raw:
        inputs:
          text/record_type:
            type: record_type
            description: The record type to search for
            required@bool: yes
          text/record_query:
            type: freeform
            description: The search query to test
            required@bool: yes
        
        start:
          data.query/search:
            output: results
            inputs:
              query@text:
                type:worklist.records
                of:{{inputs.record_type}}
                query:({{inputs.record_query}})
                format:dictionaries
            on_success:
              return:
                content: SUCCESS: Found {{results._.paging.page.rows.of}} records
            on_error:
              return:
                content: ERROR: {{results.error}}
      policy_kata@raw:
        commands:
          data.query:
            deny/type@bool: {{query.type != 'worklist.records'}}
            allow@bool: yes
  toolbar_section/toolbar_search:
    fields:
      name: Search Agent
      toolbar_name: global.search
      priority@int: 0
      is_disabled: 0
      toolbar_kata@raw:
        interaction/c9bghh:
          label: Search with AI
          uri: cerb:automation:wgm.agent.search
          icon: magic
          #hidden@bool: {{row_selections ? 'no' : 'yes'}}
          #badge: 0
          #after:
            #refresh_widgets@list: Actions
```

Click the **Continue** button.

Link your Anthropic account and click **Continue** again.

The following records will be created:

 

# Usage

From **Search**, select "Search with AI".

 

Type what you're looking for in natural language and the search agent will construct the query for you and display the results:

 
