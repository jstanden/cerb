---
id: "docs-scripting-filters--kataencode"
title: "Scripting Filter: kata_encode"
url: "https://cerb.ai/docs/scripting/filters/#kataencode"
summary: "Emit an object/array as a KATA text block"
tags: ["docs", "docs-scripting"]
---
## kata\_encode

Emit an object/array as a KATA text block:

```
{% set object = {
	colors: ["red","green","blue"],
	size: 100,
} %}
{{object|kata_encode}}
```

```
colors@list:
  red
  green
  blue
size@int: 100
```

Each value is labeled with the [annotation](/docs/kata/#key-annotations) for its type – `@int` for a whole number, `@float` for a decimal, `@bool` for true or false – so a reader that applies those annotations gets numbers and booleans back rather than text. A value with no annotation is text.
