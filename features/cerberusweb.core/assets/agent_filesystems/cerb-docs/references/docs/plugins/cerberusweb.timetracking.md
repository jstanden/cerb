---
id: "docs-plugins-cerberusweb-timetracking"
title: "Plugin: Time Tracking"
url: "https://cerb.ai/docs/plugins/cerberusweb.timetracking/"
summary: "This page provides detailed information about the Time Tracking plugin for Cerb, developed by Webgroup Media, LLC. The plugin is designed to help users track time spent on various helpdesk activities, such as replying to tickets. It introduces a 'Track Time' button to the Display Ticket and Organizations interfaces and adds a Time Tracking tab to the Activity page. The page outlines various extensions associated with the plugin, including Bot Event, Event Listener, Page Section, Page Type, Prebody Renderer, Profile Script, Record Type, Reply Toolbar Item, and Rest API Controller, each with specific identifiers and functions to enhance time tracking capabilities within the Cerb platform."
tags: ["docs"]
---
| **Name:** | Time Tracking |
| **Identifier (ID):** | cerberusweb.timetracking |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | features/cerberusweb.timetracking/ |
| **Image:** |  |

Track time spent on various helpdesk activities (replying to tickets, etc). This adds a Track Time button to Display Ticket and Organizations, and a Time Tracking tab to the Activity page.

- [Extensions](#extensions)
  - [Bot Event](#bot-event)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Page Type](#page-type)
  - [Prebody Renderer](#prebody-renderer)
  - [Profile Script](#profile-script)
  - [Record Type](#record-type)
  - [Reply Toolbar Item](#reply-toolbar-item)
  - [Rest API Controller](#rest-api-controller)

# Extensions

### Bot Event

| Record custom behavior on time tracking entry | `event.macro.timetracking` |

### Event Listener

| Time Tracking Listener | `timetracking.listener.core` |

### Page Section

| Time Tracking Activity Section | `cerb.page.profiles.timetracking_activity` |
| Time Tracking Page Section | `cerberusweb.profiles.time_tracking` |

### Page Type

| Time Tracking Page | `timetracking.page` |

### Prebody Renderer

| Time Tracking Pre-body Renderer | `timetracking.renderer.prebody` |

### Profile Script

| Time Tracking Profile Script | `timetracking.profile_script.timer` |

### Record Type

| Time Tracking Activity | `cerberusweb.contexts.timetracking.activity` |
| Time Tracking | `cerberusweb.contexts.timetracking` |

### Reply Toolbar Item

| Time Tracking Reply Toolbar Timer | `timetracking.reply.toolbaritem.timer` |

### Rest API Controller

| Time Tracking | `cerberusweb.rest.controller.timetracking` |

[\< Plugins](/docs/plugins/#plugins)

