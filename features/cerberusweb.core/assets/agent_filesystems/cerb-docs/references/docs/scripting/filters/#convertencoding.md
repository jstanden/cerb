---
id: "docs-scripting-filters--convertencoding"
title: "Scripting Filter: convert_encoding"
url: "https://cerb.ai/docs/scripting/filters/#convertencoding"
summary: "Convert character encodings between different formats"
tags: ["docs", "docs-scripting"]
---
## convert\_encoding

Convert character encodings to the first argument from the second. If the second argument is blank then Cerb will attempt to auto-detect the current encoding.

`|convert_encoding(to, from)`

Converting _away_ from UTF-8 produces bytes that a UTF-8 page can't display, so a single conversion in that direction looks like mojibake even when it succeeded. A round trip shows the text survives intact:

```
{{"Café"|convert_encoding('iso-8859-1', 'utf-8')|convert_encoding('utf-8', 'iso-8859-1')}}
```

```
Café
```

When any character can't be represented in the target encoding, the _entire_ conversion fails and returns an empty string. It is not a partial result with the unmappable characters removed. One emoji loses the whole message, and no error is reported.

Both of these return nothing: {{"This has 😂 emoji"|convert\_encoding('iso-8859-1', 'utf-8')}} and {{"Café"|convert\_encoding('ASCII', 'utf-8')}}
