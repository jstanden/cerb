---
id: "docs-scripting-functions--dictunset"
title: "Scripting Function: dict_unset"
url: "https://cerb.ai/docs/scripting/functions/#dictunset"
summary: "Remove items by key from an array or object using dot notation"
tags: ["docs", "docs-scripting"]
---
## dict\_unset

You can use the **dict\_unset** function to remove items by key from an array or object.

You can unset deeply nested keys in a single line using dot-notation:

```
{% set person = {"person":{"name":{"first":"Jane","last":"Tester"},"age":28,"location":"Secret"}} %}
{% set person = dict_unset(person, ['person.name.last','person.age','person.location']) %}
{{person|json_encode|json_pretty}}
```

```
{
    "person": {
        "name": {
            "first": "Jane"
        }
    }
}
```
