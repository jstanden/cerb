---
id: "docs-plugins-cerberusweb-calls"
title: "Plugin: Call Logging"
url: "https://cerb.ai/docs/plugins/cerberusweb.calls/"
summary: "This page provides detailed information about the Call Logging plugin for Cerb, developed by Webgroup Media, LLC. The plugin introduces a new Call record type designed for logging both incoming and outgoing phone activities. It includes various extensions such as Bot Action, Bot Event, Event Listener, Page Section, and Record Type, each with specific identifiers and functionalities. These extensions enable users to log call events, record custom behaviors, listen to call events, and manage call records within the Cerb platform."
tags: ["docs"]
---
| **Name:** | Call Logging |
| **Identifier (ID):** | cerberusweb.calls |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | storage/plugins/cerberusweb.calls/ |
| **Image:** |  |

This plugin adds a new Call record type for logging incoming and outgoing phone activity.

- [Extensions](#extensions)
  - [Bot Action](#bot-action)
  - [Bot Event](#bot-event)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Record Type](#record-type)

# Extensions

### Bot Action

| Log Call Global Event Action | `calls.event.action.post` |

### Bot Event

| Record custom behavior on call | `event.macro.call` |

### Event Listener

| Event Listener | `calls.listener` |

### Page Section

| Call Section | `calls.page.profiles.call` |

### Record Type

| Call | `cerberusweb.contexts.call` |

[\< Plugins](/docs/plugins/#plugins)

