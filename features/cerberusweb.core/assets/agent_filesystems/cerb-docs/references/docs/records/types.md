---
id: "docs-records-types"
title: "Record Types"
url: "https://cerb.ai/docs/records/types/"
summary: "This page provides a comprehensive reference for the various record types available in Cerb, including both built-in and plugin-provided types. It details the aliases and corresponding records, which are essential for building automations, search queries, data queries, or interacting with the API. The built-in record types cover a wide range of functionalities such as activity logs, email management, automation, calendars, contacts, and more. Additionally, plugin-provided record types extend the system's capabilities with features like calls, classifications, knowledgebase management, and time tracking. This reference is crucial for developers and users looking to customize and extend Cerb's functionality."
tags: ["docs"]
---
Cerb includes many built-in [record](/docs/records/) types. You can create your own [custom record types](/docs/records/#custom-records), and new types may also be introduced by [plugins](/docs/plugins/).

This reference can be used when building [automations](/docs/automations/), [search queries](/docs/search/), [data queries](/docs/data-queries/), or working with the [API](/docs/api/).

# Built-in record types

| Alias | Record |
| --- | --- |
| `activity_log` | [Activity Logs](/docs/records/types/activity_log/) |
| `agent_file` | [Agent Files](/docs/records/types/agent_file/) |
| `agent_filesystem` | [Agent Filesystems](/docs/records/types/agent_filesystem/) |
| `agent_model` | [Agent Models](/docs/records/types/agent_model/) |
| `address` | [Email Addresses](/docs/records/types/address/) |
| `attachment` | [Attachments](/docs/records/types/attachment/) |
| `automation` | [Automations](/docs/records/types/automation/) |
| `automation_event` | [Automation Events](/docs/records/types/automation_event/) |
| `automation_event_listener` | [Automation Event Listeners](/docs/records/types/automation_event_listener/) |
| `automation_resource` | [Automation Resources](/docs/records/types/automation_resource/) |
| `automation_timer` | [Automation Timers](/docs/records/types/automation_timer/) |
| `bucket` | [Buckets](/docs/records/types/bucket/) |
| `calendar_event` | [Calendar Events](/docs/records/types/calendar_event/) |
| `calendar_recurring_event` | [Calendar Recurring Events](/docs/records/types/calendar_recurring_event/) |
| `calendar` | [Calendars](/docs/records/types/calendar/) |
| `card_widget` | [Card Widget](/docs/records/types/card_widget/) |
| `comment` | [Comments](/docs/records/types/comment/) |
| `community_portal` | [Community Portals](/docs/records/types/community_portal/) |
| `connected_account` | [Connected Accounts](/docs/records/types/connected_account/) |
| `connected_service` | [Connected Services](/docs/records/types/connected_service/) |
| `contact` | [Contacts](/docs/records/types/contact/) |
| `currency` | [Currencies](/docs/records/types/currency/) |
| `custom_field` | [Custom Fields](/docs/records/types/custom_field/) |
| `custom_fieldset` | [Custom Fieldsets](/docs/records/types/custom_fieldset/) |
| `custom_record` | [Custom Records](/docs/records/types/custom_record/) |
| `draft` | [Drafts](/docs/records/types/draft/) |
| `email_signature` | [Email Signatures](/docs/records/types/email_signature/) |
| `file_bundle` | [File Bundles](/docs/records/types/file_bundle/) |
| `gpg_public_key` | [Public Keys](/docs/records/types/gpg_public_key/) |
| `gpg_private_key` | [Private Keys](/docs/records/types/gpg_private_key/) |
| `group` | [Groups](/docs/records/types/group/) |
| `html_template` | [Email Templates](/docs/records/types/html_template/) |
| `mail_transport` | [Email Transports](/docs/records/types/mail_transport/) |
| `mailbox` | [Email Mailboxes](/docs/records/types/mailbox/) |
| `mail_delivery_log` | [Email Delivery Log](/docs/records/types/mail_delivery_log/) |
| `mail_inbound_log` | [Email Inbound Log](/docs/records/types/mail_inbound_log/) |
| `mail_routing_rule` | [Email Routing Rule](/docs/records/types/mail_routing_rule/) |
| `message` | [Email Messages](/docs/records/types/message/) |
| `metric` | [Metrics](/docs/records/types/metric/) |
| `notification` | [Notifications](/docs/records/types/notification/) |
| `oauth_app` | [OAuth Apps](/docs/records/types/oauth_app/) |
| `org` | [Organizations](/docs/records/types/org/) |
| `package` | [Packages](/docs/records/types/package/) |
| `profile_tab` | [Profile Tabs](/docs/records/types/profile_tab/) |
| `profile_widget` | [Profile Widgets](/docs/records/types/profile_widget/) |
| `project_board_column` | [Project Board Columns](/docs/records/types/project_board_column/) |
| `project_board` | [Project Boards](/docs/records/types/project_board/) |
| `queue` | [Queues](/docs/records/types/queue/) |
| `queue_job` | [Queue Jobs](/docs/records/types/queue_job/) |
| `reminder` | [Reminders](/docs/records/types/reminder/) |
| `resource` | [Resources](/docs/records/types/resource/) |
| `role` | [Roles](/docs/records/types/role/) |
| `saved_search` | [Saved Searches](/docs/records/types/saved_search/) |
| `search_index` | [Search Indexes](/docs/records/types/search_index/) |
| `service_token` | [Service Tokens](/docs/records/types/service_token/) |
| `snippet` | [Snippets](/docs/records/types/snippet/) |
| `task` | [Tasks](/docs/records/types/task/) |
| `task_project` | [Task Projects](/docs/records/types/task_project/) |
| `ticket` | [Tickets](/docs/records/types/ticket/) |
| `toolbar` | [Toolbars](/docs/records/types/toolbar/) |
| `toolbar_section` | [Toolbar Sections](/docs/records/types/toolbar_section/) |
| `webhook_listener` | [Webhooks](/docs/records/types/webhook_listener/) |
| `worker` | [Workers](/docs/records/types/worker/) |
| `workflow` | [Workflows](/docs/records/types/workflow/) |
| `workspace_list` | [Workspace Worklists](/docs/records/types/workspace_list/) |
| `workspace_page` | [Workspace Pages](/docs/records/types/workspace_page/) |
| `workspace_tab` | [Workspace Tabs](/docs/records/types/workspace_tab/) |
| `workspace_widget` | [Workspace Widgets](/docs/records/types/workspace_widget/) |

# Plugin-provided record types

| Alias | Record |
| --- | --- |
| `behavior` | [Behaviors](/docs/records/types/behavior/) (deprecated; superseded by [automations](/docs/automations/)) |
| `bot` | [Bots](/docs/records/types/bot/) (deprecated; superseded by [automations](/docs/automations/)) |
| `call` | [Calls](/docs/records/types/call/) |
| `classifier_class` | [Classifications](/docs/records/types/classifier_class/) |
| `classifier_entity` | [Classifier Entities](/docs/records/types/classifier_entity/) |
| `classifier_example` | [Classifier Examples](/docs/records/types/classifier_example/) |
| `classifier` | [Classifiers](/docs/records/types/classifier/) |
| `domain` | [Domains](/docs/records/types/domain/) |
| `feed_item` | [Feed Items](/docs/records/types/feed_item/) |
| `feed` | [Feeds](/docs/records/types/feed/) |
| `feedback` | [Feedback](/docs/records/types/feedback/) |
| `kb_article` | [Knowledgebase Articles](/docs/records/types/kb_article/) |
| `kb_category` | [Knowledgebase Categories](/docs/records/types/kb_category/) |
| `opportunity` | [Opportunities](/docs/records/types/opportunity/) |
| `scheduled_behavior` | [Scheduled Behaviors](/docs/records/types/scheduled_behavior/) (deprecated; superseded by [automations](/docs/automations/)) |
| `sensor` | [Sensors](/docs/records/types/sensor/) |
| `server` | [Servers](/docs/records/types/server/) |
| `time_entry` | [Time Tracking Entries](/docs/records/types/time_entry/) |
| `timetracking_activity` | [Time Tracking Activities](/docs/records/types/timetracking_activity/) |
| `webapi_credentials` | [Web API Keys](/docs/records/types/webapi_credentials/) |

