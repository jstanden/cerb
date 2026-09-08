---
id: "docs-scripting-functions--cerbextractmentions"
title: "Scripting Function: cerb_extract_mentions"
url: "https://cerb.ai/docs/scripting/functions/#cerbextractmentions"
summary: "Return the workers named by @mention in text"
tags: ["docs", "docs-scripting"]
---
## cerb\_extract\_mentions

Return the workers named by `@mention` in a block of text.

`cerb_extract_mentions(text)`

**Arguments:**

| Name | Notes |
| --- | --- |
| `text` | Plain text to scan. Not HTML. |

**Returns:** A plain list of worker [dictionaries](/docs/guide/developers/dictionaries/), one per matched worker. Empty when nothing matches.

Each element is a full worker dictionary, so any [worker placeholder](/docs/records/types/worker/) can be read from it.

```
{% set mentioned = cerb_extract_mentions("Paging @kina for review") %}
{% for worker in mentioned %}
{{worker.full_name}} -- {{worker.title}}
{% endfor %}
```
