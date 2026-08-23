---
id: "docs-scripting-filters--baseconvert"
title: "Scripting Filter: base_convert"
url: "https://cerb.ai/docs/scripting/filters/#baseconvert"
summary: "Convert between number system bases"
tags: ["docs", "docs-scripting"]
---
## base\_convert

Convert between number system bases.

```
{% set int = 123456789 %}
{{int|base_convert(10,16)}}

{% set hex = '75bcd15' %}
{{hex|base_convert(16,10)}}
```

```
75bcd15

123456789
```
