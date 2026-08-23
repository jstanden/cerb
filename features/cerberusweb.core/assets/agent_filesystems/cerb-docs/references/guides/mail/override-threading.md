---
id: "guides-mail-override-threading"
title: "Override email header threading"
url: "https://cerb.ai/guides/mail/override-threading/"
summary: "This guide demonstrates how to use `mail.filter` automations to override email threading behavior by manipulating `In-Reply-To:` and `References:` headers. It provides practical examples for preventing replies to closed tickets from reopening them, which helps maintain clean ticket histories and proper workflow states. The guide includes step-by-step instructions for creating and configuring the necessary automation and event listener, along with explanations of how the process works and why it's useful for support teams."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Preventing replies to closed tickets](#preventing-replies-to-closed-tickets)
- [Creating the automation](#creating-the-automation)
- [Configuring the event listener](#configuring-the-event-listener)
- [How it works](#how-it-works)
- [Testing the automation](#testing-the-automation)
- [Benefits](#benefits)
- [Conclusion](#conclusion)

# Introduction

Email threading uses the `In-Reply-To:` and `References:` headers to connect related messages into conversations. These headers contain message IDs that help email systems identify which messages are replies to others. When a client replies to a ticket, these headers tell Cerb which existing ticket should include the new message.

However, there are scenarios where you might want to override this default behavior.

- Preventing replies to closed tickets from reopening them.
- Creating new tickets for messages that would otherwise thread with existing tickets.
- Merging multiple messages from the same sender into a single conversation.
- Implementing custom threading rules based on specific business requirements.

This guide demonstrates how to use [mail.filter](/docs/automations/events/mail.filter/) automations to control threading behavior by modifying these email headers.

# Preventing replies to closed tickets

A common requirement is preventing replies to closed tickets from reopening those tickets. Instead, these replies should create new tickets while preserving the conversation context.

# Creating the automation

We will create an automation that checks if an incoming email is a reply to a closed ticket and removes threading headers if it is, effectively creating a new ticket.

Navigate to **Search&nbsp;» Automations** and click the (+) button.

 

Set the following properties.

Name: `example.mailFilter.closedTickets`

Trigger: `cerb.trigger.mail.filter`

 

Enter the following code in the editor.

```
start:
  decision/check_parent:
    outcome/has_parent:
      if@bool: {{parent_ticket_ is not empty}}
      then:
        decision/check_status:
          outcome/is_closed:
            if@bool: {{parent_ticket_status == 'closed'}}
            then:
              return:
                set:
                  headers:
                    in-reply-to: null
                    references: null
          outcome/not_closed:
            then:
              return:
    outcome/no_parent:
      then:
        return:
```

The visualization tab shows us a logic tree of our code.

 

Review the Automation.

 

Click **Save Changes** when finished.

# Configuring the event listener

Now we will connect the Automation to a new Automation Event Listener.

Navigate to **Search » Automation Event Listeners** and click the (+) button.

 

Set the following properties.

Name: `mailFilter.closedTickets`

Event: `mail.filter`

 

Enter the following code in the editor.

```
automation/preventReopenClosed:
  uri: cerb:automation:example.mailFilter.closedTickets
```

Review the Automation Event Listener.

 

Click **Create** when finished.

# How it works

Let's walk through the process of what happens when a client replies to a closed ticket.

- Email arrives: A client sends a reply to an email notification from a closed ticket.
- Mail filter runs: Cerb processes the incoming email through the `mail.filter` event.
- Automation checks ticket status. 
  - Detects the parent ticket ID from email headers.
  - Checks if the parent ticket exists and is closed.

- Headers are removed: If the ticket is closed, the automation removes the threading headers.
- New ticket created: Without threading headers, Cerb creates a new ticket instead of reopening the closed one.
- Context preserved: The reply still contains quoted text from previous messages, preserving context for support staff.

# Testing the automation

Let's test our automation with a real example. Here is a fullfilled and closed ticket.

 

After closing the ticket, the customer replies with a new question. Without our automation, this would reopen the closed ticket. Instead, our automation creates a new ticket.

 

Note how the new ticket preserves the conversation history.

 

# Benefits

This approach provides several advantages.

- Maintains workflow integrity: Closed tickets stay closed until deliberately reopened by staff.
- Preserves conversation context: New tickets still contain the email history in the message body.
- Keeps related tickets organized: New tickets inherit the same subject line (with "Re:") making them easy to find.
- Works with any email client: No special requirements for customers to create new conversations.

# Conclusion

By manipulating email headers with `mail.filter` automations, you can implement custom threading behavior that maintains your workflow integrity while providing a seamless experience for your customers.

We encourage you to explore how this technique can be adapted for other requirements specific to your organization's workflow needs beyond the closed ticket example shown here.

