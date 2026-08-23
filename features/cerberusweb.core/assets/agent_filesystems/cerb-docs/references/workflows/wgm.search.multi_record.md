---
id: "workflows-wgm-search-multirecord"
title: "Smart Multi-Record Search"
url: "https://cerb.ai/workflows/wgm.search.multi_record/"
summary: "This page describes the Smart Multi-record Search workflow in Cerb, which enables a smart search that can search multiple record types (organizations, email addresses, tickets, and workers) simultaneously based on a single query. Access the Smart Search interaction from the global menu at the bottom right corner of any page, allowing for efficient searching across multiple record types with a single query."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)

# Introduction

This workflow creates a smart search interaction and places it on the global menu. The interaction searches multiple different record types at once depending on the query given to it.

# Installation

Navigate to **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Empty**.

Paste the following KATA into the large text box:

```
workflow:
  name: wgm.search.multi_record
  version: 2026-05-25T20:00:00Z
  description: Multi-record smart search
  website: https://cerb.ai/resources/workflows/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core 
records:
  automation/search:
    fields:
      name: wgm.search.multi_record
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        start:
          while:
            if@bool: true
            do:
              set/initial:
                elements:
                  text/prompt_query:
                    type: freeform
                    label: Search:
                    placeholder: (e.g. customer@example.com, #ABC-12345-678, Company Name, Worker)
                    default@key: prompt_query
              
              outcome/isOrg@ref: isOrg
              outcome/isEmail@ref: isEmail
              outcome/isTicket@ref: isTicket
              outcome/isWorker@ref: isWorker
                    
              var.push/submit:
                inputs:
                  key: elements
                  value:
                    submit:
                      buttons:
                        continue/search:
                          label: Search
                          icon: search
                          icon_at: start
                          value: search                
                        
              await/query:
                form:
                  title: Smart Search
                  elements@key: elements
        
        &isEmail:
          if@bool: {{prompt_query is not prefixed ('#')}}
          then:
            data.query:
              output: results
              inputs:
                query@text:
                  type:worklist.records
                  of:address
                  query:(${query} sort:-nonspam limit:20)
                  format:dictionaries
                query_params:
                  query@key: prompt_query
            
            outcome/notEmpty:
              if@bool: {{results.data is not empty}}
              then:
                var.push:
                  inputs:
                    key: elements
                    value:
                      sheet/addresses:
                        label: Email Addresses
                        data@key: results:data
                        limit: 5
                        schema:
                          layout:
                            headings@bool: no
                            paging@bool: no
                            #filtering@bool: {{results._.paging.page.rows.of > 10}}
                          columns:
                            card/_label:
                              params:
                                image@bool: yes
                                bold@bool: yes
                            card/org_id:
                              params:
                                image@bool: yes
        
        &isOrg:
          if@bool: {{prompt_query is not prefixed ('#')}}
          then:
            data.query:
              output: results
              inputs:
                query@text:
                  type:worklist.records
                  of:org
                  query:(${query} sort:name limit:20)
                  format:dictionaries
                query_params:
                  query@key: prompt_query
            
            outcome/notEmpty:
              if@bool: {{results.data is not empty}}
              then:
                var.push:
                  inputs:
                    key: elements
                    value:
                      sheet/orgs:
                        label: Organizations
                        data@key: results:data
                        limit: 5
                        schema:
                          layout:
                            headings@bool: no
                            paging@bool: no
                            #filtering@bool: {{results._.paging.page.rows.of > 10}}
                          columns:
                            card/_label:
                              params:
                                image@bool: yes
                                bold@bool: yes
                            text/country:
        
        &isTicket:
          if@bool: {{prompt_query is prefixed ('#')}}
          then:
            data.query:
              output: results
              inputs:
                query@text:
                  type:worklist.records
                  of:ticket
                  query:(mask:${mask} sort:-updated limit:20)
                  format:dictionaries
                query_params:
                  mask: {{prompt_query[1:]}}*
            
            outcome/notEmpty:
              if@bool: {{results.data is not empty}}
              then:
                var.push:
                  inputs:
                    key: elements
                    value:
                      sheet/tickets:
                        label: Tickets
                        data@key: results:data
                        limit: 5
                        schema:
                          layout:
                            headings@bool: no
                            paging@bool: no
                            #filtering@bool: {{results._.paging.page.rows.of > 10}}
                            title_column: _label
                          columns:
                            card/_label:
                              params:
                                image@bool: yes
                                bold@bool: yes
                            card/initial_message_sender__label:
                            card/group__label:
                            text/status:
                            date/updated:
        
        &isWorker:
          if@bool: {{prompt_query is not prefixed ('#')}}
          then:
            data.query:
              output: results
              inputs:
                query@text:
                  type:worklist.records
                  of:worker
                  query:(${terms} sort:-updated limit:20)
                  format:dictionaries
                query_params:
                  terms: {{prompt_query}}
            
            outcome/notEmpty:
              if@bool: {{results.data is not empty}}
              then:
                var.push:
                  inputs:
                    key: elements
                    value:
                      sheet/workers:
                        label: Workers
                        data@key: results:data
                        limit: 5
                        schema:
                          layout:
                            headings@bool: no
                            paging@bool: no
                            #filtering@bool: {{results._.paging.page.rows.of > 10}}
                          columns:
                            card/_label:
                              params:
                                image@bool: yes
                                bold@bool: yes
                            text/title:
                            text/location:                    
      policy_kata@raw:
        commands:
          data.query:
            deny/type@bool: {{query.type != 'worklist.records'}}
            allow@bool: yes
  toolbar_section/global_menu:
    fields:
      name: Smart Search
      toolbar_name: global.menu
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/search:
          label: Smart Search
          uri: cerb:automation:wgm.search.multi_record
          icon: search
          #hidden@bool: {{row_selections ? 'no' : 'yes'}}
          #badge: 0
          after:
            #refresh_widgets@list: Actions
```

Click the **Continue** button.

You should see output like the following:

 

# Usage

The Smart Search interaction can be accessed from the global menu now found in the bottom right corner of any page.

 

It will search multiple record types all at once with a single query.

 
