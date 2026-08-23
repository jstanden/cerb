---
id: "docs-scripting-filters--markdowntohtml"
title: "Scripting Filter: markdown_to_html"
url: "https://cerb.ai/docs/scripting/filters/#markdowntohtml"
summary: "Convert Markdown formatting to HTML"
tags: ["docs", "docs-scripting"]
---
## markdown\_to\_html

Convert Markdown[2](#fn:markdown) formatting to HTML:

```
{% set markdown %}
This is **bold** text with a link
{% endset %}
{{markdown|markdown_to_html}}
```

```
<p>This is <strong>bold</strong> text with a <a href="https://cerb.ai/">link</a></p>
```
