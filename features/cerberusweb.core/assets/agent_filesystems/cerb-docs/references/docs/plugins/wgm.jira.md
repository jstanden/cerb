---
id: "docs-plugins-wgm-jira"
title: "Plugin: JIRA Integration (Legacy)"
url: "https://cerb.ai/docs/plugins/wgm.jira/"
summary: "This page provides details about the JIRA Integration (Legacy) plugin for Cerb, developed by Webgroup Media, LLC. The plugin facilitates integration with Atlassian JIRA through its REST API, serving as a bridge for other plugins to interact with JIRA services. It includes various extensions such as Bot Events for tracking new issues, comments, and status changes in JIRA, as well as custom behaviors for JIRA issues and projects. Additionally, it features Page Sections for displaying JIRA issue and project information, Record Types for managing JIRA issues and projects within Cerb, a Scheduled Job for JIRA synchronization, and a Search Schema for querying JIRA issues."
tags: ["docs"]
---
| **Name:** | JIRA Integration (Legacy) |
| **Identifier (ID):** | wgm.jira |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | storage/plugins/wgm.jira/ |
| **Image:** |  |

This plugin provides integration with Atlassian JIRA via their REST API. It is intended to be a bridge used by other plugins to communicate with JIRA services.

- [Extensions](#extensions)
  - [Bot Event](#bot-event)
  - [Page Section](#page-section)
  - [Record Type](#record-type)
  - [Scheduled Job](#scheduled-job)
  - [Search Schema](#search-schema)

# Extensions

### Bot Event

| New JIRA issue | `wgmjira.event.issue.created` |
| New comment on JIRA issue | `wgmjira.event.issue.commented` |
| New status on JIRA issue | `wgmjira.event.issue.status.changed` |
| Record custom behavior on JIRA issue | `event.macro.jira_issue` |
| Record custom behavior on JIRA project | `event.macro.jira_project` |

### Page Section

| Jira Issue Section | `jira.page.profiles.jira_issue` |
| Jira Project Section | `jira.page.profiles.jira_project` |

### Record Type

| Jira Issue | `cerberusweb.contexts.jira.issue` |
| Jira Project | `cerberusweb.contexts.jira.project` |

### Scheduled Job

| JIRA Synchronization | `wgmjira.cron` |

### Search Schema

| Jira Issues | `jira.search.schema.jira_issue` |

[\< Plugins](/docs/plugins/#plugins)

