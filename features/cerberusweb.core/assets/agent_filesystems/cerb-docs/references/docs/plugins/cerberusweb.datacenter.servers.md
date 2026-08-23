---
id: "docs-plugins-cerberusweb-datacenter-servers"
title: "Plugin: Servers"
url: "https://cerb.ai/docs/plugins/cerberusweb.datacenter.servers/"
summary: "This page provides detailed information about the 'Servers' plugin for Cerb, developed by Webgroup Media, LLC. The plugin is designed to manage datacenter assets by introducing Server objects. It includes various extensions such as Bot Actions for creating servers, Bot Events for recording custom server behaviors, Event Listeners, and Page Sections for server management. Additionally, it defines a specific Page Type for datacenter management, a Record Type for server contexts, and a REST API Controller for server-related operations. The plugin is identified by the ID 'cerberusweb.datacenter.servers' and is located in the specified storage path."
tags: ["docs"]
---
| **Name:** | Servers |
| **Identifier (ID):** | cerberusweb.datacenter.servers |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | storage/plugins/cerberusweb.datacenter.servers/ |
| **Image:** |  |

This plugin adds Server objects that can be used to manage datacenter assets.

- [Extensions](#extensions)
  - [Bot Action](#bot-action)
  - [Bot Event](#bot-event)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Page Type](#page-type)
  - [Record Type](#record-type)
  - [Rest API Controller](#rest-api-controller)

# Extensions

### Bot Action

| Create Server | `va.action.create_server` |

### Bot Event

| Record custom behavior on server | `event.macro.server` |

### Event Listener

| Event Listener | `cerberusweb.datacenter.listener` |

### Page Section

| Server Page Section | `cerberusweb.profiles.server` |

### Page Type

| Datacenter Page | `cerberusweb.datacenter.page` |

### Record Type

| Server | `cerberusweb.contexts.datacenter.server` |

### Rest API Controller

| Servers | `cerberusweb.datacenter.servers.rest` |

[\< Plugins](/docs/plugins/#plugins)

