---
id: "docs-scripting-filters--round"
title: "Scripting Filter: round"
url: "https://cerb.ai/docs/scripting/filters/#round"
summary: "Round a number with desired precision and method"
tags: ["docs", "docs-scripting"]
---
## round

Round a number with desired precision.

`|round(precision,method)`

- `precision` The number of floating point digits.
- `method`: 
  - common
  - ceil
  - floor

```
{% set pi = 3.141592653589793238462643383279502884197169399375105820974944592307816406286 %}
{{pi|round}}
{{pi|round(5)}}
{{pi|round(5,'ceil')}}
```

```
3
3.14159
3.1416
```
