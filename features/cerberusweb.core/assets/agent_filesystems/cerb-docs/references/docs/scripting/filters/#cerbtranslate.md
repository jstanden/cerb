---
id: "docs-scripting-filters--cerbtranslate"
title: "Scripting Filter: cerb_translate"
url: "https://cerb.ai/docs/scripting/filters/#cerbtranslate"
summary: "Convert string IDs into text in the current worker's language"
tags: ["docs", "docs-scripting"]
---
## cerb\_translate

Converts string IDs (like `status.open`) into text in the current worker's language.

```
The ticket is {{'status.open'|cerb_translate}}
```

```
The ticket is open.
```
