---
id: "docs-scripting-filters--default"
title: "Scripting Filter: default"
url: "https://cerb.ai/docs/scripting/filters/#default"
summary: "Give a default value to empty variables"
tags: ["docs", "docs-scripting"]
---
## default

You can use the **default** filter to give a default value to empty variables:

```
{% set name = '' %}
Hi {{name|default('there')}}
```

```
Hi there
```
