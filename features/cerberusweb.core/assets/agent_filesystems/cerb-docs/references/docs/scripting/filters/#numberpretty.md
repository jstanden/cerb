---
id: "docs-scripting-filters--numberpretty"
title: "Scripting Filter: number_pretty"
url: "https://cerb.ai/docs/scripting/filters/#numberpretty"
summary: "Format a large number in a human-readable form with a magnitude suffix"
tags: ["docs", "docs-scripting"]
---
## number\_pretty

Format a large number in a human-readable form, with a magnitude suffix of `K`, `M`, `B`, or `T`:

```
{{12345678|number_pretty(1)}}
```

```
12.3M
```

The optional argument determines the number of digits of precision, and defaults to none:

```
{{32768|number_pretty}}
```

```
32K
```

This **truncates** rather than rounds, which is where it parts ways with [`bytes_pretty`](#bytes_pretty). A 32,768-token context window is universally called "32K", never "33K", and truncating also keeps `999999` from rounding up into a nonsensical `1000K`.

A number below 1,000 is returned as-is, and a negative number keeps its sign. A non-numeric value returns an empty string.
