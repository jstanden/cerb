---
id: "docs-plugins-cerberusweb-datacenter-domains"
title: "Plugin: Domains"
url: "https://cerb.ai/docs/plugins/cerberusweb.datacenter.domains/"
summary: "This page provides detailed information about the 'Domains' plugin for Cerb, developed by Webgroup Media, LLC. The plugin is designed to manage Domain objects within web hosting, SaaS, and on-demand infrastructure environments. It includes various extensions such as Bot Actions for creating domains, Bot Events for recording custom behaviors, Event Listeners, and Page Sections and Types for domain management. Additionally, it supports a Record Type for domains and a REST API Controller for domain-related operations. The plugin is identified by the ID 'cerberusweb.datacenter.domains' and is located in the specified storage path."
tags: ["docs"]
---
| **Name:** | Domains |
| **Identifier (ID):** | cerberusweb.datacenter.domains |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | storage/plugins/cerberusweb.datacenter.domains/ |
| **Image:** |  |

This plugin adds Domain objects for managing webhosting/SaaS/On-Demand infrastructure.

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

| Create Domain | `va.action.create_domain` |

### Bot Event

| Record custom behavior on domain | `event.macro.domain` |

### Event Listener

| Event Listener | `cerberusweb.datacenter.domains.listener` |

### Page Section

| Domain Section | `cerberusweb.datacenter.page.profiles.domain` |

### Page Type

| Domains Page | `cerberusweb.datacenter.domains.page` |

### Record Type

| Domain | `cerberusweb.contexts.datacenter.domain` |

### Rest API Controller

| Domains | `cerberusweb.datacenter.domains.rest` |

[\< Plugins](/docs/plugins/#plugins)

