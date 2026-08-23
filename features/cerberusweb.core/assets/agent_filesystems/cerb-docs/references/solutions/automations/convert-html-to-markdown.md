---
id: "solutions-automations-convert-html-to-markdown"
title: "Convert HTML to Markdown"
url: "https://cerb.ai/solutions/automations/convert-html-to-markdown/"
summary: "This page demonstrates how to use the `|html_to_text` filter to convert HTML content into Markdown format. The example shows converting an HTML document with formatting into clean, readable Markdown text."
tags: ["solutions", "solutions-automations"]
---
## Using |html\_to\_text filter

Here is an example of using the [|html\_to\_text](/docs/scripting/filters/#html_to_text) filter to convert HTML content into Markdown format.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message_html@text:
      <html><body><h1>Title</h1>This is an <b>HTML</b> message</body></html>
  return:
    output: {{message_html|html_to_text(truncate=50000)}}
```
- 
```
__return:
  output: |-
    # Title
    This is an HTML message
```

