---
id: "docs-scripting-filters--numberformat"
title: "Scripting Filter: number_format"
url: "https://cerb.ai/docs/scripting/filters/#numberformat"
summary: "Format a number with thousand separators and decimal places"
tags: ["docs", "docs-scripting"]
---
## number\_format

Format a number with thousand separators and decimal places:

```
{% set cost = 16858 %}
That will be ${{cost|number_format(2,'.',',')}}
```

```
That will be $16,858.00
```
