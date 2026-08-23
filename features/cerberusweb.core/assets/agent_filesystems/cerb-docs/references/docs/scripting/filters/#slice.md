---
id: "docs-scripting-filters--slice"
title: "Scripting Filter: slice"
url: "https://cerb.ai/docs/scripting/filters/#slice"
summary: "Extract part of a string, array, or object"
tags: ["docs", "docs-scripting"]
---
## slice

Extract part of a string, array, or object.

`|slice(start, length, preserve_keys)`

```
{{[1,2,3,4,5]|slice(2,2)|json_encode}}
{{"This is some text"|slice(0,4)}}
```

```
[3,4]
This
```
