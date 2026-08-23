---
id: "workflows-cerb-records-reminders"
title: "Record Reminders"
url: "https://cerb.ai/workflows/cerb.records.reminders/"
summary: "This page provides a detailed guide on creating and managing reminders. The page outlines the usage of the new 'Reminder' button available on record profile pages and card popups, allowing users to quickly set reminders linked to specific records. Additionally, it offers a reference template for building custom record reminder workflows, including detailed instructions on modifying the workflow identifier and using domain-specific prefixes. The page also includes a comprehensive workflow script, detailing the necessary fields, inputs, and commands for creating reminders, as well as toolbar configurations for record profiles and cards."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
- [Reference](#reference)

# Introduction

This workflow creates reminders from record profiles and cards.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Record Reminders**.

# Usage

Navigate to any record profile page or card popup. A new **Reminder** button is in the top toolbar.

 

Clicking on the button allows you to quickly create a new reminder linked to the current record.

 

# Reference

You can build your own record reminders workflow using this template as a reference.

Change occurrences of **cerb.records.reminders** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.records.reminders
  description: Create reminders from record profiles and cards
  website: https://cerb.ai/workflows/cerb.records.reminders/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core
  version: 2025-02-21T00:00:00Z
records:
  automation/workerInteraction:
    fields:
      name: cerb.records.reminders.interaction
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          text/record_type:
            type: freeform
            required@bool: yes
          text/record_id:
            type: number
            required@bool: yes

        start:
          set/load:
            record_id@int: {{inputs.record_id}}
            record__context: {{inputs.record_type}}

          await/input:
            form:
              title: Create Reminder
              elements:
                text/prompt_name:
                  label: Reminder:
                  required@bool: yes
                  type: freeform
                  default: {{record__label}}
                  placeholder: (e.g. "Remind me to write back")
                text/prompt_due:
                  label: When:
                  type: date
                  required@bool: yes
                chooser/prompt_owner_id:
                  label: Who:
                  record_type: worker
                  query@text: isDisabled:n
                  default: {{worker_id}}
                  required@bool: yes

          record.create/reminder:
            output: new_reminder
            inputs@ref:
              record_type: reminder
              fields:
                name: {{prompt_name}}
                remind_at@date: {{prompt_due}}
                worker_id@optional,int: {{prompt_owner_id}}
                links@csv: {{record__context}}:{{record_id}}

          return:
            alert: Reminder created!
      policy_kata@raw:
        commands:
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('reminder')}}
            allow@bool: yes
  toolbar_section/recordProfile:
    fields:
      name: Reminders
      toolbar_name: record.profile
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/reminder:
          label: Reminder
          uri: cerb:automation:cerb.records.reminders.interaction
          icon: pushpin
          inputs:
            record_type: {{record__context}}
            record_id: {{record_id}}
          after:
            refresh_toolbar@bool: no
            refresh_widgets@list: Ticket
  toolbar_section/recordCard:
    fields:
      name: Reminders
      toolbar_name: record.card
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/reminder:
          label: Reminder
          uri: cerb:automation:cerb.records.reminders.interaction
          icon: pushpin
          inputs:
            record_type: {{record__context}}
            record_id: {{record_id}}
          after:
            refresh_toolbar@bool: no
            refresh_widgets@list: Properties
```
