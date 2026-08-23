---
id: "guides-developers-testing-inbound-email"
title: "Test inbound email"
url: "https://cerb.ai/guides/developers/testing-inbound-email/"
summary: "This guide demonstrates how to test email processing using the mail import tool. It provides a complete example message with HTML content and attachments that you can use to verify how your system handles different types of email content. The guide includes steps for importing messages, verifying ticket creation, and checking attachment handling, making it easy to test email-related features without affecting your production environment."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Understanding MIME Format](#understanding-mime-format)
- [Testing Inbound Email](#testing-inbound-email)
- [Verifying Results](#verifying-results)

# Introduction

When developing or testing email-related features, it's crucial to verify how messages will be processed without sending actual emails to your production system.

The mail import tool provides a safe environment for testing email processing, including complex MIME-formatted messages with attachments.

# Understanding MIME Format

MIME (Multipurpose Internet Mail Extensions) is an internet standard that extends email capabilities beyond simple text.

It enables:

- Sending HTML-formatted emails with styling and formatting
- Including attachments of various file types
- Supporting international character sets
- Combining multiple content types in a single message

When testing email functionality, proper MIME formatting is essential as it affects how your system processes and displays different parts of the message.

# Testing Inbound Email

The mail import tool at **Setup&nbsp;» Mail&nbsp;» Incoming&nbsp;» Import** allows you to test this functionality by importing sample messages.

This provides several benefits:

- Test email processing without affecting production systems
- Verify proper handling of MIME-formatted content
- Validate attachment processing and storage
- Confirm HTML rendering and text formatting
- Test character encoding support

Here is an example message. It includes common elements like HTML formatting and base64 encoded attachments.

```
From: sender@example.com
To: recipient@example.com
Subject: Test Email with Short Attachments
Date: Mon, 17 Feb 2025 10:00:00 -0500
Message-ID: <unique-message-id@example.com>
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="boundary123"

--boundary123
Content-Type: multipart/alternative; boundary="boundary456"

--boundary456
Content-Type: text/plain; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable

This is a test email demonstrating different types of short attachments.

Attachments included:
1. config.json - A small JSON configuration file
2. sample.txt - A brief text file
3. example.csv - A minimal CSV file

--boundary456
Content-Type: text/html; charset="UTF-8"
Content-Transfer-Encoding: quoted-printable

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Email</title>
</head>
<body>
    <h1>Test Email</h1>
    <p>This is a test email demonstrating different types of short attachments.</p>
    
    <h2>Attachments included:</h2>
    <ul>
        <li>config.json - A small JSON configuration file</li>
        <li>sample.txt - A brief text file</li>
        <li>example.csv - A minimal CSV file</li>
    </ul>
</body>
</html>

--boundary456--

--boundary123
Content-Type: application/json
Content-Disposition: attachment; filename="config.json"
Content-Transfer-Encoding: base64

ewogICJhcHBOYW1lIjogIlRlc3RBcHAiLAogICJ2ZXJzaW9uIjogIjEuMC4wIiwKICAiZGVidWciOiBmYWxzZQp9

--boundary123
Content-Type: text/plain
Content-Disposition: attachment; filename="sample.txt"
Content-Transfer-Encoding: base64

VGhpcyBpcyBhIHNhbXBsZSB0ZXh0IGZpbGUuCkl0IGhhcyBqdXN0IGEgZmV3IGxpbmVzLgpQZXJmZWN0IGZvciB0ZXN0aW5nIQ==

--boundary123
Content-Type: text/csv
Content-Disposition: attachment; filename="example.csv"
Content-Transfer-Encoding: base64

ZXhhbXBsZV9pZCxleGFtcGxlX25hbWUsdmFsdWUKMSxFeGFtcGxlIEEsMTAwCjIsRXhhbXBsZSBCLDIwMAozLEV4YW1wbGUgQywzMDA=

--boundary123--
```

Paste your test email into the import tool.

 

# Verifying Results

When you import a test message, it creates a new ticket just as if it was received as an email.

You can then review the ticket to verify formatting.

 

And check the attachments.

 

By using the mail import tool, you can thoroughly test your email processing functionality before deploying to production, ensuring a smooth experience for your users.

