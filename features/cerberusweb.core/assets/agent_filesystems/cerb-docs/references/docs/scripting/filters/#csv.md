---
id: "docs-scripting-filters--csv"
title: "Scripting Filter: csv"
url: "https://cerb.ai/docs/scripting/filters/#csv"
summary: "Format an array as a comma-separated values list"
tags: ["docs", "docs-scripting"]
---
## csv

Format an array as a comma-separated values list. This is useful for exporting reports for Excel from bots.

Objects and dictionaries are automatically coerced to arrays.

```
{% set records = [
	{
		id: 1,
		subject: "Help with the API",
	},
	{
		id: 2,
		subject: "Automating email replies", 
	}
] %}
ID,Subject
{{records|csv}}
```

```
ID,Subject
1,"Help with the API"
2,"Automating email replies"
```
