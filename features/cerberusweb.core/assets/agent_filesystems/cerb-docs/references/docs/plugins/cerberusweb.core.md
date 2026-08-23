---
id: "docs-plugins-cerberusweb-core"
title: "Plugin: Cerb Core"
url: "https://cerb.ai/docs/plugins/cerberusweb.core/"
summary: "This page provides a comprehensive overview of the Cerb Core plugin, detailing its core functionalities and various extensions. It includes a wide range of extensions such as Bot Actions, Bot Events, Calendar Datasources, Connected Service Providers, Controllers, Custom Field Types, Event Listeners, HTTP Request Listeners, Mail Transport Types, Page Sections, Page Types, Profile Tab Types, Profile Widget Types, Record Types, Scheduled Jobs, Search Schemas, Storage Schemas, Workspace Page Types, Workspace Tab Types, Workspace Widget Datasources, and Workspace Widget Types. Each extension is listed with its specific functionalities and identifiers, showcasing the extensive capabilities and integrations available within the Cerb Core framework. This detailed enumeration highlights the plugin's versatility in managing and automating various tasks and processes within the Cerb environment."
tags: ["docs"]
---
| **Name:** | Cerb Core |
| **Identifier (ID):** | cerberusweb.core |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | features/cerberusweb.core/ |
| **Image:** |  |

The core functionality of Cerb.

- [Extensions](#extensions)
  - [Bot Action](#bot-action)
  - [Bot Event](#bot-event)
  - [Calendar Datasource](#calendar-datasource)
  - [Connected Service Provider](#connected-service-provider)
  - [Controller](#controller)
  - [Custom Field Type](#custom-field-type)
  - [Event Listener](#event-listener)
  - [Http Request Listener](#http-request-listener)
  - [Mail Transport Type](#mail-transport-type)
  - [Page Section](#page-section)
  - [Page Type](#page-type)
  - [Profile Tab Type](#profile-tab-type)
  - [Profile Widget Type](#profile-widget-type)
  - [Record Type](#record-type)
  - [Scheduled Job](#scheduled-job)
  - [Search Schema](#search-schema)
  - [Storage Schema](#storage-schema)
  - [Workspace Page Type](#workspace-page-type)
  - [Workspace Tab Type](#workspace-tab-type)
  - [Workspace Widget Datasource](#workspace-widget-datasource)
  - [Workspace Widget Type](#workspace-widget-type)

# Extensions

### Bot Action

| Classifier Prediction | `core.va.action.classifier_prediction` |
| Create Attachment | `core.va.action.create_attachment` |
| Create Reminder | `core.bot.action.create_reminder` |
| Data Query | `core.bot.action.data_query` |
| Email Parser | `core.bot.action.email_parser` |
| Get time elapsed using calendar availability | `core.bot.action.calculate_time_elapsed` |
| HTTP Request | `core.va.action.http_request` |
| Package Import | `core.bot.action.package.import` |
| Record Create | `core.bot.action.record.create` |
| Record Delete | `core.bot.action.record.delete` |
| Record Retrieve | `core.bot.action.record.retrieve` |
| Record Search | `core.bot.action.record.search` |
| Record Update | `core.bot.action.record.update` |
| Record Upsert | `core.bot.action.record.upsert` |
| Schedule Proactive Interaction | `core.bot.action.interaction_proactive.schedule` |
| Service AWS Get Pre-signed URL | `wgm.aws.bot.action.get_presigned_url` |

### Bot Event

| After message sent from group member | `event.mail.after.sent.group` |
| After message sent from worker | `event.mail.after.sent` |
| After ticket profile viewed by a worker | `event.ticket.viewed.worker` |
| Before message sent from group member | `event.mail.sent.group` |
| Before message sent from worker | `event.mail.before.sent` |
| Before new incoming message is accepted | `event.mail.received.app` |
| Conversation assigned in group | `event.mail.assigned.group` |
| Conversation closed in group | `event.mail.closed.group` |
| Conversation get interactions for worker | `event.interactions.get.worker` |
| Conversation handle interaction with worker | `event.interaction.chat.worker` |
| Conversation moved in group | `event.mail.moved.group` |
| Conversation with worker | `event.message.chat.worker` |
| Custom API request | `event.api.custom_request` |
| Dashboard get metric for widget | `event.dashboard.widget.get_metric` |
| Dashboard render widget | `event.dashboard.widget.render` |
| Data Query Datasource | `event.data.query.datasource` |
| Form interaction | `event.form.interaction.worker` |
| New comment on conversation in group | `event.comment.ticket.group` |
| New message added to ticket in this group | `event.mail.received.group` |
| New message added to ticket | `event.mail.received` |
| New message on a watched ticket | `event.mail.received.watcher` |
| New notification for me | `event.notification.received.worker` |
| New task created | `event.task.created.worker` |
| Record changed | `event.record.changed` |
| Record commented on | `event.comment.created.worker` |
| Record custom behavior on bot | `event.macro.bot` |
| Record custom behavior on calendar event | `event.macro.calendar_event` |
| Record custom behavior on calendar | `event.macro.calendar` |
| Record custom behavior on contact | `event.macro.contact` |
| Record custom behavior on email address | `event.macro.address` |
| Record custom behavior on group | `event.macro.group` |
| Record custom behavior on message | `event.macro.message` |
| Record custom behavior on notification | `event.macro.notification` |
| Record custom behavior on organization | `event.macro.org` |
| Record custom behavior on reminder | `event.macro.reminder` |
| Record custom behavior on task | `event.macro.task` |
| Record custom behavior on ticket | `event.macro.ticket` |
| Record custom behavior on worker | `event.macro.worker` |
| Recurrent behavior | `event.behavior.recurrent` |
| [DEPRECATED] [UI] During a message reply | `event.mail.reply.during.ui.worker` |
| [UI] Before composing a message reply | `event.mail.reply.pre.ui.worker` |
| [UI] Before composing a new message | `event.mail.compose.pre.ui.worker` |
| [UI] Record editor opened | `event.ui.card.editor.opened.worker` |
| [UI] Render record worklist | `event.ui.worklist.render.worker` |
| [UI] Respond to Ajax HTTP request | `event.ajax.request` |

### Calendar Datasource

| Calendar | `calendar.datasource.calendar` |
| Worklist | `calendar.datasource.worklist` |

### Connected Service Provider

| [**Amazon Web Services**](/docs/plugins/extensions/cerb.service.provider.aws/) | `cerb.service.provider.aws` |
| [**Cerb API (Legacy Signatures)**](/docs/plugins/extensions/cerb.service.provider.cerb.api.legacy/) | `cerb.service.provider.cerb.api.legacy` |
| [**Facebook Pages**](/docs/plugins/extensions/wgm.facebook.pages.service.provider/) | `wgm.facebook.pages.service.provider` |
| [**HTTP Basic Authentication**](/docs/plugins/extensions/cerb.service.provider.http.basic/) | `cerb.service.provider.http.basic` |
| [**LDAP**](/docs/plugins/extensions/cerb.service.provider.ldap/) | `cerb.service.provider.ldap` |
| [**OAuth1 Provider**](/docs/plugins/extensions/cerb.service.provider.oauth1/) | `cerb.service.provider.oauth1` |
| [**OAuth2 Provider**](/docs/plugins/extensions/cerb.service.provider.oauth2/) | `cerb.service.provider.oauth2` |
| [**OpenID Connect Identity Provider**](/docs/plugins/extensions/cerb.service.provider.oidc/) | `cerb.service.provider.oidc` |
| [**SAML Identity Provider**](/docs/plugins/extensions/cerb.service.provider.saml.idp/) | `cerb.service.provider.saml.idp` |
| [**Token Bearer**](/docs/plugins/extensions/cerb.service.provider.token.bearer/) | `cerb.service.provider.token.bearer` |

### Controller

| Avatars Controller | `core.controller.avatars` |
| Cerb Controller | `core.controller.page` |
| Debug Controller | `core.controller.debug` |
| Explorer Controller | `core.controller.explorer` |
| Files Controller | `core.controller.files` |
| Internal Controller | `core.controller.internal` |
| OAuth Controller | `core.controller.oauth` |
| Portal Controller | `core.controller.portal` |
| Resource Controller | `core.controller.resource` |
| SSO Controller | `controller.sso` |
| Scheduled Tasks (Cron) Controller | `core.controller.cron` |
| UI Controller | `core.controller.ui` |

### Custom Field Type

| [**Latitude/Longitude**](/docs/plugins/extensions/cerb.custom_field.geo.point/) | `cerb.custom_field.geo.point` |

### Event Listener

| Cerb Event Listener | `cerberusweb.listeners.event` |
| Triggers Manager | `cerberusweb.listeners.triggers` |

### Http Request Listener

| Core Helpdesk Tour | `core.listeners.tour` |

### Mail Transport Type

| [**Null**](/docs/plugins/extensions/core.mail.transport.null/) | `core.mail.transport.null` |
| [**SMTP**](/docs/plugins/extensions/core.mail.transport.smtp/) | `core.mail.transport.smtp` |

### Page Section

| Abstract Custom Record Section | `core.page.profiles.abstract_custom_record` |
| Address Section | `core.page.profiles.address` |
| Attachment Section | `core.page.profiles.attachment` |
| Authentication Section | `core.page.setup.auth` |
| Avatars Section | `core.page.setup.avatars` |
| Behavior Section | `core.page.profiles.behavior` |
| Bot Scripting Tester | `core.page.setup.developers.bot_scripting_tester` |
| Bot Section | `core.page.profiles.bot` |
| Branding Section | `core.page.setup.branding` |
| Bucket Section | `core.page.profiles.bucket` |
| Cache Section | `core.page.setup.cache` |
| Calendar Event Section | `core.page.profiles.calendar_event` |
| Calendar Recurring Profile Section | `core.page.profiles.calendar_recurring_profile` |
| Calendar Section | `core.calendars.page.profiles.calendar` |
| Classifier Class Section | `core.page.profiles.classifier_class` |
| Classifier Entity Section | `core.page.profiles.classifier_entity` |
| Classifier Example Section | `core.page.profiles.classifier.example` |
| Classifier Section | `core.page.profiles.classifier` |
| Comment Section | `core.page.profiles.comment` |
| Community Portal Section | `core.page.profiles.community_tool` |
| Connected Account Section | `core.page.profiles.connected_account` |
| Connected Service Section | `core.page.profiles.connected_service` |
| Contact Section | `core.page.profiles.contact` |
| Currency Section | `core.page.profiles.currency` |
| Custom Field Section | `core.page.profiles.custom_field` |
| Custom Fields Section | `core.page.setup.fields` |
| Custom Fieldset Section | `core.page.profiles.custom_fieldset` |
| Custom Record Section | `core.page.profiles.custom_record` |
| Data Query Builder | `core.page.setup.developers.data_query_builder` |
| Data Query Tester | `core.page.setup.developers.data_query_tester` |
| Draft Section | `core.page.profiles.draft` |
| Email Signature Section | `core.page.profiles.email_signature` |
| File Bundle Section | `core.page.profiles.file_bundle` |
| Gpg Public Key Section | `core.page.profiles.gpg_public_key` |
| Group Section | `core.page.profiles.group` |
| HTML Template Section | `core.page.profiles.mail_html_template` |
| Incoming Mail Section | `core.page.setup.mail_incoming` |
| Internal Section Calendars | `core.internal.section.calendars` |
| Internal Section Custom Fieldsets | `core.internal.section.custom_fieldsets` |
| Internal Section Skills | `core.internal.section.skills` |
| Internal Section Watchers | `core.internal.section.watchers` |
| Internal Section Workspaces | `core.internal.section.workspaces` |
| License Section | `core.page.setup.license` |
| Localization Section | `core.page.setup.localization` |
| Mail Transport Section | `core.page.profiles.mail_transport` |
| Mailbox Account Section | `core.page.profiles.mailbox` |
| OAuth App Section | `core.page.profiles.oauth_app` |
| OAuth2 Token Generator | `core.page.setup.developers.oauth2_token_generator` |
| Organization Section | `core.page.profiles.organization` |
| Outgoing Mail Section | `core.page.setup.mail_outgoing` |
| Package Import Section | `core.page.setup.package.import` |
| Package Library Section | `core.page.setup.package.library` |
| Package Section | `core.page.profiles.package_library` |
| Plugin Library Section | `core.page.setup.plugin_library` |
| Plugins Section | `core.page.setup.plugins` |
| Profile Tab Section | `core.page.profiles.profile_tab` |
| Profile Widget Section | `core.page.profiles.profile_widget` |
| Records Section | `core.page.setup.records` |
| Reminder Section | `core.page.profiles.reminder` |
| Role Section | `core.page.profiles.role` |
| Saved Search Section | `core.page.profiles.context_saved_search` |
| Scheduled Behavior Section | `core.page.profiles.scheduled_behavior` |
| Scheduled Behavior Section | `core.page.setup.scheduled_behavior` |
| Scheduler Section | `core.page.setup.scheduler` |
| Security Section | `core.page.setup.security` |
| Sessions Section | `core.page.setup.sessions` |
| Skill Section | `core.page.profiles.skill` |
| Skills Section | `core.page.setup.skills` |
| Skillset Section | `core.page.profiles.skillset` |
| Snippet Section | `core.page.profiles.snippet` |
| Snippets Section | `core.page.setup.snippets` |
| Storage Attachments Section | `core.page.setup.storage_attachments` |
| Storage Content Section | `core.page.setup.storage_content` |
| Storage Profiles Section | `core.page.setup.storage_profiles` |
| Task Section | `core.page.profiles.task` |
| Team Section | `core.page.setup.team` |
| Ticket Section | `core.page.profiles.ticket` |
| Worker Section | `core.page.profiles.worker` |
| Workspace List Section | `core.page.profiles.workspace_list` |
| Workspace Page Section | `core.page.profiles.workspace_page` |
| Workspace Tab Section | `core.page.profiles.workspace_tab` |
| Workspace Widget Section | `core.page.profiles.workspace_widget` |

### Page Type

| Contacts Page | `core.page.contacts` |
| Custom Pages | `core.page.pages` |
| Display Ticket Page | `core.page.display` |
| Profiles Pages | `core.page.profiles` |
| Search Page | `core.page.search` |
| Setup Page | `core.page.configuration` |
| Signin Page | `core.page.signin` |
| Tickets Page | `core.page.tickets` |
| Welcome Page | `core.page.welcome` |

### Profile Tab Type

| [**Dashboard**](/docs/plugins/extensions/cerb.profile.tab.dashboard/) | `cerb.profile.tab.dashboard` |
| [**Portal Configure**](/docs/plugins/extensions/cerb.profile.tab.portal.config/) | `cerb.profile.tab.portal.config` |
| [**Portal Deploy**](/docs/plugins/extensions/cerb.profile.tab.portal.deploy/) | `cerb.profile.tab.portal.deploy` |
| [**Worker Settings**](/docs/plugins/extensions/cerb.profile.tab.worker.settings/) | `cerb.profile.tab.worker.settings` |

### Profile Widget Type

| [**Behavior Tree**](/docs/plugins/extensions/cerb.profile.tab.widget.behavior.tree/) | `cerb.profile.tab.widget.behavior.tree` |
| [**Calendar Availability**](/docs/plugins/extensions/cerb.profile.tab.widget.calendar.availability/) | `cerb.profile.tab.widget.calendar.availability` |
| [**Calendar**](/docs/plugins/extensions/cerb.profile.tab.widget.calendar/) | `cerb.profile.tab.widget.calendar` |
| [**Chart: Categories**](/docs/plugins/extensions/cerb.profile.tab.widget.chart.categories/) | `cerb.profile.tab.widget.chart.categories` |
| [**Chart: Pie**](/docs/plugins/extensions/cerb.profile.tab.widget.chart.pie/) | `cerb.profile.tab.widget.chart.pie` |
| [**Chart: Scatterplot**](/docs/plugins/extensions/cerb.profile.tab.widget.chart.scatterplot/) | `cerb.profile.tab.widget.chart.scatterplot` |
| [**Chart: Table**](/docs/plugins/extensions/cerb.profile.tab.widget.chart.table/) | `cerb.profile.tab.widget.chart.table` |
| [**Chart: Time Series**](/docs/plugins/extensions/cerb.profile.tab.widget.chart.time_series/) | `cerb.profile.tab.widget.chart.time_series` |
| [**Comments**](/docs/plugins/extensions/cerb.profile.tab.widget.comments/) | `cerb.profile.tab.widget.comments` |
| [**Custom**](/docs/plugins/extensions/cerb.profile.tab.widget.bot/) | `cerb.profile.tab.widget.bot` |
| [**Data Query Visualization**](/docs/plugins/extensions/cerb.profile.tab.widget.visualization/) | `cerb.profile.tab.widget.visualization` |
| [**Form Interaction**](/docs/plugins/extensions/cerb.profile.tab.widget.form_interaction/) | `cerb.profile.tab.widget.form_interaction` |
| [**HTML/Javascript**](/docs/plugins/extensions/cerb.profile.tab.widget.html/) | `cerb.profile.tab.widget.html` |
| [**Map: Geo Points**](/docs/plugins/extensions/cerb.profile.tab.widget.map.geopoints/) | `cerb.profile.tab.widget.map.geopoints` |
| [**Record Fields**](/docs/plugins/extensions/cerb.profile.tab.widget.fields/) | `cerb.profile.tab.widget.fields` |
| [**Responsibilities**](/docs/plugins/extensions/cerb.profile.tab.widget.responsibilities/) | `cerb.profile.tab.widget.responsibilities` |
| [**Sheet**](/docs/plugins/extensions/cerb.profile.tab.widget.sheet/) | `cerb.profile.tab.widget.sheet` |
| [**Ticket Conversation**](/docs/plugins/extensions/cerb.profile.tab.widget.ticket.convo/) | `cerb.profile.tab.widget.ticket.convo` |
| [**Ticket Spam Analysis**](/docs/plugins/extensions/cerb.profile.tab.widget.ticket.spam_analysis/) | `cerb.profile.tab.widget.ticket.spam_analysis` |
| [**Worklist**](/docs/plugins/extensions/cerb.profile.tab.widget.worklist/) | `cerb.profile.tab.widget.worklist` |

### Record Type

| Activity Log | `cerberusweb.contexts.activity_log` |
| Application | `cerberusweb.contexts.app` |
| Attachment | `cerberusweb.contexts.attachment` |
| Behavior Scheduled | `cerberusweb.contexts.behavior.scheduled` |
| Behavior | `cerberusweb.contexts.behavior` |
| Bot | `cerberusweb.contexts.bot` |
| Bucket | `cerberusweb.contexts.bucket` |
| Calendar Event | `cerberusweb.contexts.calendar_event` |
| Calendar Recurring Event | `cerberusweb.contexts.calendar_event.recurring` |
| Calendar | `cerberusweb.contexts.calendar` |
| Classifier Classification | `cerberusweb.contexts.classifier.class` |
| Classifier Entity | `cerberusweb.contexts.classifier.entity` |
| Classifier Example | `cerberusweb.contexts.classifier.example` |
| Classifier | `cerberusweb.contexts.classifier` |
| Comment | `cerberusweb.contexts.comment` |
| Connected Account | `cerberusweb.contexts.connected_account` |
| Connected Service | `cerberusweb.contexts.connected_service` |
| Contact | `cerberusweb.contexts.contact` |
| Currency | `cerberusweb.contexts.currency` |
| Custom Field | `cerberusweb.contexts.custom_field` |
| Custom Fieldset | `cerberusweb.contexts.custom_fieldset` |
| Custom Record | `cerberusweb.contexts.custom_record` |
| Draft | `cerberusweb.contexts.mail.draft` |
| Email Address | `cerberusweb.contexts.address` |
| Email Signature | `cerberusweb.contexts.email.signature` |
| Email Template | `cerberusweb.contexts.mail.html_template` |
| Email Transport | `cerberusweb.contexts.mail.transport` |
| File Bundle | `cerberusweb.contexts.file_bundle` |
| Group | `cerberusweb.contexts.group` |
| Mailbox Account | `cerberusweb.contexts.mailbox` |
| Message | `cerberusweb.contexts.message` |
| Notification | `cerberusweb.contexts.notification` |
| OAuth App | `cerberusweb.contexts.oauth.app` |
| Organization | `cerberusweb.contexts.org` |
| Package | `cerberusweb.contexts.package.library` |
| Portal | `cerberusweb.contexts.portal` |
| Profile Tab | `cerberusweb.contexts.profile.tab` |
| Profile Widget | `cerberusweb.contexts.profile.widget` |
| Public Key | `cerberusweb.contexts.gpg_public_key` |
| Reminder | `cerberusweb.contexts.reminder` |
| Role | `cerberusweb.contexts.role` |
| Saved Search | `cerberusweb.contexts.context.saved.search` |
| Skill | `cerberusweb.contexts.skill` |
| Skillset | `cerberusweb.contexts.skillset` |
| Snippet | `cerberusweb.contexts.snippet` |
| Task | `cerberusweb.contexts.task` |
| Ticket | `cerberusweb.contexts.ticket` |
| Worker | `cerberusweb.contexts.worker` |
| Workspace Page | `cerberusweb.contexts.workspace.page` |
| Workspace Tab | `cerberusweb.contexts.workspace.tab` |
| Workspace Widget | `cerberusweb.contexts.workspace.widget` |
| Workspace Worklist | `cerberusweb.contexts.workspace.list` |

### Scheduled Job

| Bot Scheduled Behavior | `cron.bot.scheduled_behavior` |
| Heartbeat | `cron.heartbeat` |
| Inbound Email Message Processor | `cron.parser` |
| Mail Queue Processor | `cron.mail_queue` |
| Mailbox Checker and Email Downloader | `cron.mailbox` |
| Maintenance | `cron.maint` |
| Packages Importer | `cron.packages` |
| Reminders | `cron.reminders` |
| Search Indexer | `cron.search` |
| Storage Manager | `cron.storage` |

### Search Schema

| Comment Content | `cerberusweb.search.schema.comment_content` |
| Contacts | `cerb.search.schema.contact` |
| Email Addresses | `cerb.search.schema.address` |
| Message Content | `cerberusweb.search.schema.message_content` |
| Organizations | `cerb.search.schema.org` |
| Plugin Library | `cerb.search.schema.plugin_library` |
| Snippets | `cerb.search.schema.snippet` |
| Workers | `cerb.search.schema.worker` |

### Storage Schema

| Attachments | `cerberusweb.storage.schema.attachments` |
| Avatars | `cerberusweb.storage.schema.context_avatar` |
| Message Content | `cerberusweb.storage.schema.message_content` |

### Workspace Page Type

| [**Workspace**](/docs/plugins/extensions/core.workspace.page.workspace/) | `core.workspace.page.workspace` |

### Workspace Tab Type

| [**Dashboard**](/docs/plugins/extensions/core.workspace.tab.dashboard/) | `core.workspace.tab.dashboard` |
| [**Worklists**](/docs/plugins/extensions/core.workspace.tab.worklists/) | `core.workspace.tab.worklists` |

### Workspace Widget Datasource

| [**Bot Behavior**](/docs/plugins/extensions/core.workspace.widget.datasource.bot/) | `core.workspace.widget.datasource.bot` |
| [**Data Query**](/docs/plugins/extensions/cerb.workspace.widget.datasource.data_query/) | `cerb.workspace.widget.datasource.data_query` |
| [**Manual Input**](/docs/plugins/extensions/core.workspace.widget.datasource.manual/) | `core.workspace.widget.datasource.manual` |
| [**URL**](/docs/plugins/extensions/core.workspace.widget.datasource.url/) | `core.workspace.widget.datasource.url` |
| [**Worklist (Metric)**](/docs/plugins/extensions/core.workspace.widget.datasource.worklist.metric/) | `core.workspace.widget.datasource.worklist.metric` |
| [**Worklist (Series)**](/docs/plugins/extensions/core.workspace.widget.datasource.worklist.series/) | `core.workspace.widget.datasource.worklist.series` |

### Workspace Widget Type

| [**(Deprecated) HTML/Javascript**](/docs/plugins/extensions/core.workspace.widget.custom_html/) | `core.workspace.widget.custom_html` |
| [**(Deprecated) Line/Bar Chart**](/docs/plugins/extensions/core.workspace.widget.chart/) | `core.workspace.widget.chart` |
| [**(Deprecated) Pie Chart**](/docs/plugins/extensions/core.workspace.widget.pie_chart/) | `core.workspace.widget.pie_chart` |
| [**(Deprecated) Scatterplot**](/docs/plugins/extensions/core.workspace.widget.scatterplot/) | `core.workspace.widget.scatterplot` |
| [**(Deprecated) Subtotals**](/docs/plugins/extensions/core.workspace.widget.subtotals/) | `core.workspace.widget.subtotals` |
| [**Bot Behavior Tree**](/docs/plugins/extensions/cerb.workspace.widget.behavior.tree/) | `cerb.workspace.widget.behavior.tree` |
| [**Bot Custom Widget**](/docs/plugins/extensions/core.workspace.widget.bot/) | `core.workspace.widget.bot` |
| [**Calendar**](/docs/plugins/extensions/core.workspace.widget.calendar/) | `core.workspace.widget.calendar` |
| [**Chart: Categories**](/docs/plugins/extensions/cerb.workspace.widget.chart.categories/) | `cerb.workspace.widget.chart.categories` |
| [**Chart: Pie**](/docs/plugins/extensions/cerb.workspace.widget.chart.pie/) | `cerb.workspace.widget.chart.pie` |
| [**Chart: Scatterplot**](/docs/plugins/extensions/cerb.workspace.widget.chart.scatterplot/) | `cerb.workspace.widget.chart.scatterplot` |
| [**Chart: Table**](/docs/plugins/extensions/cerb.workspace.widget.chart.table/) | `cerb.workspace.widget.chart.table` |
| [**Chart: Time Series**](/docs/plugins/extensions/cerb.workspace.widget.chart.timeseries/) | `cerb.workspace.widget.chart.timeseries` |
| [**Clock**](/docs/plugins/extensions/core.workspace.widget.clock/) | `core.workspace.widget.clock` |
| [**Countdown**](/docs/plugins/extensions/core.workspace.widget.countdown/) | `core.workspace.widget.countdown` |
| [**Counter**](/docs/plugins/extensions/core.workspace.widget.counter/) | `core.workspace.widget.counter` |
| [**Form Interaction**](/docs/plugins/extensions/core.workspace.widget.form_interaction/) | `core.workspace.widget.form_interaction` |
| [**Gauge**](/docs/plugins/extensions/core.workspace.widget.gauge/) | `core.workspace.widget.gauge` |
| [**Map: Geo Points**](/docs/plugins/extensions/cerb.workspace.widget.map.geopoints/) | `cerb.workspace.widget.map.geopoints` |
| [**Record Fields**](/docs/plugins/extensions/core.workspace.widget.record.fields/) | `core.workspace.widget.record.fields` |
| [**Sheet**](/docs/plugins/extensions/core.workspace.widget.sheet/) | `core.workspace.widget.sheet` |
| [**Worklist**](/docs/plugins/extensions/core.workspace.widget.worklist/) | `core.workspace.widget.worklist` |

[\< Plugins](/docs/plugins/#plugins)

