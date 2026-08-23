---
id: "docs-automations-events-mail-reply-validate"
title: "mail.reply.validate"
url: "https://cerb.ai/docs/automations/events/mail.reply.validate/"
summary: "This page provides detailed information on the `mail.reply.validate` automation events in Cerb, which are used to implement interactive custom validators before replying to emails. These validators function similarly to worker interactions and can be configured to check recent worker activity to prevent duplication of effort. The page explains how interactive validators can allow workers to bypass warnings and proceed with sending emails, unlike non-interactive validators that would require error correction before continuing. It also outlines the structure of the automation event dictionary, including placeholders and outputs, and emphasizes the importance of filtering unnecessary validators to optimize the process."
tags: ["docs", "docs-automations"]
---
**mail.reply.validate** [automation](/docs/automations/) [events](/docs/automations/#events) implement interactive custom validators before replying to email. These automations have the same functionality as worker interactions.

For example, checking recent worker activity to avoid duplication of effort.

Interactive validators are configured on the `mail.reply.validate` automation event, and all enabled automations will run in sequence.

Through interactivity, a validator can allow a worker to bypass a warning and continue sending; whereas non-interactive custom validators would reject with an error message that a worker would have to correct before continuing.

While the most efficient option is to filter unneeded validators from the event, a `mail.reply.validate` automation that exits without an `await:` is silent and never opens the interaction popup.

# Placeholders

The automation event [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `caller_name` | string | The [caller](#callers) which started the interaction. |
| `caller_params` | dictionary | Built-in parameters based on the caller type. |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the caller. |
| `message_*` | record | The [message](/docs/records/types/message/) record. Supports key expansion. |
| `worker_*` | record | The active [worker](/docs/records/types/worker/) record. Supports key expansion. |

# Outputs

| Key | Type | Notes |
| --- | --- | --- |
| `reject:` | string | If set, sending the message is aborted. If omitted, message sending continues. |

# Examples

Warn if another worker is currently replying to a ticket:

- [automation](#)
- [policy](#)
- [event](#)

- 
```
start:
  record.search/drafts:
    inputs:
      record_type: draft
      record_query@text:
        ticket.id:${ticket_id} 
        worker.id:!${worker_id} 
        is.queued:no 
        updated:"-15 mins to now"
      record_query_params:
        ticket_id: {{inputs.message.ticket_id}}
        worker_id: {{worker_id}}
    output: drafts
    on_success:
      outcome/notEmpty:
        if@bool: {{drafts is not empty and drafts is iterable}}
        then:
          await:
            form:
              title: Duplication of effort?
              elements:
                sheet/others:
                  data@key: drafts
                  label: Other workers are currently responding to this ticket:
                  schema:
                    columns:
                      date/updated:
                        label: When
                      card/worker_id:
                        label: Who
                      card/id:
                        label: Draft
                submit/prompt_continue:
                  buttons:
                    continue/yes:
                      label: Reply anyway
                      icon: circle-ok
                      icon_at: start
                      value: yes
                    continue/no:
                      label: Abort
                      style: secondary
                      value: no
          outcome/abort:
            if@bool: {{'yes' != prompt_continue}}
            then:
              return:
                reject@bool: true
```
- 
```
commands:
  record.search:
    deny/type@bool: {{inputs.record_type is not record type ('draft')}}
    allow@bool: yes
```
- 
```
automation/duplicate:
  uri: cerb:automation:example.mailDraft.duplicateCheck
  inputs:
    message@key: message_id
  disabled@bool: no
```

