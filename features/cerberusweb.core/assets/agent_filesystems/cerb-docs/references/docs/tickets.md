---
id: "docs-tickets"
title: "Tickets"
url: "https://cerb.ai/docs/tickets/"
summary: "This page provides a comprehensive overview of the ticketing system in Cerb, describing how tickets function as brief projects centered around specific questions or issues. It explains the organization of tickets into groups and buckets, the role of participants and owners, and the use of unique reference masks for privacy. The page details ticket statuses, including open, waiting for reply, closed, and deleted, and discusses the importance of managing response times and ticket importance. It also covers features like anti-spam measures, drafts, and the ability to merge or split tickets. The system encourages efficient communication and collaboration among workers while maintaining a streamlined workflow."
tags: ["docs"]
---
You can think of a **ticket** as a brief project based on a specific question or issue.

The ticket's [profile](/docs/profiles/) page is a workspace that groups together email correspondence, comments, attachments, etc.

Each ticket functions like a private mailing list. Any number of [contacts](/docs/contacts/) may be subscribed to a ticket conversation as **participants**, and they will receive a copy of all outgoing email messages from your team.

Tickets are sorted into [buckets](/docs/buckets/) within [groups](/docs/groups/).

The [worker](/docs/workers/) who is currently responsible for a ticket's completion is its **owner**.

- [Masks](#masks)
- [Status](#status)
- [Importance](#importance)
- [Response Times](#response-times)
- [Anti-spam](#anti-spam)
- [Drafts](#drafts)
- [Merge](#merge)
- [Split](#split)

 

# Masks

Every ticket is automatically assigned a unique, non-sequential reference **mask**, like `NCY-35312-825`.

The term _"mask"_ refers to the fact that the randomized reference number (opposed to a numeric ID) doesn't disclose information about your total number of tickets or your daily email volume.

A contact can provide the first three letters of the mask for quick lookups.

# Status

The **status** of a [ticket](/docs/tickets/) is always one of the following:

- **open**: the conversation needs worker attention.
- **waiting for reply**: the conversation is on hold pending client action.
- **closed**: the conversation has concluded.
- **deleted**: the conversation is flagged for deletion during the next maintenance window.

You'll most likely have at least one worklist dedicated to _open_ tickets if you're responsible for responding to inquiries from contacts.

When a ticket is changed to the _waiting_ status you can also schedule a **wait until** date and time, at which point the conversation will automatically return to the _open_ status. This keeps your inbox free of tickets that you can't work on until a later date. It's also a great way to schedule followups on sales or marketing messages.

If a contact adds a new message to a ticket that is _waiting_ or _closed_ it will automatically change back to the _open_ status.

By default, when a ticket is deleted you will have 7 days from its last activity to change it to a different status before it's removed permanently. This is called the _"undo window"_.

# Importance

Ticket priority is determined by the **importance** field. You'll encounter importance as a simple slider where you can nudge it up or down as needed.

The importance field can also be automatically adjusted by [automations](/docs/automations/) in response to service level committments, escalations, etc.

# Response Times

Cerb records ticket-level **response time** information in two fields:

- **First response**: the total time elapsed before the first response from a worker. This focuses on the first response so that it isn't skewed by later delays that may be attributed to the client.

- **First resolution**: the total time elapsed before the ticket was first changed to the _closed_ status. This isn't affected by subsequent changes between the _open_ and _closed_ statuses that may occur when you get stuck in a loop of _"Thanks!"_ and _"You're welcome!"_ – especially because that may occur days after the ticket was actually resolved.

When a worker responds to a specific client message, Cerb also records the response time on the worker's message. This encourages workers to respond quickly, even if it's only to say, _"Let me check on that and get right back to you"._

# Anti-spam

Cerb performs a simple statistical analysis of the contents of the first message on new [tickets](/docs/tickets/) to predict whether a message is spam or not.

The predicted probability of being spam is stored as a **spam score** on the ticket.

By itself, this prediction has no effect on tickets. However, the computed score can be used by [automations](/docs/automations/) and [workers](/docs/workers/) to filter mail when desirable.

The spam predictions improve and adapt over time through training. When a worker replies to a client message, Cerb learns to be more approving of similar messages in the future.

When workers click the **Report spam** button on a ticket, Cerb becomes more critical of similar messages in the future.

# Drafts

**Drafts** automatically save the current progress of a worker's message before they send it. This allows a composed message to be resumed at a later date, and it provides a _backup_ (e.g. the browser crashes or otherwise unintentionally closes).

Drafts are displayed in a ticket's conversation timeline, which instantly lets _other_ workers know that a response is being sent so they can move on to a different ticket. This prevents _duplication of effort_.

Similarly, a trainee can ask a supervisor to review their draft before they send a message to a client.

# Merge

Related tickets from the same participants can be **merged** together into a single ticket, which combines all of the messages and comments.

# Split

Conversely, if a ticket conversation diverges into multiple topics that would be better handled by other groups or workers, one or more messages can be **split** off into new tickets.

