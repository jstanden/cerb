---
id: "docs-plugins-cerberusweb-kb"
title: "Plugin: Legacy Knowledgebase"
url: "https://cerb.ai/docs/plugins/cerberusweb.kb/"
summary: "This page provides detailed information about the Knowledgebase plugin for Cerb, developed by Webgroup Media, LLC. It outlines the plugin's purpose of creating and categorizing articles to facilitate knowledge sharing among workers or within a community. The page lists various extensions associated with the plugin, including Bot Event, Controller, Event Listener, Page Section, Page Type, Profile Widget Type, Record Type, Reply Toolbar Item, Rest API Controller, Search Schema, Support Center Controller, Support Center RSS Feed, and Workspace Widget Type. Each extension is described with its specific function and identifier, highlighting the comprehensive capabilities of the Knowledgebase plugin in managing and accessing knowledge resources within Cerb."
tags: ["docs"]
---
| **Name:** | Legacy Knowledgebase |
| **Identifier (ID):** | cerberusweb.kb |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | features/cerberusweb.kb/ |
| **Image:** |  |
| **Status:** | Deprecated |

Create and categorize articles to share knowledge between workers or your community.

- [Extensions](#extensions)
  - [Bot Event](#bot-event)
  - [Controller](#controller)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Page Type](#page-type)
  - [Profile Widget Type](#profile-widget-type)
  - [Record Type](#record-type)
  - [Reply Toolbar Item](#reply-toolbar-item)
  - [Rest API Controller](#rest-api-controller)
  - [Search Schema](#search-schema)
  - [Support Center Controller](#support-center-controller)
  - [Support Center RSS Feed](#support-center-rss-feed)
  - [Workspace Widget Type](#workspace-widget-type)

# Extensions

### Bot Event

| Record custom behavior on knowledgebase article | `event.macro.kb_article` |

### Controller

| KB Ajax Controller | `cerberusweb.kb.controller.ajax` |

### Event Listener

| Event Listener | `kb.listener` |

### Page Section

| KB Article Section | `cerberusweb.page.profiles.kb_article` |
| Kb Category Section | `kb.page.profiles.kb_category` |

### Page Type

| Knowledgebase | `core.page.kb` |

### Profile Widget Type

| [**Knowledgebase Article**](/docs/plugins/extensions/cerb.profile.tab.widget.kb_article/) | `cerb.profile.tab.widget.kb_article` |

### Record Type

| Knowledgebase Article | `cerberusweb.contexts.kb_article` |
| Knowledgebase Category | `cerberusweb.contexts.kb_category` |

### Reply Toolbar Item

| KB Reply Toolbar | `cerberusweb.kb.reply.toolbaritem.kb` |

### Rest API Controller

| KB Articles | `cerberusweb.rest.controller.kbarticles` |
| KB Categories | `cerberusweb.rest.controller.kbcategories` |

### Search Schema

| Knowledgebase Articles | `cerberusweb.search.schema.kb_article` |

### Support Center Controller

| Knowledgebase | `cerberusweb.kb.sc.controller` |

### Support Center RSS Feed

| Knowledgebase RSS | `cerberusweb.kb.sc.rss.controller` |

### Workspace Widget Type

| [**Knowledgebase Browser**](/docs/plugins/extensions/kb.workspace.widget.kb.browser/) | `kb.workspace.widget.kb.browser` |

[\< Plugins](/docs/plugins/#plugins)

