---
id: "docs-scripting-functions--arraycombine"
title: "Scripting Function: array_combine"
url: "https://cerb.ai/docs/scripting/functions/#arraycombine"
summary: "Create a new array with the given keys and values"
tags: ["docs", "docs-scripting"]
---
## array\_combine

The **array\_combine** function creates a new array with the given `keys` and `values`:

```
{% set keys = ['name', 'age', 'email'] %}
{% set values = ['Janey Youve', '30-ish', 'janey@cerb.example'] %}
{% set person = array_combine(keys, values) %}
{{person.name}} can be reached at {{person.email}}
```

```
Janey Youve can be reached at janey@cerb.example
```
