---
id: "docs-scripting-filters--nl2br"
title: "Scripting Filter: nl2br"
url: "https://cerb.ai/docs/scripting/filters/#nl2br"
summary: "Convert newline characters to HTML breaks"
tags: ["docs", "docs-scripting"]
---
## nl2br

Convert newline characters (`\n`) to HTML breaks (`<br />`):

```
{% set text = "This has
line feeds
in the text
"%}
{{text|nl2br}}
```

```
This has<br />
line feeds<br />
in the text<br />
```
