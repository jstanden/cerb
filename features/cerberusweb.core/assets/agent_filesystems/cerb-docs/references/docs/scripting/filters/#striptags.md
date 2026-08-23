---
id: "docs-scripting-filters--striptags"
title: "Scripting Filter: striptags"
url: "https://cerb.ai/docs/scripting/filters/#striptags"
summary: "Remove HTML tags from a string"
tags: ["docs", "docs-scripting"]
---
## striptags

Remove HTML tags from a string.

```
{% set html = "This <b>string</b> has <b>HTML</b> tags!" %}
{{html|striptags}}
```

```
This string has HTML tags!
```
