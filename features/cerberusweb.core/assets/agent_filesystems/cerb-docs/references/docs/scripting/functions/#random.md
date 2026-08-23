---
id: "docs-scripting-functions--random"
title: "Scripting Function: random"
url: "https://cerb.ai/docs/scripting/functions/#random"
summary: "Return a random item from string/array or random number"
tags: ["docs", "docs-scripting"]
---
## random

Return a random item from a string or array, or a random number between 0 and the given number (inclusive).

```
{{random([1,2,3,4,5,6,7,8,9,0])}}
{{random("abcdefghijklmnopqrstuvwxyz")}}
{{random(20)}}
```

```
9
o
17
```
