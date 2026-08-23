---
id: "solutions-automations-strip-line-prefix"
title: "Strip common prefixes from text lines"
url: "https://cerb.ai/solutions/automations/strip-line-prefix/"
summary: "This page demonstrates how to use the strip_lines filter in automation scripting to remove common prefixes from text lines. It shows how to strip quoted email text and other prefixed content, making it useful for email processing and text manipulation tasks."
tags: ["solutions", "solutions-automations"]
---
## Removing email quotes

Here are examples of using the [|strip\_lines](/docs/scripting/filters#strip_lines) filter (prefix removal, quote stripping) for text processing in automation scripting.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message@text:
      > This is quoted text
      > From a prior conversation
      > and we want to strip it out
      >
      This is the reply
  return:
    message: {{message|strip_lines(prefixes='>')}}
```
- 
```
__return:
  message: This is the reply
```

