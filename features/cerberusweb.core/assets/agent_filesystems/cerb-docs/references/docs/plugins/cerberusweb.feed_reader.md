---
id: "docs-plugins-cerberusweb-feedreader"
title: "Plugin: Collaborative Feed Reader"
url: "https://cerb.ai/docs/plugins/cerberusweb.feed_reader/"
summary: "This page provides detailed information about the Collaborative Feed Reader plugin for Cerb, developed by Webgroup Media, LLC. The plugin facilitates the creation of new records for Feeds and Feed Items, enabling users to collaboratively manage and monitor RSS/Atom feeds. It supports various tasks such as moderating blog comments, auditing wiki changes, reading new forum posts, and tracking vendor announcements. The plugin includes several extensions like Bot Event, Event Listener, Page Section, Record Type, and Scheduled Job, each serving specific functions to enhance feed management and collaboration."
tags: ["docs"]
---
| **Name:** | Collaborative Feed Reader |
| **Identifier (ID):** | cerberusweb.feed\_reader |
| **Author:** | Webgroup Media, LLC. |
| **Path:** | storage/plugins/cerberusweb.feed\_reader/ |
| **Image:** |  |

This plugin adds new records for Feeds and Feed Items. These can be used to share monitoring duties on RSS/Atom feeds: moderate blog comments, audit wiki changes, read new forum posts, track vendor announcements, etc.

- [Extensions](#extensions)
  - [Bot Event](#bot-event)
  - [Event Listener](#event-listener)
  - [Page Section](#page-section)
  - [Record Type](#record-type)
  - [Scheduled Job](#scheduled-job)

# Extensions

### Bot Event

| Record custom behavior on feed item | `event.macro.feeditem` |

### Event Listener

| Event Listener | `cerberusweb.feed_reader.listener` |

### Page Section

| Feed Item Page Section | `feeds.page.profiles.feed_item` |
| Feed Page Section | `feeds.page.profiles.feed` |

### Record Type

| Feed Item | `cerberusweb.contexts.feed.item` |
| Feed | `cerberusweb.contexts.feed` |

### Scheduled Job

| Feeds Cron | `feeds.cron` |

[\< Plugins](/docs/plugins/#plugins)

