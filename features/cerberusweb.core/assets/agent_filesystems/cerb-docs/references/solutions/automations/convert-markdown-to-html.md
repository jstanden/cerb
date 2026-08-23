---
id: "solutions-automations-convert-markdown-to-html"
title: "Convert Markdown to HTML"
url: "https://cerb.ai/solutions/automations/convert-markdown-to-html/"
summary: "This page demonstrates how to use the `|markdown_to_html` filter to convert Markdown content into HTML format. The example shows converting a Markdown document with formatting into sanitized HTML output, with options for handling untrusted content."
tags: ["solutions", "solutions-automations"]
---
## Using |markdown\_to\_html filter

Here is an example of using the [|markdown\_to\_html](/docs/scripting/filters/#markdown_to_html) filter to convert Markdown content into HTML format.

The `is_untrusted` parameter sanitizes HTML output (e.g. script blocks and images).

- [automation](#)
- [output](#)

- 
```
start:
  set:
    markdown_text@text:
      # Title
      This is a **Markdown** message.
  return:
    output: {{markdown_text|markdown_to_html(is_untrusted=true)}}
```
- 
```
__return:
  output: |-
    <h1>Title</h1>
    <p>This is a <strong>Markdown</strong> message.</p>
```

