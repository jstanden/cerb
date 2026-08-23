---
id: "docs-scripting-filters--convertencoding"
title: "Scripting Filter: convert_encoding"
url: "https://cerb.ai/docs/scripting/filters/#convertencoding"
summary: "Convert character encodings between different formats"
tags: ["docs", "docs-scripting"]
---
## convert\_encoding

Convert character encodings to the first argument from the second. If the second argument is blank then Cerb will attempt to auto-detect the current encoding.

```
{{"This has 😂 emoji"|convert_encoding('iso-8859-1', 'utf-8')}}
```

```
This has ? emoji
```
