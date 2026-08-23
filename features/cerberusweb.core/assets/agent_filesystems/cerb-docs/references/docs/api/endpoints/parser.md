---
id: "docs-api-endpoints-parser"
title: "Parser"
url: "https://cerb.ai/docs/api/endpoints/parser/"
summary: "This page provides instructions for using the Cerb API to parse new messages and replies. It includes examples of how to import a raw message source and how to parse a reply to an existing message. The examples demonstrate the use of MIME format for message content and the importance of headers like `Message-Id` and `In-Reply-To` for message threading. The page also highlights the use of subject masks as a fallback option for identifying message threads."
tags: ["docs"]
---
# Parse a new message

**POST /rest/parser/parse.json**

Import a raw message source.

### Example

```
POST /rest/parser/parse.json
Host: cerb.example
Authorization: Bearer <token>

message=From: jeff@localhost
  To: support@localhost
  Subject: This is a test through the Web-API.
  Message-Id: <abc2@local1234>
  X-Mailer: cURL+PHP5

  This is some message content.
```

# Parse a reply

**POST /rest/parser/parse.json**

Parsing a reply to an existing message is fairly simple. You should use the quoted `Message-Id:` header as an `In-Reply-To:` when possible, but you can also use a ticket mask in the subject as a fallback option.

### Example

```
POST /rest/parser/parse.json
Host: cerb.example
Authorization: Bearer <token>

message=From: ben@localhost
  To: support@localhost
  Subject: [parser #TKD-88128-525] This is a test through the Web-API.
  Message-Id: <abc1@local1234>
  X-Mailer: cURL+PHP5
  
  This is another reply using the subject masks.
```
