---
id: "workflows-wgm-example-newticketinteractionwithsnippets"
title: "New Ticket Templates"
url: "https://cerb.ai/workflows/wgm.example.new_ticket_interaction_with_snippets/"
summary: "This workflow shows how to combine snippets with automation interactions to create a ticket creation system. It includes a snippet template with various prompt types (text, picklist, checkbox), an automation that generates interactive forms based on snippet prompts, and a workspace widget for easy access. The workflow demonstrates dynamic form generation, email parsing, and ticket creation from templated content."
tags: ["workflows"]
---
- [Introduction](#introduction)
  - [Key Components](#key-components)
    - [Snippet Template](#snippet-template)
    - [Automation](#automation)
    - [Workspace Integration](#workspace-integration)

  - [Use Cases](#use-cases)

- [Importing the workflow](#importing-the-workflow)
- [Usage](#usage)

# Introduction

This workflow demonstrates how to create a flexible ticket creation system using interactive snippet templates in Cerb. It's particularly useful for organizations that need to standardize ticket creation while allowing for customization of specific details.

The workflow combines several Cerb features to create a seamless user experience:

- **Snippet Templates**: Pre-formatted email templates with dynamic prompts for customization
- **Interactive Automations**: Generate forms based on snippet prompts and handle user input
- **Email Parsing**: Convert completed templates into properly formatted email messages
- **Ticket Creation**: Automatically create tickets from the parsed email content
- **Workspace Integration**: Provide easy access through toolbar widgets

## Key Components

### Snippet Template

The workflow creates a snippet template with various prompt types:

- **Text prompts**: For free-form input like summaries or descriptions
- **Picklist prompts**: For selecting from predefined options (e.g., priority, category)
- **Checkbox prompts**: For yes/no selections

### Automation

The core automation (`wgm.example.new_ticket_interaction_with_snippets.createTicketFromSnippet`) handles the entire process:

1. Validates the snippet is plaintext type
2. Dynamically generates form fields based on snippet prompts
3. Presents an interactive form to the user
4. Processes the user's responses and applies them to the template
5. Creates and parses an email message
6. Creates a new ticket and confirms completion

### Workspace Integration

A workspace page with toolbar widgets provides quick access to multiple template variations, allowing users to create tickets with different default settings.

## Use Cases

This workflow is ideal for:

- **Support teams** creating tickets from customer reports
- **Sales teams** generating leads from inquiry templates
- **Project managers** creating standardized project tickets
- **Any team** needing consistent ticket creation with customizable details

The dynamic form generation means you can modify snippet prompts without changing the automation code, making it highly maintainable and adaptable to changing requirements.

# Importing the workflow

Navigate to **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Empty**.

Click the **Update Template** button at the top of the popup.

Paste the following workflow KATA into the large text box:

```
workflow:
  name: wgm.example.new_ticket_interaction_with_snippets
  description: A workflow demonstrating how to create tickets from interactive snippet templates with dynamic prompts and form fields
  website: https://cerb.ai/workflows/wgm.example.custom_records.academia/
  version: 2026-05-25T20:00:00Z
  requirements:
    cerb_version: >=11.1.3 <12.1
    cerb_plugins: cerberusweb.core
records:
  snippet/snippetTemplate:
    fields:
      title: Automatic Email Template
      context@text:
      owner__context: app
      owner_id: 0
      prompts_kata@raw:
        # Add prompted placeholders here
        # (use Ctrl+Shift for autocompletion)
        text/summary:
          label: Summary:
          required@bool: yes
          params:
            multiple@bool: yes
        picklist/prompt_picklist:
          label: Picklist:
          default: green
          required@bool: no
          params:
            options@list:
              red
              green
              blue
        checkbox/prompt_checkbox:
          label: Checkbox:
          required@bool: no
          default@bool: yes
      content@raw:
        ============================================
        EMAIL TEMPLATE
        ============================================
        
        Summary:
        
        {{summary}}
        
        Checkbox: {{prompt_checkbox ? 'yes' : 'no'}}
        {% if prompt_picklist %}
        Picklist: {{prompt_picklist}}
        
        {% endif %}
  automation/scriptCreateTicket:
    fields:
      name: wgm.example.new_ticket_interaction_with_snippets.createTicketFromSnippet
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          record/snippet:
            record_type: snippet
            required@bool: yes
          text/to:
            type: email
            required@bool: yes
          text/from:
            type: email
            default: customer@cerb.example
        
        start:
          outcome/isNotPlaintext:
            if@bool: {{inputs.snippet.context is not empty}}
            then:
              await/error:
                form:
                  title: Error
                  elements:
                    say:
                      content: The selected snippet must be of type: plaintext
              error: The selected snippet must be of type: plaintext
          
          # Create interaction form elements for the snippet prompts
          set/elements:
            elements@kata:
              text/form_subject:
                label: Subject:
                required@bool: yes
              {% for prompt in inputs.snippet.prompts %}
              {% if 'checkbox' == prompt.type %}
              sheet/form_{{prompt.name}}:
                label: {{prompt.label}}
                {% if prompt.required %}
                required@bool: yes
                {% endif %}
                default: {{prompt.default ? '1' : '0'}}
                data:
                  true:
                    label: yes
                    value: 1
                  false:
                    label: no
                    value: 0
                schema:
                  layout:
                    style: grid
                    headings@bool: no
                    paging@bool: no
                  columns:
                    selection/value:
                      params:
                        mode: single
                    text/label:
              {% elseif 'picklist' == prompt.type %}
              sheet/form_{{prompt.name}}:
                label: {{prompt.label}}
                {% if prompt.required %}
                required@bool: yes
                {% endif %}
                default: {{prompt.default}}
                data@json: {{prompt.params.options|map((v) => {'label':v,'value':v})|json_encode}}
                schema:
                  layout:
                    style: grid
                    headings@bool: no
                    paging@bool: no
                  columns:
                    selection/value:
                      params:
                        mode: single
                    text/label:
              {% else %}
              text/form_{{prompt.name}}:
                label: {{prompt.label}}
                {% if prompt.required %}
                required@bool: yes
                {% endif %}
                
              {% endif %}
              {% endfor %}
          
          await:
            form:
              title: Interaction
              elements@key: elements
        
          set/dict:
            template_dict@kata:
              {% for prompt in inputs.snippet.prompts %}
              {{prompt.name}}@key: form_{{prompt.name}}
              {% endfor %}
        
          kata.parse:
            output: parsed_snippet
            inputs:
              kata:
                template: {{inputs.snippet.content}}
              dict@key: template_dict
        
          email.parse:
            output: results
            inputs:
              message@text:
                From: {{inputs.from}}
                To: {{inputs.to}}
                MIME-Version: 1.0
                Content-Type: text/plain; charset="UTF-8"
                Content-Transfer-Encoding: quoted-printable
                Subject: =?UTF-8?Q?{{form_subject|qp_encode}}?=
                
                {{parsed_snippet.template|qp_encode}}
          
          await/confirm:
            form:
              title: Ticket Created
              elements:
                say:
                  content@text:
                    New ticket created!
                sheet:
                  data:
                    0@key: results
                  schema:
                    layout:
                      headings@bool: no
                      paging@bool: no
                    columns:
                      card/ticket_id:
                        label: Ticket
                        params:
                          context: ticket
                          id_template@raw: {{id}}
                          label_template@raw: {{_label}}
          
          # [TODO] Send an API request
          # http.request:
      policy_kata@raw:
        commands:
          email.parse:
            allow@bool: yes
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('draft')}}
            allow@bool: yes
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('snippet')}}
            allow@bool: yes
  workspace_page/pageEmailTemplates:
    fields:
      name: Email Templates
      extension_id: core.workspace.page.workspace
      owner__context: workflow
      owner_id: {{workflow_id}}
  workspace_tab/tabExample:
    fields:
      name: Example
      page_id: {{records.pageEmailTemplates.id}}
      extension_id: core.workspace.tab.dashboard
      pos@int: 99
      params:
        layout@text:
        prompts_kata@text:
  workspace_widget/widgetToolbar:
    fields:
      label: Email templates
      tab_id: {{records.tabExample.id}}
      extension_id: core.workspace.widget.form_interaction
      pos@int: 1
      width_units@int: 4
      zone: content
      params:
        interactions_kata@text:
          menu/templates:
            label: Create ticket
            icon: envelope
            items:
              interaction/createTicket:
                label: Template #1
                uri: cerb:automation:wgm.example.new_ticket_interaction_with_snippets.createTicketFromSnippet
                inputs:
                  to: support@cerb.example
                  from: customer@cerb.example
                  snippet: {{records.snippetTemplate.id}}
              interaction/summary:
                label: Template #2
                uri: cerb:automation:wgm.example.new_ticket_interaction_with_snippets.createTicketFromSnippet
                inputs:
                  to: support@cerb.example
                  from: boss@cerb.example
                  snippet: {{records.snippetTemplate.id}}
        is_popup: 1
```

Click the **Continue** button three times.

You should see results like the following:

 

# Usage

From **Search&nbsp;» Snippets** open the **Automatic Email Template** sample snippet.

Any prompted placeholders you define here will be used by the interaction to create the new ticket message.

 

From **Search&nbsp;» Workspace Pages** open the **Email Templates** page.

Clicking on the **Create ticket** button shows an interaction menu.

 

You can add or modify email templates and override from/to/snippet by editing the **Email templates** toolbar widget.

The `snippet:` ID must reference a plaintext snippet.

 
