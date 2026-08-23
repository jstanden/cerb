---
id: "docs-automations-commands-email-parse"
title: "Automations: email.parse"
url: "https://cerb.ai/docs/automations/commands/email.parse/"
summary: "This page provides detailed information on the 'email.parse' command used in Cerb automations to convert a MIME-encoded email message into a ticket. It outlines the syntax and structure of the command, including the necessary inputs, such as the MIME-encoded email message, and the expected output, which is a ticket dictionary. The page also explains the different stages of the command execution, including 'on_simulate' for simulation commands, 'on_success' for successful execution commands, and 'on_error' for handling failures. Each section is clearly defined to guide users in implementing the command effectively within their automation workflows."
tags: ["docs", "docs-automations"]
---
The **email.parse:** command parses a MIME-encoded email message into a [ticket](/docs/records/types/ticket/).

```
start:
  email.parse/parse:
    output: results
    inputs:
      message@text:
        From: customer@cerb.example
        To: support@cerb.example
        Subject: This is an example
        
        This is an example message.
    on_simulate:
      set:
        results:
          _context: ticket
          id@int: 123
    on_success:
      return:
        ticket_id@key: results:id
```

- [Syntax](#syntax)
  - [inputs:](#inputs)
  - [output:](#output)
  - [on\_simulate:](#on_simulate)
  - [on\_success:](#on_success)
  - [on\_error:](#on_error)

- [Examples](#examples)
  - [Parse a quoted-printable message with emoji](#parse-a-quoted-printable-message-with-emoji)

# Syntax

## inputs:

| Key | &nbsp; |
| --- | --- |
| `message@text:` | A MIME-encoded email message as a text block. At minimum this should contain headers, a blank line, and a plaintext body. |

## output:

Save the results in this placeholder.

## on\_simulate:

The [commands](/docs/automations/#commands) to run during simulation instead of parsing the email.

If omitted, the email is parsed during simulation.

## on\_success:

The [commands](/docs/automations/#commands) to run on success.

The `output:` placeholder receives a [ticket](/docs/records/types/ticket/#dictionary-placeholders) dictionary.

## on\_error:

The [commands](/docs/automations/#commands) to run on failure. If omitted, the automation exits in the `error` [state](/docs/automations/#exit-states).

The `output:` placeholder receives a dictionary with these keys:

| Key | &nbsp; |
| --- | --- |
| `error` | The error message. |

# Examples

### Parse a quoted-printable message with emoji

- [automation](#)
- [policy](#)

- 
```
start:
  set:
    message_subject: Welcome 🌟 to our service!
    message_body@text:
      Hello and welcome to our new service! 😀
      
      We're delighted 🎉 to have you as a member of our community.
      This is a sample email with emojis 🚀 and quoted-printable encoding.
      
      Have a great day! 🌈
      
      Best regards,
      The Team 👋
    
  email.parse:
    output: new_ticket
    inputs:
      message@text:
        From: sender@example.com
        To: recipient@example.com
        Date: {{'now'|date('r')}}
        MIME-Version: 1.0
        Content-Type: text/plain; charset="UTF-8"
        Content-Transfer-Encoding: quoted-printable
        Subject: =?UTF-8?Q?{{message_subject|qp_encode}}?=
        
        {{message_body|qp_encode}}
```
- 
```
commands:
  email.parse:
    allow@bool: yes
```

