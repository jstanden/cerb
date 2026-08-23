---
id: "docs-scripting-filters--split"
title: "Scripting Filter: split"
url: "https://cerb.ai/docs/scripting/filters/#split"
summary: "Convert a string to an array with the given delimiter"
tags: ["docs", "docs-scripting"]
---
## split

Convert a string to an array with the given delimiter.

`|split(delimiter, limit)`

```
{{"1,2,3,4,5"|split(',')|json_encode}}
```

```
["1","2","3","4","5"]
```
