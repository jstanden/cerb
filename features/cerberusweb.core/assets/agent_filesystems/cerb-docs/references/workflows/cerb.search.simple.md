---
id: "workflows-cerb-search-simple"
title: "Simple Ticket Search"
url: "https://cerb.ai/workflows/cerb.search.simple/"
summary: "This page provides a comprehensive guide on the Simple Ticket Search workflow in Cerb, which offers a user-friendly, point-and-click interface for searching tickets without the need for complex search queries. It includes sections on introduction, installation, usage, and reference. The workflow is integrated into Cerb version 11.0 and above, and can be enabled through the Search menu. Users can easily modify search filters and update results through an intuitive popup interface. Additionally, the page offers a template for creating custom Simple Search workflows, detailing how to adapt the workflow identifier and providing a structured script for implementation."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
- [Reference](#reference)

# Introduction

This workflow adds a simplified point-and-click ticket search popup without using search queries.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Simple Ticket Search**.

# Usage

In the **Search** menu in the top right of every page, click **Search Tickets**.

 

The popup displays matching ticket records along with the generated search query at the top.

 

Click the **Edit Filters** button in the bottom right to change the search filters.

 

Click the **Update** button to return to the search results.

# Reference

You can build your own Simple Search workflow using this template as a reference.

Change occurrences of **cerb.search.simple** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.search.simple
  version: 2026-08-13T00:00:00Z
  description: Simplified point-and-click ticket search popup without using search queries
  website: https://cerb.ai/workflows/cerb.search.simple/
  requirements:
    cerb_version: >=12.0 <12.1
    cerb_plugins: cerberusweb.core,
records:
  automation/interactionSearchTickets:
    fields:
      name: cerb.search.simple.interaction
      extension_id: cerb.trigger.interaction.worker
      description: Simplified point-and-click ticket search popup without using search queries
      script@raw:
        start:
          set:
            filter_status@csv: o
            filter_created: -1 week to now

          while:
            if@bool: yes
            do:
              set:
                search_query@text,trim:
                  {% if filter_subject %}
                  subject:"{{filter_subject}}"
                  {% endif %}
                  {% if filter_mask %}
                  mask:{{filter_mask|split(' ')|first}}
                  {% endif %}
                  {% if filter_content %}
                  text:"{{filter_content}}"
                  {% endif %}
                  {% if filter_status %}
                  status:[{{filter_status|join(',')}}]
                  {% endif %}
                  {% if filter_created is not empty %}
                  created:"{{filter_created}}"
                  {% endif %}
                  {% if filter_groups is not empty %}
                  group:(name:[{{filter_groups|map((group_name) => '"' ~ group_name ~ '"')|join(',')}}])
                  {% endif %}
                  {% if filter_participants is not empty %}
                  participant:(email:[{{filter_participants|join(',')}}])
                  {% endif %}
                  sort:-updated

              await/search:
                form:
                  title: Search: Tickets
                  elements:
                    say:
                      content@text:
                        #### {{search_query|split_crlf|join(' ')}}
                    sheet:
                      data:
                        automation:
                          uri: cerb:automation:cerb.data.records
                          inputs:
                            record_type: ticket
                            query_required@key: search_query
                      limit: 15
                      schema:
                        layout:
                          filtering@bool: no
                          headings@bool: no
                          paging@bool: yes
                          style: table
                          title_column: _label
                        columns:
                          selection/id:
                            params:
                              mode: multiple
                          card/_label:
                            params:
                              icon:
                                record_uri@raw: cerb:group:{{group_id}}
                          text/status:
                          card/initial_message_sender__label:
                            params:
                              underline@bool: no
                          card/group__label:
                            params:
                              underline@bool: no
                          card/bucket__label:
                            params:
                              underline@bool: no
                          date/updated:
                      toolbar:

                    submit/prompt_action:
                      buttons:
                        continue/refresh:
                          label: Refresh
                          icon: refresh
                          value: update
                        continue/editFilters:
                          label: Edit Filters
                          icon: gear
                          value: edit-filters
                          style: secondary

              decision/action:
                outcome/editFilters:
                  if@bool: {{prompt_action == 'edit-filters'}}
                  then:
                    await:
                      form:
                        title: Add Filter
                        elements:
                          text/filter_mask:
                            label: Mask:
                            default@key: filter_mask

                          text/filter_subject:
                            label: Subject:
                            default@key: filter_subject

                          text/filter_content:
                            label: Content:
                            default@key: filter_content

                          sheet/filter_status:
                            label: Status:
                            default@key: filter_status
                            limit: 5
                            data:
                              open:
                                key: o
                                label: open
                              waiting:
                                key: w
                                label: waiting
                              closed:
                                key: c
                                label: closed
                              deleted:
                                key: d
                                label: deleted
                            schema:
                              layout:
                                style: grid
                                filtering@bool: no
                                headings@bool: no
                                paging@bool: no
                              columns:
                                selection/key:
                                  params:
                                    mode: multiple
                                text/label:

                          text/filter_created:
                            type: freeform
                            label: Created between:

                          sheet/filter_groups:
                            label: Group:
                            default@key: filter_groups
                            limit: 30
                            data:
                              automation:
                                uri: cerb:automation:cerb.data.records
                                inputs:
                                  record_type: group
                                  query@text:
                                    sort:name
                            schema:
                              layout:
                                style: grid
                                filtering@bool: yes
                                headings@bool: no
                                paging@bool: yes
                              columns:
                                selection/name:
                                  params:
                                    mode: multiple
                                text/_label:
                                  params:
                                    icon:
                                      record_uri@raw: cerb:group:{{id}}
                                    bold@bool: yes

                          sheet/filter_participants:
                            label: Participants:
                            default@key: filter_participants
                            limit: 5
                            data:
                              automation:
                                uri: cerb:automation:cerb.data.records
                                inputs:
                                  record_type: address
                                  query@text:
                                    isBanned:n isDefunct:n sort:-nonspam
                            schema:
                              layout:
                                style: table
                                filtering@bool: yes
                                headings@bool: no
                                paging@bool: yes
                              columns:
                                selection/email:
                                  params:
                                    mode: multiple
                                text/_label:
                                  params:
                                    icon:
                                      record_uri@raw: cerb:address:{{id}}
                                    bold@bool: yes

                          submit:
                            buttons:
                              continue/update:
                                label: Update
                                icon: circle-ok

      policy_kata@text:
  toolbar_section/toolbarSearch:
    fields:
      name: Ticket Search
      toolbar_name: global.search
      priority@int: 25
      is_disabled: 0
      toolbar_kata@raw:
        interaction/searchTickets:
          label: Search Tickets
          uri: cerb:automation:cerb.search.simple.interaction
          icon: search
```
