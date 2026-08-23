---
id: "docs-scripting-filters--stripdatauris"
title: "Scripting Filter: strip_data_uris"
url: "https://cerb.ai/docs/scripting/filters/#stripdatauris"
summary: "Remove base64-encoded content from data URIs"
tags: ["docs", "docs-scripting"]
---
## strip\_data\_uris

Remove the base64-encoded content from data URIs in a text block. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where the encoded payload contributes noise rather than searchable terms.

`|strip_data_uris`

```
{% set html %}
<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...">
{% endset %}
{{html|strip_data_uris}}
```

```
<img src="data:image/png;base64,">
```
