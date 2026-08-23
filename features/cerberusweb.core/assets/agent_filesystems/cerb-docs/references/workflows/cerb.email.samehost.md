---
id: "workflows-cerb-email-samehost"
title: "Similar Senders"
url: "https://cerb.ai/workflows/cerb.email.samehost/"
summary: "This page provides an implementation guide for the 'Find contacts from the same host' workflow in Cerb, which extracts emails from the same hostname and displays a list of matching contacts."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)

# Introduction

This workflow adds a worker interaction and a toolbar button on email record cards. The interaction finds any other contacts in the system with emails from the same hostname.

# Installation

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» (empty)** and entering in the following Workflow KATA:

```
workflow:
  name: cerb.email.samehost
  description: Find contacts from the same host as a given profile
  version: 2026-05-25T20:00:00Z
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core
records:
  automation/toolbarinteraction:
    fields:
      name: cerb.email.samehost.interaction
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          record/email:
            record_type: address
            required@bool: yes
          
        start:
          await:
            form:
              title: Emails from the same host
              elements:
                sheet/prompt_results:
                  label: Results:
                  data:
                    automation:
                      uri: cerb:automation:cerb.data.records
                      inputs:
                        record_type: address
                        query_required: host:"{{inputs.email.host}}"
                  limit: 10
                  schema:
                    layout:
                      title_column: _label
                      headings@bool: yes
                      paging@bool: yes
                    columns:
                      card/_label:
                        params:
                          bold@bool: yes
                      text/email:
                      card/org_id:
                        label: Organization
                      text/num_nonspam:
                        label: Number of Messages
                
            #on_simulate:
            #on_success:
            #on_error:
      policy_kata@raw:
        commands:
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('address')}}
  toolbar_section/profiletoolbar:
    fields:
      name: Similar Email
      toolbar_name: record.card
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/l1nn3e:
          label: Similar
          uri: cerb:automation:cerb.email.samehost.interaction
          inputs:
            email: {{record_id}}
          hidden@bool: {{record__type is not record type ('address')}}
```

# Usage

Load up any email record and click the "Similar" button.

 

You will be presented with a sheet of every other contact with emails from that same hostname.

 
