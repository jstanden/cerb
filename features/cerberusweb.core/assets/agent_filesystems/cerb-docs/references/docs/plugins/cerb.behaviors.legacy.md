---
id: "docs-plugins-cerb-behaviors-legacy"
title: "Plugin: Bot Behaviors"
url: "https://cerb.ai/docs/plugins/cerb.behaviors.legacy/"
summary: "This page documents the Bot Behaviors plugin for Cerb, identified as `cerb.behaviors.legacy`. The plugin preserves the legacy bot, behavior, and scheduled-behavior record types along with the Bot Event and Bot Action extensions they depended on. It has been replaced by automations and will be removed in a future version. Extensions documented here include Bot Action, Bot Event, Card Widget Type, Event Listener, Page Section, Profile Widget Type, Record Type, Rest API Controller, Scheduled Job, Workspace Widget Datasource, and Workspace Widget Type."
tags: ["docs"]
---
| **Name:** | Bot Behaviors |
| **Identifier (ID):** | cerb.behaviors.legacy |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | plugins/cerb.behaviors.legacy/ |
| **Status:** | Deprecated |

The legacy bot behaviors have been replaced by [automations](/docs/automations/). They will be removed in a future version.

- [Extensions](#extensions)
  - [Bot Action](#bot-action)
  - [Bot Event](#bot-event)
  - [Card Widget Type](#card-widget-type)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Profile Widget Type](#profile-widget-type)
  - [Record Type](#record-type)
  - [Rest API Controller](#rest-api-controller)
  - [Scheduled Job](#scheduled-job)
  - [Workspace Widget Datasource](#workspace-widget-datasource)
  - [Workspace Widget Type](#workspace-widget-type)

# Extensions

### Bot Action

| Automation | `core.bot.action.automation` |
| Create Attachment | `core.va.action.create_attachment` |
| Create Reminder | `core.bot.action.create_reminder` |
| Data Query | `core.bot.action.data_query` |
| Email Parser | `core.bot.action.email_parser` |
| Get time elapsed using calendar availability | `core.bot.action.calculate_time_elapsed` |
| HTTP Request | `core.va.action.http_request` |
| Package Import | `core.bot.action.package.import` |
| PGP Encrypt | `core.bot.action.pgp.encrypt` |
| Record Create | `core.bot.action.record.create` |
| Record Delete | `core.bot.action.record.delete` |
| Record Retrieve | `core.bot.action.record.retrieve` |
| Record Search | `core.bot.action.record.search` |
| Record Update | `core.bot.action.record.update` |
| Record Upsert | `core.bot.action.record.upsert` |
| Schedule Proactive Interaction | `core.bot.action.interaction_proactive.schedule` |
| Service AWS Get Pre-signed URL | `wgm.aws.bot.action.get_presigned_url` |

### Bot Event

| After message sent from group member (Legacy) | `event.mail.after.sent.group` |
| After message sent from worker (Legacy) | `event.mail.after.sent` |
| Before composing a message reply [DEPRECATED] | `event.mail.reply.pre.ui.worker` |
| Before composing a new message [DEPRECATED] | `event.mail.compose.pre.ui.worker` |
| Before message sent by group member (Legacy) | `event.mail.sent.group` |
| Before message sent by worker (Legacy) | `event.mail.before.sent` |
| Chat with worker (Legacy) | `event.message.chat.worker` |
| Custom API request (Legacy) | `event.api.custom_request` |
| Dashboard get metric for widget (Legacy) | `event.dashboard.widget.get_metric` |
| Dashboard render widget (Legacy) | `event.dashboard.widget.render` |
| Data query datasource (Legacy) | `event.data.query.datasource` |
| Filter new incoming message (Legacy) | `event.mail.received.app` |
| Form interaction (Legacy) | `event.form.interaction.worker` |
| Get chat interactions for worker (Legacy) | `event.interactions.get.worker` |
| Handle chat interaction with worker (Legacy) | `event.interaction.chat.worker` |
| New comment on ticket in group (Legacy) | `event.comment.ticket.group` |
| New message added to ticket (Legacy) | `event.mail.received` |
| New message added to ticket in group (Legacy) | `event.mail.received.group` |
| New message on a watched ticket (Legacy) | `event.mail.received.watcher` |
| New notification for me (Legacy) | `event.notification.received.worker` |
| New task created (Legacy) | `event.task.created.worker` |
| Record changed (Legacy) | `event.record.changed` |
| Record commented on (Legacy) | `event.comment.created.worker` |
| Record custom behavior on bot (Legacy) | `event.macro.bot` |
| Record custom behavior on calendar (Legacy) | `event.macro.calendar` |
| Record custom behavior on calendar event (Legacy) | `event.macro.calendar_event` |
| Record custom behavior on contact (Legacy) | `event.macro.contact` |
| Record custom behavior on email address (Legacy) | `event.macro.address` |
| Record custom behavior on group (Legacy) | `event.macro.group` |
| Record custom behavior on message (Legacy) | `event.macro.message` |
| Record custom behavior on notification (Legacy) | `event.macro.notification` |
| Record custom behavior on organization (Legacy) | `event.macro.org` |
| Record custom behavior on reminder (Legacy) | `event.macro.reminder` |
| Record custom behavior on task (Legacy) | `event.macro.task` |
| Record custom behavior on ticket (Legacy) | `event.macro.ticket` |
| Record custom behavior on worker (Legacy) | `event.macro.worker` |
| Record worklist rendered (Legacy) | `event.ui.worklist.render.worker` |
| Recurrent behavior (Legacy) | `event.behavior.recurrent` |
| Respond to Ajax HTTP request | `event.ajax.request` |
| Ticket assigned in group (Legacy) | `event.mail.assigned.group` |
| Ticket closed in group (Legacy) | `event.mail.closed.group` |
| Ticket moved in group (Legacy) | `event.mail.moved.group` |
| Ticket profile viewed by a worker (Legacy) | `event.ticket.viewed.worker` |

### Card Widget Type

| Behavior Tree | `cerb.card.widget.behavior.tree` |

### Event Listener

| Event Listener | `cerb.behaviors.legacy.listener` |
| Triggers Manager | `cerberusweb.listeners.triggers` |

### Page Section

| Behavior Section | `core.page.profiles.behavior` |
| Bot Section | `core.page.profiles.bot` |
| Export Bots | `core.page.setup.developers.export.bots` |
| Scheduled Behavior Section | `core.page.profiles.scheduled_behavior` |

### Profile Widget Type

| Behavior Tree | `cerb.profile.tab.widget.behavior.tree` |
| Custom (Deprecated) | `cerb.profile.tab.widget.bot` |

### Record Type

| Behavior | `cerberusweb.contexts.behavior` |
| Behavior Scheduled | `cerberusweb.contexts.behavior.scheduled` |
| Bot | `cerberusweb.contexts.bot` |

### Rest API Controller

| Bots | `cerberusweb.rest.controller.bots` |

### Scheduled Job

| Bot Scheduled Behavior | `cron.bot.scheduled_behavior` |

### Workspace Widget Datasource

| Bot Behavior (Legacy) | `core.workspace.widget.datasource.bot` |

### Workspace Widget Type

| Bot Behavior Tree | `cerb.workspace.widget.behavior.tree` |
| Bot Custom Widget (Deprecated) | `core.workspace.widget.bot` |

[\< Plugins](/docs/plugins/#plugins)

