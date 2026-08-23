---
id: "docs-scripting-functions--attribute"
title: "Scripting Function: attribute"
url: "https://cerb.ai/docs/scripting/functions/#attribute"
summary: "Access object values with a variable key"
tags: ["docs", "docs-scripting"]
---
## attribute

Access the values of an object with a variable key:

```
{% set person = {"first_name": "Kina", "last_name": "Halpue", "title": "Customer Support Supervisor"} %}
{% set key = 'title' %}
{{attribute(person, key)}}
```

```
Customer Support Supervisor
```
