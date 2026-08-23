---
id: "docs-scripting-filters--trim"
title: "Scripting Filter: trim"
url: "https://cerb.ai/docs/scripting/filters/#trim"
summary: "Remove leading and/or trailing whitespace from a string"
tags: ["docs", "docs-scripting"]
---
## trim

Remove leading and/or trailing whitespace from a string.

`|trim(character_mask, side)`

- `character_mask` The characters to remove
- `side`
  - both
  - left
  - right

```
{% set str = " whitespace " %}
{{str|trim}}
{{str|trim(' ', 'left')}}
{{str|trim(' ', side='right')}}
```

```
whitespace
whitespace    
    whitespace
```
