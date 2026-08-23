---
id: "guides-mail-relaying"
title: "Respond to messages from an external email client"
url: "https://cerb.ai/guides/mail/relaying/"
summary: "This page provides detailed information on how Cerb's email relay feature allows workers to respond to messages using external email clients like Gmail, Outlook, or mobile phones, while maintaining the benefits of Cerb's platform such as shared history and privacy protection. It explains how administrators can enable the mail relay, and how messages can be relayed to external accounts either through bots or directly from the Cerb user interface. The page also outlines the use of specific #commands in email replies, which allow users to perform actions like adding comments, cutting content, changing conversation status, and managing conversation watch status, all while ensuring that personal email addresses remain private."
tags: ["guides"]
---
# Introduction

The email relay allows workers to respond to messages from external mail applications (e.g. Gmail, mobile phones, Outlook, etc) instead of requiring them to always use Cerb in the web browser.

Relayed responses are received from a worker's personal email address and rewritten so they appear to be from Cerb before being sent to a conversation's recipients. This process protects the privacy of personal worker email addresses, while still providing the benefits of Cerb (e.g. shared history, assignments, etc).

- [Introduction](#introduction)
- [Enabling the mail relay](#enabling-the-mail-relay)
- [Relaying messages to external email accounts](#relaying-messages-to-external-email-accounts)
  - [From bots](#from-bots)
  - [From the UI](#from-the-ui)

- [Using #commands in replies](#using-commands-in-replies)
  - [#comment](#comment)
  - [#cut](#cut)
  - [#noreply](#noreply)
  - [#reopen](#reopen)
  - [#sig](#sig)
  - [#status](#status)
  - [#start comment](#start-comment)
  - [#unwatch](#unwatch)
  - [#watch](#watch)

# Enabling the mail relay

Administrators can enable mail relay functionality from [Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» External Relay](/docs/setup/mail/relay/).

# Relaying messages to external email accounts

## From bots

In bots, on message-based events, you can use the **Send&nbsp;» Email&nbsp;» Relay To Workers** action to automatically relay specific messages to a list of workers:

 

## From the UI

From Cerb, you can also do a one-time relay for any message from the **Reply** menu on a ticket timeline:

 

# Using #commands in replies

You can use the following **#commands** when replying to email through the relay.

Each command must appear on its own line.

## #comment

Add a private comment to the conversation timeline. By default, a comment is **in addition** to your reply.

```
You're all set!

#cut
#status closed
#comment I called the client a couple minutes ago and we resolved this.
```

## #cut

Ignore all content below this line when sending a reply.

This is useful in mobile email clients where deleting quoted text is tedious. It's also useful to make sure other #commands aren't accidentally sent to the recipients.

```
This is my quick reply to the customer.

#sig
#cut
#status waiting
#reopen +2 days

On Friday, customer@example.com wrote:
> This is the original quoted message, and it will not be displayed as 
> part of your reply because of the nifty #cut tag above. You don't 
> have to waste your time deleting this message in your mobile 
> email client.
```

## #noreply

Only apply tags to the conversation and do not send anything to requesters.

```
#comment I'll take care of this on Monday.
#noreply
```

## #reopen

If waiting or closed, reopen the conversation on at the given time (`+2 days`, `next Tuesday`).

```
#reopen +2 days
```

## #sig

Insert your full signature on the current line (based on the conversation's group and bucket).

```
This is my reply to the recipients.

#sig
```

## #status

Change conversation status (`open`, `waiting`, or `closed`]).

```
You're all set!

#sig
#status closed
```

## #start comment

Add a private multi-line comment to the conversation timeline, and terminate with **#end**.

```
#start comment
This is the first line of the comment.
This is the second line of the comment.
#end
```

## #unwatch

Stop watching this conversation.

```
#noreply
#unwatch
```

## #watch

Start watching this conversation.

```
I'll research this and get right back to you.

#sig
#watch
```
