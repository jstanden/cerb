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
{{"&amp;quot;iPhone&amp;quot; is &amp;copy; Apple, Inc."|unescape}}
```

```
"iPhone" is © Apple, Inc.
```
