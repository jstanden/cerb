---
id: "solutions-automations-add-dictionary-column"
title: "Add a column to dictionaries"
url: "https://cerb.ai/solutions/automations/add-dictionary-column/"
summary: "This page demonstrates how to use the merge filter to add new columns to existing dictionaries. The example shows adding random age values to a list of people, illustrating dictionary manipulation techniques."
tags: ["solutions", "solutions-automations"]
---
## Using |merge filter

The [|merge](/docs/scripting/filters/#merge) filter combines two arrays or objects. We can use it in [|map](/docs/scripting/filters/#map) to add new columns to dictionaries.

- [automation](#)
- [output](#)

- 
```
start:
  set:
    people:
      kina:
        name: Kina Halpue
        email: kina@cerb.example
      milo:
        name: Milo Dade
        email: milo@cerb.example
  set/merge:
    people@json: {{people|map((v) => v|merge({'age':random(20,65)}))|json_encode}}
```
- 
```
people:
  kina:
    name: Kina Halpue
    email: kina@cerb.example
    age: 24
  milo:
    name: Milo Dade
    email: milo@cerb.example
    age: 38
```

