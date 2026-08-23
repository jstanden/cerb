---
id: "docs-plugins-cerberusweb-crm"
title: "Plugin: Opportunity Tracking"
url: "https://cerb.ai/docs/plugins/cerberusweb.crm/"
summary: "This page provides detailed information about the Opportunity Tracking plugin for Cerb, developed by Webgroup Media, LLC. It outlines the functionality of creating and managing sales leads linked to email addresses within the Cerb platform. The plugin allows users to create opportunity records from the Activity menu or while reading tickets. It includes various extensions such as Bot Actions for creating opportunities, Bot Events for recording custom behaviors, Event Listeners for CRM activities, Page Sections for opportunity profiles, Record Types for opportunity management, and a REST API Controller for handling opportunities programmatically."
tags: ["docs"]
---
| **Name:** | Opportunity Tracking |
| **Identifier (ID):** | cerberusweb.crm |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | features/cerberusweb.crm/ |
| **Image:** |  |

Create opportunities (sales leads) linked to e-mail addresses. Opportunity records can be created from the Activity menu or while reading tickets.

- [Extensions](#extensions)
  - [Bot Action](#bot-action)
  - [Bot Event](#bot-event)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Record Type](#record-type)
  - [Rest API Controller](#rest-api-controller)

# Extensions

### Bot Action

| Create Opportunity | `va.action.create_opportunity` |

### Bot Event

| Record custom behavior on opportunity | `event.macro.crm.opportunity` |

### Event Listener

| CRM Listener | `crm.listeners.core` |

### Page Section

| Opp Section | `crm.page.profiles.opportunity` |

### Record Type

| Opportunity | `cerberusweb.contexts.opportunity` |

### Rest API Controller

| Opportunities | `crm.rest.controller.opps` |

[\< Plugins](/docs/plugins/#plugins)

