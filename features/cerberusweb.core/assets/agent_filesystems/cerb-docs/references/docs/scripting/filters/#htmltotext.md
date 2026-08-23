---
id: "docs-scripting-filters--htmltotext"
title: "Scripting Filter: html_to_text"
url: "https://cerb.ai/docs/scripting/filters/#htmltotext"
summary: "Convert HTML content to plain text"
tags: ["docs", "docs-scripting"]
---
## html\_to\_text

Convert HTML content to plain text.

`|html_to_text(truncate=50000)`

| **truncate** | The maximum length to parse (bytes) |

```
{% set html %}
<p>
	This has <b>bold</b> and <u>underlined</u> text with <a href="https://cerb.ai/">links</a>.
</p>
<p>
	List:
	<ul>
		<li>This</li>
		<li>is</li>
		<li>a</li>
		<li>list</li>
	</ul>
</p>
{% endset %}
{{html|html_to_text}}
```

```
This has bold and underlined text with links <https://cerb.ai/>.
 
List:
* This
* is
* a
* list
```
