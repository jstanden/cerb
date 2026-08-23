---
id: "workflows-cerb-email-orgbyhostname"
title: "Sender Org By Hostname"
url: "https://cerb.ai/workflows/cerb.email.org_by_hostname/"
summary: "This page provides a detailed overview of the 'Sender Org By Hostname' workflow in Cerb, which is designed to automatically assign organizations to new email senders based on their email hostname. It includes sections on introduction, installation, usage, and reference. The workflow is integrated into Cerb version 11.0 and above, and can be enabled through the Cerb interface. The usage section explains how to assign email hostnames to organizations, simplifying processes like Service Level Agreements (SLAs). The reference section offers guidance on customizing the workflow, including changing identifiers and using a domain-based prefix. The page also includes a template for building a custom workflow, detailing the necessary fields, extensions, and automation scripts to facilitate the automatic assignment of organizations to email senders."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Assigning email hostnames to organizations](#assigning-email-hostnames-to-organizations)

- [Reference](#reference)

# Introduction

This workflow automatically assigns organizations to new senders based on their email @hostname.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Sender Org By Hostname**.

# Usage

### Assigning email hostnames to organizations

Navigate to **Search&nbsp;» Organizations**.

Edit an organization record.

Click the **Add Fieldset** button and select **Sender Org By Hostname**.

In the **Email Hostnames:** field, click the **(+)** button and add an email hostname like `hostname.example`. This is the part after the `@` in an email address.

 

Click the **Save Changes** button.

When you receive a message from a new email address at that hostname it will automatically be assigned to the organization. This drastically simplifies workflows like [Service Level Agreements](/resources/workflows/cerb.sla/) (SLAs).

# Reference

You can build your own Sender Org By Hostname workflow using this template as a reference.

Change occurrences of **cerb.email.org\_by\_hostname** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.email.org_by_hostname
  version: 2025-02-21T00:00:00Z
  description: Automatically assign organizations to new senders based on their email hostname
  website: https://cerb.ai/workflows/cerb.email.org_by_hostname/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core
extensions:
  activity:
    id: cerb.email.org_by_hostname.assign
    label: Org Auto-Assigned
    message: {{actor}} assigned {{target}} to {{org}}
records:
  custom_fieldset/fieldset_sender_assignment:
    fields:
      name: Sender Org By Hostname
      context: org
      owner__context: app
      owner_id@int: 0
  custom_field/field_sender_assignment_hostnames:
    fields:
      name: Email Hostnames
      context: org
      uri: email_hostnames
      custom_fieldset_id: {{records.fieldset_sender_assignment.id}}
      type: M
      pos@int: 1
  automation/automation_assign_by_host:
     fields:
       name: cerb.email.org_by_hostname.assign
       extension_id: cerb.trigger.mail.filter
       description: Assign organizations to new senders based on their email hostname
       #workflow_id: {{workflow_id}}
       script@raw:
         start:
           outcome/validation:
             if@bool: {{email_sender_org_id}}
             then:
               return:

           # Find an organization by email sender @host
           record.search/org:
             output: result_org
             inputs:
               record_type: org
               record_query: senderOrgByHostname.emailHostnames:${host} limit:1
               record_query_params:
                 host: {{email_sender_host}}
             on_success:
               outcome/found:
                 if@bool: {{result_org.id}}
                 then:
                   api.command:
                     output: api_results
                     inputs:
                       name: cerb.commands.activity.log
                       params:
                         activity_point: cerb.email.org_by_hostname.assign
                         actor_record_type: app
                         actor_record_id@int: 0
                         target_record_type: address
                         target_record_id@int: {{email_sender_id}}
                         entry:
                           message@raw: {{actor}} assigned {{target}} to org {{org}}
                           variables:
                             org: {{result_org.name}}
                           urls:
                             org: cerb:org:{{result_org.id}}
                   return:
                     set:
                       email_sender_org_id@int: {{result_org.id}}
       policy_kata@raw:
         commands:
           api.command:
             deny/name@bool: {{inputs.name not in ['cerb.commands.activity.log']}}
             allow@bool: yes
           record.search:
             deny/type@bool: {{inputs.record_type is not record type ('org')}}
             allow@bool: yes
  automation_event_listener/listener_mail_filter:
    fields:
      name: Sender Org By Hostname
      event_name: mail.filter
      priority: 1
      is_disabled: 0
      event_kata@raw:
        automation/assign:
          uri: cerb:automation:cerb.email.org_by_hostname.assign
          # Skip when sender has an org assigned
          disabled@bool: {{email_sender_org_id}}
```
