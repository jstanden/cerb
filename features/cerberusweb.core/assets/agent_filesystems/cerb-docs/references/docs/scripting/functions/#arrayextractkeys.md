---
id: "docs-scripting-functions--arrayextractkeys"
title: "Scripting Function: array_extract_keys"
url: "https://cerb.ai/docs/scripting/functions/#arrayextractkeys"
summary: "Return given keys from all elements of a list"
tags: ["docs", "docs-scripting"]
---
## array\_extract\_keys

Returns the given keys from all elements of a list.

```
{% set records = [
	{
		id: 1,
		subject: "Help with the API",
		status: "open",
		sender: "customer@cerb.example",
	},
	{
		id: 2,
		subject: "Automating email replies",
		status: "open",
		sender: "customer@cerb.example",
	}
] %}
Sender,Subject,Status
{{array_extract_keys(records, ['sender','subject','status'])|csv}}
```

```
Sender,Subject,Status
customer@cerb.example,"Help with the API",open
customer@cerb.example,"Automating email replies",open
```
