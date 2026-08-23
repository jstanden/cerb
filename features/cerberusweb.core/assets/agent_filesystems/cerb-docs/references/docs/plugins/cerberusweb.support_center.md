---
id: "docs-plugins-cerberusweb-supportcenter"
title: "Plugin: Support Center"
url: "https://cerb.ai/docs/plugins/cerberusweb.support_center/"
summary: "This page provides detailed information about the Support Center plugin for Cerb, developed by Webgroup Media, LLC. It outlines the plugin's purpose of creating a public-facing support portal and lists its various extensions, including Bot Event, Event Listener, Portal, Support Center Controller, and Support Center Login Authenticator. Each extension is described with its specific functionalities, such as handling new contact registrations, managing support center events, and providing various controller actions like Ajax, Announcements, Avatar, Contact Us, Home, Login, My Account, and Ticket History. The default login authenticator for the Support Center is also mentioned."
tags: ["docs"]
---
| **Name:** | Support Center |
| **Identifier (ID):** | cerberusweb.support\_center |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | features/cerberusweb.support\_center/ |
| **Image:** |  |

Adds a Support Center community portal for public-facing support.

- [Extensions](#extensions)
  - [Bot Event](#bot-event)
  - [Event Listener](#event-listener)
  - [Portal](#portal)
  - [Support Center Controller](#support-center-controller)
  - [Support Center Login Authenticator](#support-center-login-authenticator)

# Extensions

### Bot Event

| New contact registered in Support Center | `event.contact.registered.sc` |

### Event Listener

| Support Center Event Listener | `sc.listeners.core` |

### Portal

| [**Support Center**](/docs/plugins/extensions/sc.tool/) | `sc.tool` |

### Support Center Controller

| Ajax | `sc.controller.ajax` |
| Announcements | `sc.controller.announcements` |
| Avatar | `sc.controller.avatar` |
| Contact Us | `sc.controller.contact` |
| Home | `sc.controller.home` |
| Login | `sc.controller.login` |
| My Account | `sc.controller.account` |
| Ticket History | `sc.controller.history` |

### Support Center Login Authenticator

| Cerb (Default) | `sc.login.auth.default` |

[\< Plugins](/docs/plugins/#plugins)

