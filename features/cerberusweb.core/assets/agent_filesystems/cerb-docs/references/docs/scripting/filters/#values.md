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

[\< Functions](/docs/scripting/functions/)

[Tests \>](/docs/scripting/tests/)

# References

1. Wikipedia: Hash-based message authentication code (HMAC) - https://en.wikipedia.org/wiki/Hash-based\_message\_authentication\_code&nbsp;[↩](#fnref:hmac)

2. Wikipedia: Markdown - https://en.wikipedia.org/wiki/Markdown&nbsp;[↩](#fnref:markdown)

3. Wikipedia: MD5 - https://en.wikipedia.org/wiki/MD5&nbsp;[↩](#fnref:md5)

4. Wikipedia: Regular Expression - https://en.wikipedia.org/wiki/Regular\_expression&nbsp;[↩](#fnref:regexp)

5. Wikipedia: SHA-1 - https://en.wikipedia.org/wiki/SHA-1&nbsp;[↩](#fnref:sha1)