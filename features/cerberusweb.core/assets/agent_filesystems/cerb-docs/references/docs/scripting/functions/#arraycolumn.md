---
id: "docs-scripting-functions--arraycolumn"
title: "Scripting Function: array_column"
url: "https://cerb.ai/docs/scripting/functions/#arraycolumn"
summary: "Extract a column from the elements of an array"
tags: ["docs", "docs-scripting"]
---
## array\_column

The **array\_column** function extracts a column from the elements of an array:

```
{% set people = [
	{"id": 1, "name": "Kina Halpue", "email": "kina@cerb.example"},
	{"id": 2, "name": "Milo Dade", "email": "milo@cerb.example"},
	{"id": 3, "name": "Janey Youve", "email": "janey@cerb.example"},
] %}
The email addresses are: {{array_column(people,'email')|join(', ')}}
```

```
The email addresses are: kina@cerb.example, milo@cerb.example, janey@cerb.example
```
