---
id: "docs-scripting-filters--quote"
title: "Scripting Filter: quote"
url: "https://cerb.ai/docs/scripting/filters/#quote"
summary: "Add quote prefixes to lines of text for email replies"
tags: ["docs", "docs-scripting"]
---
## quote

```
{% set text = "This is a message you are replying to.

You should quote it.
" %}
{{text|quote}}
```

```
> This is a message you are replying to.
>
> You should quote it.
```
