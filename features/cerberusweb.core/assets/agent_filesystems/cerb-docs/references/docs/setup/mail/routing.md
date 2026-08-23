---
id: "docs-setup-mail-routing"
title: "Setup: Mail Routing"
url: "https://cerb.ai/docs/setup/mail/routing/"
summary: "This page provides guidance on setting up mail routing in Cerb. It explains how to configure rules to automatically direct incoming emails to specific groups based on message properties such as recipient addresses. Examples include routing emails sent to `support@*` to the Support group, `orders@*` to Sales, and `receipts@*` to Billing. The page also introduces the concept of Routing KATA, which allows for complex workflows through automations, though they are not mandatory. It details the use of conditions and actions within routing rules, such as checking recipients, subject lines, and other message attributes to determine the appropriate group for each email. The page includes sample rules for various departments like Development, Sales, Billing, Corporate, and Support, demonstrating how to set up these automated routing processes."
tags: ["docs"]
---
You can configure rules to automatically route new mail to groups based on message properties.

For example, you may want to route messages addressed to `support@*` to the **Support** group, `orders@*` to **Sales**, and `receipts@*` to **Billing**.

These messages will be delivered to the **Inbox** bucket in those groups, unless specified otherwise, and group managers can configure additional sorting from there.

- [Routing KATA](#routing-kata)
  - [Conditions:](#conditions)
    - [script](#script)
    - [recipients](#recipients)
    - [sender\_email](#sender_email)
    - [spam\_score](#spam_score)
    - [subject](#subject)
    - [body](#body)
    - [header](#header)

  - [Actions](#actions)
    - [bucket](#bucket)
    - [comment](#comment)
    - [group](#group)
    - [importance](#importance)
    - [owner](#owner)
    - [watchers](#watchers)

# Routing KATA

**Note:** Automations are available for complex workflows but are no longer required.

Conditions include: `script`, `recipients`, `sender_email`, `spam_score`, `subject`, `body`, and `header`.

Actions include: `bucket`, `comment`, `group`, `importance`, `owner`, and `watchers`.

## Conditions:

Multiple conditions in a single `if:` node are "all of these", and multiple `if:` nodes are "any of these".

```
rule/dev:
  if/to:
    recipients: dev@, development@, bugs@
  if/subj:
    subject: [Bugs] *
    body: * bug report *
  then:
    group: Development
```

In this scenario, tickets would be routed to the Development group if they were either sent to `dev@`. `development@` or `bugs@`, or they had both a subject line containing `[Bugs]` and body containing `bug report`.

### script

With the `script` condition, you can route based on any custom scripting you would like, such as boolean logic or negation. It also has full access to the `sender` dictionary.

```
rule/dev:
  if:
    recipient: dev@
    script: {{subject != 'dev conference invite'}}
  then:
    group: Development
rule/trouble:
  if/name:
    script@text: 
      {{
        sender_full_name == "Trouble Customer"
        or sender_num_spam >= 10
      }}
  then:
    group: Special Handling
    owner: KinaHalpue
```

### recipients

`recipients` routes based on the address(es) the mail is sent to. You can use full addresses. prefixes or domains. You can include multiple addresses in a comma-separated list or in a line-separated list with the `@list` annotation.

```
rule/billing:
    if:
      recipients: billing@, receipts@
    then:
      group: Billing
  rule/brand2:
    if:
      recipients@list: 
        help@brand2.example
        sales@brand2.example
    then:
      group: Brand 2
```

### sender\_email

`sender_email` routes based on the address(es) of the sender. You can use full addresses. prefixes or domains. You can include multiple addresses in a comma-separated list or in a line-separated list with the `@list` annotation.

```
rule/vip:
    if:
      sender_email: *@vipcustomer.example
    then:
      group: VIP
      importance: 90
```

### spam\_score

`spam_score` routes based on the assigned spam score of an email. You can use `<`,`>` and `=` operators to specify a "greater than" or "less than" value.

```
rule/spam:
    if:
      spam_score: >=80%
    then:
      group: Spam
```

### subject

`subject` routes based on the text of the email subject line.

```
rule/bugs:
  if:
    subject: this is a bug
  then:
    group: Development
```

### body

`body` routes based on content of the email message body.

```
rule/campaign:
  if:
    body: *I'm responding to your marketing campaign*
  then:
    group: Marketing
```

### header

`header` routes based on content from the message header

```
rule/autoreplies:
  if:
    header: 
      Auto-Submitted: auto-generated
  then:
    group: Spam
    bucket: Autoreplies
```

## Actions

Actions are what you want to occur if the conditions are met. You can have multiple actions for each routing rule.

### bucket

`bucket` sets a bucket to route the ticket.

```
rule/bugs:
  if/email:
    recipients: bugs@
  if/subj:
    subject: bug report
  then:
    group: Development
    bucket: Bugs
```

### comment

`comment` adds a comment with the given text to the resulting ticket.

```
rule/vip:
  if:
    sender_email: *@vipcustomer.example
  then:
    group: VIP
    importance: 90
    comment: This is a high value customer. Please respond rapidly.
```

### group

`group` defines the group a ticket will be moved to. If you do not also use `bucket` the ticket will be placed in the group's inbox.

```
rule/billing:
  if:
    recipients: billing@, receipts@
  then:
    group: Billing
```

### importance

`importance` sets the "importance" field on the ticket. It can be a value between 0 and 100.

```
rule/vip:
  if:
    sender_email: *@vipcustomer.example
  then:
    group: VIP
    importance: 90
rule/spam:
  if:
    spam_score: >=80%
  then:
    group: Spam
    importance: 0
```

### owner

`owner` assigns an owner to the ticket. This is done with the @mention name of the relevant worker.

```
rule/billing:
  if:
    recipients: billing@, receipts@
  then:
    group: Billing
    owner: karlkwota
```

### watchers

`watchers` assigns watchers to a ticket with a comma-separated list of their @mention names. This cam be useful, for example, for assigning developers as watchers on tickets about bug reports or certain workers for tickets from a VIP customer.

```
rule/bugs:
  if/email:
    recipients: bugs@
  if/subj:
    subject: bug report
  then:
    group: Development
    bucket: Bugs
    watchers@csv: marakusako, milodade
```
