---
id: "docs-scripting-filters--unescape"
title: "Scripting Filter: unescape"
url: "https://cerb.ai/docs/scripting/filters/#unescape"
summary: "Decode HTML entities"
tags: ["docs", "docs-scripting"]
---
## unescape

Decode HTML entities:

```
{{"&quot;iPhone&quot; is &copy; Apple, Inc."|unescape}}
```

```
"iPhone" is © Apple, Inc.
```
