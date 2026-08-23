---
id: "docs-scripting-filters--stripurlquerystrings"
title: "Scripting Filter: strip_url_querystrings"
url: "https://cerb.ai/docs/scripting/filters/#stripurlquerystrings"
summary: "Remove the query string from URLs in a block of text"
tags: ["docs", "docs-scripting"]
---
## strip\_url\_querystrings

Remove the query string portion from URLs in a text block. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where tracking parameters and session IDs add noise.

`|strip_url_querystrings`

```
{% set text %}
Check out https://example.com/page?utm_source=email&utm_campaign=q4 for details.
{% endset %}
{{text|strip_url_querystrings}}
```

```
Check out https://example.com/page for details.
```
