---
id: "workflows-cerb-capturefeedback"
title: "Capture Feedback"
url: "https://cerb.ai/workflows/cerb.capture_feedback/"
summary: "This webpage provides a comprehensive guide on the 'Capture Feedback' workflow in Cerb, which is integrated into Cerb 11.0 and later versions. It introduces a custom record type for feedback and an interaction for capturing feedback from email messages. The page details the installation process, which involves enabling the workflow through the Cerb interface, and explains how to use the feature by quoting entire messages or highlighted passages as feedback. Users can select a sentiment for the feedback and log it, with all captured feedback accessible through the search function. Additionally, the page offers a reference for building custom feedback capture workflows, including a detailed template and instructions for customization. The workflow includes various fields for feedback records, such as sentiment, author, and source URL, and provides a script for automating the feedback capture process."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
- [Reference](#reference)

# Introduction

This workflow adds a **Feedback** custom record type and a **Capture Feedback** interaction when reading email messages.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Capture Feedback**.

# Usage

When viewing any ticket profile, a new **Capture Feedback** button is available on received email messages.

 

By default, the entire message will be quoted as feedback. You can also highlight a passage to use as the quote before clicking **Capture Feedback**.

 

Select a **Sentiment** and click the **Continue** button to log the feedback.

Captured feedback records can be found at **Search&nbsp;» Feedback**.

# Reference

You can build your own capture feedback workflow using this template as a reference.

Change occurrences of **cerb.capture\_feedback** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.capture_feedback
  version: 2025-02-21T00:00:00Z
  description: Capture user feedback while reading email messages
  website: https://cerb.ai/workflows/cerb.capture_feedback/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core

records:
  custom_record/feedback:
    fields:
      name: Feedback
      name_plural: Feedback
      uri: feedback
      params:
        owners:
          contexts@csv: cerberusweb.contexts.app
        options@csv: comments
  custom_field/feedback_comment:
    fields:
      name: Comment
      context: feedback
      uri: comment
      type: T
  custom_field/feedback_sentiment:
    fields:
      name: Sentiment
      context: feedback
      uri: sentiment
      type: D
      params:
        options@csv: Praise, Neutral, Criticism
  custom_field/feedback_author:
    fields:
      name: Author
      context: feedback
      uri: author
      type: L
      params:
        context: cerberusweb.contexts.address
  custom_field/feedback_source_url:
    fields:
      name: Source URL
      context: feedback
      uri: source_url
      type: U
  custom_field/feedback_is_closed:
    fields:
      name: Is Closed
      context: feedback
      uri: is_closed
      type: C

  card_widget/card_record_fields:
    fields:
      name: Properties
      record_type: feedback
      extension_id: cerb.card.widget.fields
      pos: 1
      width_units: 4
      zone: content
      extension_params:
        context: feedback
        context_id@raw: {{record_id}}
        properties:
          0@list:
            cf_{{records.feedback_author.id}}
            created
            updated
            cf_{{records.feedback_is_closed.id}}
            owner_id
            cf_{{records.feedback_sentiment.id}}
        links:
          show: 1

  profile_tab/tab_overview:
    fields:
      name: Overview
      context: feedback
      extension_id: cerb.profile.tab.dashboard
      pos@int: 1
      extension_params:
        layout: sidebar_left

  profile_widget/profile_record_fields:
    fields:
      name: Feedback
      extension_id: cerb.profile.tab.widget.fields
      profile_tab_id: {{records.tab_overview.id}}
      pos: 1
      width_units: 4
      zone: content
      extension_params:
        context: feedback
        context_id@raw: {{record_id}}
        properties:
          0@list:
            cf_{{records.feedback_author.id}}
            created
            updated
            cf_{{records.feedback_is_closed.id}}
            owner_id
            cf_{{records.feedback_sentiment.id}}
        links:
          show: 1

  automation/captureFeedback:
    fields:
      name: cerb.captureFeedback.interaction
      extension_id: cerb.trigger.interaction.worker
      description: Capture user feedback while reading email messages
      script@raw:
        inputs:
          record/message:
            record_type: message
            required@bool: yes
            expand: sender__label

        start:
          set:
            sentiments:
              praise:
                name: Praise
              neutral:
                name: Neutral
              criticism:
                name: Criticism
          await:
            form:
              title: Capture Feedback
              elements:
                textarea/prompt_feedback:
                  label: Feedback:
                  required@bool: yes
                  default@text,trim:
                    {% if caller_params.selected_text is not empty %}
                    {{caller_params.selected_text}}
                    {% else %}
                    {{inputs.message.content}}
                    {% endif %}
                sheet/prompt_sentiment:
                  label: Sentiment:
                  required@bool: yes
                  data@key: sentiments
                  default: Neutral
                  schema:
                    layout:
                      style: grid
                      headings@bool: no
                      paging@bool: no
                    columns:
                      selection/key:
                        params:
                          mode: single
                          value_key: name
                      text/name:
                        params:
                          bold@bool: yes

          record.create/feedback:
            output: record_feedback
            inputs:
              # See: https://cerb.ai/docs/records/types/feedback/#records-api
              record_type: feedback
              fields:
                name: {{prompt_feedback|truncate(200)}}
                author: {{inputs.message.sender_id}}
                comment: {{prompt_feedback}}
                source_url: {{inputs.message.record_url}}
                sentiment: {{prompt_sentiment}}
                is_closed: 0
                links@list:
                  message:{{inputs.message.id}}
            on_error:
              await:
                form:
                  title: Error
                  elements:
                    say/error:
                      content@text:
                        ## An unexpected error occurred.
                        ~~~
                        {{record_feedback.error}}
                        ~~~
              error: {{record_feedback.error}}

          return:
            alert: Feedback captured!

      policy_kata@raw:
        commands:
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('feedback')}}
            allow: yes

  toolbar_section/mail_read:
    fields:
      name: Capture Feedback
      toolbar_name: mail.read
      priority: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/captureFeedback:
          label: Capture Feedback
          uri: cerb:automation:cerb.captureFeedback.interaction
          icon: conversation
          hidden@bool: {{message_is_outgoing}}
          inputs:
            message: {{message_id}}
```
