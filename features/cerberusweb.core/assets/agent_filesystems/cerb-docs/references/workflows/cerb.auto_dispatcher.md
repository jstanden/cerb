---
id: "workflows-cerb-autodispatcher"
title: "Auto Dispatcher"
url: "https://cerb.ai/workflows/cerb.auto_dispatcher/"
summary: "This page provides a comprehensive guide on the Auto Dispatcher workflow in Cerb, which allows workers to efficiently request their next assignment with a simple button click on a workspace. The workflow prioritizes and assigns tickets based on group memberships, importance, and duration of being open, ensuring a consistent and non-overlapping handling of issues. The guide covers installation, usage, and customization of the Auto Dispatcher, including adding workspace widgets, managing assignment rejections, and customizing rejection reasons and work order queries. It also includes a reference section for building custom auto-dispatcher workflows using the provided template. The Auto Dispatcher is integrated into Cerb 11.0+ and can be enabled through the Cerb interface."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Add the workspace widget](#add-the-workspace-widget)
  - [Waiting shortcuts](#waiting-shortcuts)
  - [Assignment rejections](#assignment-rejections)
  - [Customizing the assignment rejection reasons](#customizing-the-assignment-rejection-reasons)
  - [Customizing the work order](#customizing-the-work-order)

- [Reference](#reference)

# Introduction

The **cerb.auto\_dispatcher** workflow enables workers to simply request their next assignment by clicking a button on a workspace.

Once you have more than a few simultaneous [workers](/docs/workers/) handling [tickets](/docs/tickets/) in Cerb, it becomes inefficient for them to manually select their own next assignments from shared [worklists](/docs/worklists/). Workers may inadvertently choose the same ticket to work on, leading to wasted effort. This can also negatively impact customer satisfaction and service-level agreements, as workers may cherry-pick newer, simpler, or more interesting tickets while other issues frequently languish.

Furthermore, while tickets can be automatically assigned to workers during routing, this should not happen without user interaction, as an overly busy worker could block other available workers from handling new issues.

This workflow ensures issues are handled in a consistent, prioritized order without an overlap of effort. It resolves most issues with a large team of workers cherry-picking from the same shared worklist.

By default, the auto-dispatcher assigns open tickets from a worker's group memberships in the following order:

1. Tickets assigned to the worker before unassigned
2. Tickets with the highest importance to lowest
3. Tickets that have been open the longest to the most recent

These filters are stacked, so the very first ticket recommended to a worker would be one assigned to them, with high importance, that has been waiting for a reply the longest.

The last ticket would be unassigned, low importance, and most recently opened.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Auto Dispatcher**.

# Usage

### Add the workspace widget

On a workspace page, click the **Add Widget** button.

In the **Library** tab, select **Auto Dispatcher** and click the **Create** button.

 

The workspace will now have a **Start Work** button.

 

When the **Start Work** button is clicked it starts the auto-dispatcher interaction in explore mode.

 

Each worker is shown their next most important issue based on their group memberships and assignments.

Once an issue has been resolved or delegated, a worker can click the **Next** button in the top right for their next assignment. They will be reminded to unassign the issue if someone else can handle the follow-up.

### Waiting shortcuts

If work is waiting for a future date/time, the **Waiting** shortcuts can be used in the **Status** widget from the right sidebar.

 

If you don't see this option in an existing Cerb environment, you can re-create the **Status** widget from the **Add Widget** button.

### Assignment rejections

If a worker clicks the **Next** button before handling the current issue, they must either resume work or provide a reason why they cannot work on it.

 

They can click on the **I can't work on it** button to choose a reason.

 

Choosing a reason creates an **Assignment Rejection** record. The same issue will not be recommended again to the same worker. These records can be used in reporting to improve mail routing or worker training.

### Customizing the assignment rejection reasons

The list of assignment rejection reasons can be customized from the workflow.

From **Search&nbsp;» Workflows**, edit the **cerb.auto\_dispatcher** workflow and click **Edit Template** at the top of the popup.

Then click **Continue** at the bottom of the popup.

Add one rejection reason per line.

 

Then click **Continue** twice to save the changes to the workflow.

### Customizing the work order

You can change the query used for assignments in the `cerb.autoDispatcher.workerExplore` automation.

# Reference

You can build your own auto-dispatcher workflow using this template as a reference.

Change occurrences of **cerb.auto\_dispatcher** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.auto_dispatcher
  version: 2025-02-21T00:00:00Z
  description: Automatically assign tickets to workers based on priority
  website: https://cerb.ai/workflows/cerb.auto_dispatcher/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core,
  config:
    text/rejectReasons:
      label: Assignment rejection reasons
      multiple@bool: yes
      default@text:
        I don't know how to resolve it
        Someone else is already working on it

records:
  custom_record/assignment_rejection:
    fields:
      name: Assignment Rejection
      name_plural: Assignment Rejections
      uri: assignment_rejection
      params:
        owners:
          contexts@csv: cerberusweb.contexts.app
        options@csv: comments
  custom_field/asrej_worker:
    fields:
      name: Worker
      uri: worker
      context: assignment_rejection
      type: L
      pos: 1
      params:
        context: cerberusweb.contexts.worker
  custom_field/asrej_ticket:
    fields:
      name: Ticket
      uri: ticket
      context: assignment_rejection
      type: L
      pos: 2
      params:
        context: cerberusweb.contexts.ticket
  custom_field/asrej_reason:
    fields:
      name: Reason
      uri: reason
      context: assignment_rejection
      type: D
      pos: 3
      params:
        options@list: {{config.rejectReasons}}
  custom_field/asrej_expires:
    fields:
      name: Expires At
      uri: expires_at
      context: assignment_rejection
      type: E
      pos: 4

  automation/workerExplore:
    fields:
      name: cerb.autoDispatcher.workerExplore
      description: Step through next assignments in a worker explore mode
      extension_id: cerb.trigger.interaction.worker.explore
      policy_kata@raw:
        commands:
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
            allow@bool: yes
      script@raw:
        start:
          set:
            isLooping@bool: yes

          while:
            if@key,bool: isLooping
            do:
              record.search/find:
                output: next_ticket
                inputs:
                  record_type: ticket
                  record_query@text:
                    status:o
                    inGroupsOf:me
                    owner.id:[0,{{worker_id|round}}]
                    links.assignment_rejection.ticket:!(worker.id:${worker_id} expiresAt:"now to +1 year")
                    sort:-owner.id,-importance,lastOpenedAt
                    limit:1
                  record_query_params:
                    worker_id@key: worker_id
                on_success:
                  decision/exists:
                    outcome/yes:
                      if@bool: {{next_ticket.id}}
                      then:
                        # If this ticket was unassigned, assign it to the current worker
                        outcome/unassigned:
                          if@bool: {{0 == next_ticket.owner_id}}
                          then:
                            record.update:
                              inputs:
                                record_type: ticket
                                record_id: {{next_ticket.id}}
                                fields:
                                  owner_id@int: {{worker_id}}

                        # Display this ticket to the worker
                        await/explore:
                          explore:
                            title: {{next_ticket._label}}
                            url: {{next_ticket.record_url}}
                            toolbar:
                              interaction/next:
                                label: Next
                                icon: chevron-right
                                icon_at: end
                                keyboard: ]
                                uri: cerb:automation:cerb.autoDispatcher.workerExplore.toolbarItem.next
                                inputs:
                                  ticket: {{next_ticket.id}}
                    outcome/empty:
                      then:
                        await:
                          explore:
                            title: You're all caught up!
                            toolbar:
                              interaction/refresh:
                                label: Refresh
                                icon: refresh
  automation/toolbarExplore:
    fields:
      name: cerb.autoDispatcher.toolbarItem.explore
      description: Create a dynamic explore set for real-time work assignments
      extension_id: cerb.trigger.interaction.worker
      policy_kata@raw:
        commands:
          api.command:
            deny/name@bool: {{inputs.name not in ['cerb.commands.worklist.explorer.create']}}
            allow@bool: yes
      script@raw:
        start:
          api.command:
            inputs:
              name: cerb.commands.worklist.explorer.create
              params:
                interaction: cerb:automation:cerb.autoDispatcher.workerExplore
            output: results
            on_success:
              return:
                open_url: {{cerb_url('c=explore&guid=' ~ results.hash)}}
  automation/toolbarNext:
    fields:
      name: cerb.autoDispatcher.workerExplore.toolbarItem.next
      description: Generate a worker's next assignment in the auto-dispatcher
      extension_id: cerb.trigger.interaction.worker
      policy_kata@raw:
        commands:
          record.create:
            deny/type@bool: {{inputs.record_type is not record type ('assignment_rejection')}}
            allow@bool: yes
          record.search:
            deny/type@bool: {{inputs.record_type is not record type ('assignment_rejection_reason')}}
            allow@bool: yes
          record.update:
            deny/type@bool: {{inputs.record_type is not record type ('assignment_rejection', 'ticket')}}
            allow@bool: yes
          record.upsert:
            deny/type@bool: {{inputs.record_type is not record type ('assignment_rejection')}}
            allow@bool: yes
      script@raw:
        inputs:
          record/ticket:
            required@bool: yes
            record_type: ticket

        start:
          set/config:
            config@json: {{cerb_workflow_config('cerb.auto_dispatcher')|json_encode}}
          decision:
            # If the ticket is still open, it needs to be formally rejected to skip
            outcome/isOpen:
              if@bool: {{'open' == inputs.ticket.status and inputs.ticket.owner_id in [0,worker_id]}}
              then:
                await/isOpen:
                  form:
                    title: Unresolved
                    elements:
                      say:
                        content@text:
                          This ticket is still unresolved.
                          ===========
                      submit/prompt_menu:
                        buttons:
                          continue/accept:
                            label: I'll continue working on it
                            icon: circle-ok
                            icon_at: start
                            value: accept
                          continue/reject:
                            label: I can't work on it
                            icon: ban
                            icon_at: start
                            value: reject
                            style: secondary

                outcome/continue:
                  if@bool: {{'accept' == prompt_menu}}
                  then:
                    return:

                await/reason:
                  form:
                    title: I can't work on this ticket
                    elements:
                      sheet/prompt_reason:
                        label: Reason:
                        required@bool: yes
                        data@json: {{config.rejectReasons|split_crlf|default([])|map((v)=>{'name':v})|json_encode}}
                        limit: 10
                        schema:
                          layout:
                            headings@bool: no
                            filtering@bool: no
                            paging@bool: no
                            style: grid
                          columns:
                            selection/check:
                              params:
                                mode: single
                                value_key: name
                            text/name:
                              params:
                                bold@bool: yes
                      submit:
                        continue@bool: no
                        reset@bool: no

                # Unassign the ticket
                record.update/unassign:
                  output: updated_ticket
                  inputs:
                    record_type: ticket
                    record_id: {{inputs.ticket.id}}
                    fields:
                      owner_id: 0

                # Create/update the assignment log record
                record.upsert/assignment_rejection:
                  output: record_rejection
                  inputs:
                    record_type: assignment_rejection
                    record_query@text:
                      worker.id:${worker_id} ticket.id:${ticket_id} limit:1 sort:-id
                    record_query_params:
                      worker_id@key: worker_id
                      ticket_id: {{inputs.ticket.id}}
                    fields:
                      name: {{worker__label}} on {{inputs.ticket._label}}
                      ticket: {{inputs.ticket.id}}
                      worker: {{worker_id}}
                      reason: {{prompt_reason}}
                      expires_at@date: 1 day
                      owner__context: app
                      owner_id: 0

                return:
                  explore_page: next

            # Check if we need to unassign the current ticket
            outcome/isAssigned:
              if@bool: {{inputs.ticket.owner_id == worker_id}}
              then:
                await:
                  form:
                    title: Done
                    elements:
                      say:
                        content@text:
                          The ticket is still assigned to you.
                          =====================
                      submit/prompt_menu:
                        buttons:
                          continue/yes:
                            label: Keep me assigned
                            icon: circle-ok
                            icon_at: start
                            value: keep
                          continue/no:
                            label: Unassign me
                            icon: remove
                            icon_at: start
                            value: unassign
                            style: secondary

                outcome/yes:
                  if@bool: {{'unassign' == prompt_menu}}
                  then:
                    record.update:
                      inputs:
                        record_type: ticket
                        record_id: {{inputs.ticket.id}}
                        fields:
                          owner_id: 0

                return:
                  explore_page: next

            outcome/else:
              then:
                return:
                  explore_page: next
  package/package_get_work:
    fields:
      name: Auto Dispatcher
      description: A dynamic explore mode for assigning new work in priority order
      point: workspace_widget
      uri: cerb_workspace_widget_autodispatcher
      package_json@raw:
        {
          "package": {
            "name": "Auto Dispatcher",
            "revision": 1,
            "requires": {
              "cerb_version": "11.0.0",
              "plugins": [

              ]
            },
            "library": {
              "name": "Auto Dispatcher",
              "uri": "cerb_workspace_widget_autodispatcher",
              "description": "A dynamic explore mode for assigning new work in priority order",
              "point": "workspace_widget"
            },
            "configure": {
              "placeholders": [

              ],
              "prompts": [
                {
                  "type": "chooser",
                  "label": "Workspace Dashboard",
                  "key": "workspace_tab_id",
                  "hidden": true,
                  "params": {
                    "context": "cerberusweb.contexts.workspace.tab",
                    "single": true,
                    "query": ""
                  }
                }
              ]
            }
          },
          "records": [
            {
              "uid": "widget_autodispatcher",
              "_context": "workspace_widget",
              "label": "Actions",
              "tab_id": "{{{workspace_tab_id}}}",
              "extension_id": "core.workspace.widget.sheet",
              "pos": 1,
              "width_units": 4,
              "zone": "content",
              "params": {
                "data_query": "type:sample.records\r\nrecords:(help:())\r\nformat:dictionaries",
                "cache_secs": "",
                "placeholder_simulator_kata": "",
                "sheet_kata": "layout:\r\n style: fieldsets\r\n headings@bool: no\r\n paging@bool: no\r\n filtering@bool: no\r\n\r\ncolumns:\r\n toolbar/actions:\r\n params:\r\n #text_size@raw: 150%\r\n kata:\r\n interaction/startWork:\r\n label: Start Work\r\n uri: cerb:automation:cerb.autoDispatcher.toolbarItem.explore\r\n icon: play-button",
                "toolbar_kata": ""
              }
            }
          ]
        }
```
