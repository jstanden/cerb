---
id: "docs-scripting-filters--values"
title: "Scripting Filter: values"
url: "https://cerb.ai/docs/scripting/filters/#values"
summary: "Return the values of an array with sequential keys"
tags: ["docs", "docs-scripting"]
---
## values

Return the values of an array with sequential keys. This is the filter equivalent of the [array\_values()](/docs/scripting/functions/#array_values) function.

```
{% set countries = {
  'CA': 'Canada',
  'CN': 'China',
  'DE': 'Germany',
  'IN': 'India',
  'MX': 'Mexico',
  'US': 'United States',
} %}
{{countries|values|json_encode}}
```

```
["Canada","China","Germany","India","Mexico","United States"]
```
