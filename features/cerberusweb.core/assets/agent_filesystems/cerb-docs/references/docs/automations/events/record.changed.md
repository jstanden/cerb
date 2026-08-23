---
id: "docs-automations-events-record-changed"
title: "record.changed"
url: "https://cerb.ai/docs/automations/events/record.changed/"
summary: "This page provides information about the 'record.changed' automation events in Cerb, which are triggered when fields on a record are modified. It explains how automations are executed in sequence within the events KATA and can be dynamically enabled or disabled based on record type, field values, or the actor involved. The page details the placeholders available in the automation event dictionary, including keys for the current actor, custom input values, whether the record is new, and both the new and former record dictionaries. There are no outputs specified for these events."
tags: ["docs", "docs-automations"]
---
**record.changed** [automation](/docs/automations/) [events](/docs/automations/#events) are triggered whenever one or more fields are changed on a [record](/docs/records/types/).

In the events KATA, all enabled automations are executed in order.

Automations can dynamically be enabled/disabled by record type, field values, or actor.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `actor_*` | record | The current actor dictionary. Supports key expansion. |
| `change_type` | string | `created`, `updated`, or `deleted` |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller. |
| `is_new` | boolean | `true` if the record was created during the current request. |
| `record_*` | record | The new [record](/docs/records/types/) dictionary. Supports key expansion. |
| `was_record_*` | record | The former [record](/docs/records/types/) dictionary. Supports key expansion. |

# Outputs

(none)

# Examples

## Fix attachment image MIME types

Set the proper MIME type if a file with a `.png` extension comes in as an `application/octet-stream`:

- [automation](#)
- [policy](#)
- [event](#)

- 
```
start:
  outcome/png:
    if@bool: {{record_name is suffixed ('.png')}}
    then:
      record.update/fix:
        output: updated_attachment
        inputs:
          record_type: attachment
          record_id: {{record_id}}
          fields:
            mime_type: image/png
```
- 
```
commands:
  record.update:
    deny/type@bool: {{inputs.record_type is not record type ('attachment')}}
    allow@bool: yes
```
- 
```
automation/png:
  uri: cerb:automation:example.png.mimefix
  disabled@bool: 
      {{
        change_type not in ['created']
        or record__context is not record type ('attachment')
        or record_mime_type not in ['application/octet-stream']
      }}
```

## Create notifications for new calendar events

Create a notification when someone adds an event to your calendar:

- [automation](#)
- [policy](#)
- [event](#)

- 
```
start:
  record.create/notification:
    output: new_notification
    inputs:
      record_type: notification
      fields:
        activity_point: record.created
        params:
          message: a new event has been placed on your calendar {{record_record_url}}
        worker_id@int: {{record_calendar_owner_id}}
```
- 
```
commands:
  record.create:
    deny/type@bool: {{inputs.record_type is not record type ('notification')}}
    allow@bool: yes
```
- 
```
automation/reminder:
  uri: cerb:automation:example.calendarEvent.notification
  disabled@bool: 
    {{
      change_type not in ['created']
      or record__context is not record type ('calendar_event')
    }}
```

## Add watchers on high-touch tickets

Add a watcher (eg. a manager) if a ticket thread reaches a certain number of messages:

- [automation](#)
- [policy](#)
- [event](#)

- 
```
start:
 decision/number:
   outcome/15:
     if@bool: {{record_num_messages == 15}}
      record.update/addManager:
        output: updated_ticket
        inputs:
          record_type: ticket
          record_id: {{record_id}}
          fields:
            links@list:
              worker:1
   outcome/30:
    if@bool: {{record_num_messages == 30}}
         then:
          record.update/addSeniorManager:
            output: updated_ticket
            inputs:
              record_type: ticket
              record_id: {{record_id}}
              fields:
                links@list:
                  worker:2
```
- 
```
commands:
  record.update:
    deny/type@bool: {{inputs.record_type is not record type ('ticket')}}
    allow@bool: yes
```
- 
```
automation/manager:
  uri: cerb:automation:cerb.example.automation
  disabled@bool: 
    {{
      change_type not in ['updated']
      or record__context is not record type ('ticket')
      or record_num_messages == was_record_num_messages
      or record_num_messages not in [15,30]
    }}
```

